<?php
// +----------------------------------------------------------------------
// | YFCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015-2016 http://www.rainfer.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: rainfer <81818832@qq.com>
// +----------------------------------------------------------------------

namespace app\admin\model;

use think\Model;
use app\admin\controller\Auth;
use think\Db;

/**
 * 后台菜单模型
 * @package app\admin\model
 */
class AuthRule extends Model
{
    protected $not_check_id=[1];//不检测权限的管理员id
    protected $not_check_url=['admin/Index/index','admin/Sys/clear','admin/Index/lang'];//不检测权限的url

    /**
     * 获取所有父节点id(含自身)
     * @param int $id 节点id
     * @return array
     */
    public function getParents($id =0)
    {
        //未指定节点,则自动取当前访问url的节点id
        $id=$id?:$this->getUrlId('',1);
        if(empty($id)) return [];
        $ids=cache('parents_'.$id);
        if(!$ids){
            $lists=self::order('level desc,sort')->column('pid','id');
            $ids = [];
            while (isset($lists[$id]) && $lists[$id] !=0){
                $ids[]=$id;
                $id=$lists[$id];
            }
            if(isset($lists[$id]) && $lists[$id]==0) $ids[]=$id;
            return array_reverse($ids);
        }
        return $ids;
    }
    /**
     * 获取当前节点及父节点下菜单(仅显示状态)
     * @param int $id 节点id
     * @return array|mixed
     */
    public function getParentMenus(&$id)
    {
        //未指定节点,则自动取当前访问url的节点id
        $id=$this->getUrlId('',1);
        $pid=self::where('id',$id)->value('pid');
		//取$pid下子菜单
		$menus=self::where([['status','=',1],['pid','=',$pid]])->order('sort')->select();
        return $menus;
    }
    /**
     * 获取指定url的id(可能为显示状态或非显示状态)
     * @param string $url 为空获取当前操作的id
     * @param int $status 1表示取显示状态,为空或为0则不限制
     * @return int -1表示不需要检测 0表示无后台菜单 其他表示当前url对应id
     */
    public function getUrlId($url='',$status=0)
    {
        $url=$url?:request()->module().'/'.request()->controller().'/'.request()->action();
        if($url=='//'){
            $routeInfo=request()->routeInfo();
            //插件管理
            if($routeInfo['route']=='\app\common\controller\Base@execute'){
                $menu_id = self::where('name','admin/Addons/addonsIndex')->order('level desc,sort')->value('id');
                return $menu_id?:0;
            }else{
                return 0;
            }
        }
        if(in_array($url,$this->not_check_url)) return -1;
        $where=[];
		$where[]=['name','=',$url];
		if($status) $where[]=['status','=',$status];
        $menu_id = self::where($where)->order('level desc,sort')->value('id');
        $menu_id=$menu_id?:0;
        return $menu_id;
    }
    /**
     * 权限检测
     * @param int $id 菜单id
     * @return boolean
     */
    public function checkAuth($id=0)
    {
        $id=$id?:$this->getUrlId();
        if($id==-1) return true;
        $uid=session('admin_auth.aid');
        if(in_array($uid,$this->not_check_id)) return true;
        $auth_ids_list=cache('auth_ids_list_'.$uid);
        if(empty($auth_ids_list)){
            $auth = new Auth();
            $auth_ids_list=$auth->getAuthList($uid,1,'id');
            cache('auth_ids_list_'.$uid,$auth_ids_list);
        }
        if(empty($auth_ids_list)) return false;
        if(in_array($id,$auth_ids_list)){
            return true;
        }else{
            return false;
        }
    }
    /**
     * 菜单检查是否有效
     * @param string $name
     * @return bool
     */
    public static function checkName($name)
    {
        return true;
        @list($module,$controller,$action)=explode('/',$name);
        if(!$module || !$controller || !$action) return false;
        //处理action
        $arr=explode('?',$action);
        $action=(count($arr)==1)?$action:$arr[0];
        if(has_controller($module,$controller)){
            if($action=='default'){
                return true;
            }elseif (has_action($module,$controller,$action)==2){
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }
    /**
     * 获取权限菜单
     * @param int $uid 管理员id
     * @return array
     */
    public function getRules($uid=0)
    {
        $uid=$uid?:session('admin_auth.aid');
        $menus=cache('menus_admin_'.$uid);
        if($menus) return $menus;
        $where=[];
        $where[]=['status','=',1];
        if(!in_array($uid,$this->not_check_id)){
            $auth_ids_list=cache('auth_ids_list_'.$uid);
            if(empty($auth_ids_list)){
                $auth = new Auth();
                $auth_ids_list=$auth->getAuthList($uid,1,'id');
                cache('auth_ids_list_'.$uid,$auth_ids_list);
            }
            if(empty($auth_ids_list)) return [];
            $where[]=['id','in',$auth_ids_list];
        }
        $data = self::where($where)->order('sort')->select();
        $tree=new \Tree();
        $tree->init($data,['child'=>'_child','parentid'=>'pid']);
        $menus=$tree->get_arraylist($data);
        cache('menus_admin_'.$uid,$menus);
        return $menus;
    }
    /**
     * 获取所有权限节点树
     * @return array
     */
    public static function getRuelsTree()
    {
        $rst=cache('auth_rule');
        if(!$rst){
            $data=self::order('sort')->select();
            $tree=new \Tree();
            $tree->init($data,['child'=>'sub','parentid'=>'pid']);
            $rst=$tree->get_arraylist($data);
            cache('auth_rule',$rst);
        }
        return $rst;
    }
    /**
     * 获取子菜单(直接子菜单)
     * @param int $pid
     * @param int $type 1=返回正常数组 2=返回menu_left后的数组
     * @return array
     */
    public static function getChilds($pid=0,$type=1)
    {
        $rst=[];
        if($type==1){
            $rst=cache('auth_rule_childs_1_'.$pid);
            if(!$rst){
                $rst=self::where('pid',$pid)->order('sort')->select();
                cache('auth_rule_childs_1_'.$pid,$rst);
            }
        }elseif ($type==2){
            $rst=cache('auth_rule_childs_2_'.$pid);
            if(!$rst){
                $data=self::getChilds($pid,1);
                $level=($pid==0)?0:($data?($data[0]['level']-1):0);
                $rst = menu_left($data,'id','pid','─',$pid,$level,$level*20);
                cache('auth_rule_childs_2_'.$pid,$rst);
            }
        }
        return $rst;
    }
    /**
     * 获取全部子菜单(直接子菜单)
     * @param  array  $lists 数据集
     * @param  int $pid   父级id
     * @param  bool  $only_id 是否只取id
     * @param  bool  $self 是否包含自身
     * @return array
     */
    public static function getAllChilds($lists,$pid=0,$only_id=false,$self=false)
    {
        $result = cache('auth_rule_allchilds_'.$pid.'_'.$only_id.'_'.$self);
        if(!$result){
            if(is_array($lists) && $lists){
                foreach ($lists as $id => $a) {
                    if ($a['pid'] == $pid) {
                        $result[] = $only_id?$a['id']:$a;
                        unset($lists[$id]);
                        $result = array_merge($result, self::getAllChilds($lists,$a['id'],$only_id,$self));
                    }elseif($self && $a['id'] == $pid){
                        $result[] = $only_id?$a['id']:$a;
                    }
                }
            }
            cache('auth_rule_allchilds_'.$pid.'_'.$only_id.'_'.$self,$result);
        }
        return $result;
    }
    /**
     * 获取全部菜单
     * @param int $type 1=返回正常数组 2=返回menu_left后的数组 3=返回select的数组
     * @return array
     */
    public static function getAll($type=1)
    {
        $rst=[];
        if($type==1){
            $rst=cache('auth_rule_childs_all_1');
            if(!$rst){
                $rst=self::order('sort')->column('*','id');
                cache('auth_rule_childs_all_1',$rst);
            }
        }elseif ($type==2){
            $rst=cache('auth_rule_childs_all_2');
            if(!$rst){
                $rst=self::getAll(1);
                $rst = menu_left($rst);
                cache('auth_rule_childs_all_2',$rst);
            }
        }elseif ($type==3){
            $rst=cache('auth_rule_childs_all_3');
            if(!$rst){
                $arrs=self::getAll(2);
                $rst=[];
                foreach ($arrs as $arr){
                    $rst[$arr['id']]=$arr['lefthtml'].$arr['title'];
                }
                cache('auth_rule_childs_all_3',$rst);
            }
        }
        return $rst;
    }
    /**
     * 增加菜单
     * @param string $name
     * @param string $title
     * @param int $pid
     * @param int $status
     * @param int $notcheck
     * @param int $sort
     * @param string $icon
     * @return mixed
     */
    public static function add($name,$title,$pid=0,$status=0,$notcheck=0,$sort=10,$icon='')
    {
        if(!$name || !$title) return 'name、title参数不能为空';
        if(self::checkName($name)){
            $level=self::where('id',$pid)->value('level');
            $level=intval($level)+1;
            $data=[
                'name'=>$name,
                'title'=>$title,
                'pid'=>$pid,
                'sort'=>$sort,
                'status'=>$status,
                'level'=>$level,
                'notcheck'=>$notcheck,
                'icon'=>$icon,
                'create_time'=>time()
            ];
            $rst=self::insertGetId($data);
            if($rst){
                return intval($rst);
            }else{
                return '添加失败';
            }
        }else{
            return 'name格式不正确';
        }
    }
    /**
     * 修改菜单
     * @param int $id
     * @param string $name
     * @param string $title
     * @param int $pid
     * @param int $status
     * @param int $notcheck
     * @param int $sort
     * @param string $icon
     * @return mixed
     */
    public static function edit($id,$name,$title,$pid=0,$status=0,$notcheck=0,$sort=10,$icon='')
    {
        if(!$id || !$name || !$title) return 'id、name、title参数不能为空';
        $rule=self::get($id);
        if(!$rule) return '菜单不存在';
        if(self::checkName($name)){
            if($pid !=$rule['pid']){
                $level=self::get($pid)->value('level')+1;
                $level_diff=($level>$rule['level'])?($level-$rule['level']):($rule['level']-$level);
            }else{
                $level=$rule['level'];
            }
            $data=[
                'id'=>$id,
                'name'=>$name,
                'title'=>$title,
                'pid'=>$pid,
                'sort'=>$sort,
                'status'=>$status,
                'level'=>$level,
                'notcheck'=>$notcheck,
                'icon'=>$icon
            ];
            // 启动事务
            Db::startTrans();
            try {
                self::update($data);
                if($pid !=$rule['pid']){
                    //更新子级
                    $lists=self::getAll(1);
                    $ids=self::getAllChilds($lists,$id,true);
                    if($level>$rule['level']){
                        self::where('id','in',$ids)->setInc('level',$level_diff);
                    }else{
                        self::where('id','in',$ids)->setDec('level',$level_diff);
                    }
                }
                // 提交事务
                Db::commit();
                return 1;
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                return '修改失败';
            }
        }else{
            return 'name格式不正确';
        }
    }
    /**
     * 删除菜单
     * @param int $id
     * @return bool
     */
    public static function del($id)
    {
        if(!$id) return 'id参数不能为空';
        $rule=self::get($id);
        if(!$rule) return '菜单不存在';
        // 启动事务
        Db::startTrans();
        try {
            self::destroy($id);
            //删除子级
            $lists=self::getAll(1);
            $ids=self::getAllChilds($lists,$id,true);
            self::where('id','in',$ids)->delete();
            // 提交事务
            Db::commit();
            return true;
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return false;
        }
    }
}
