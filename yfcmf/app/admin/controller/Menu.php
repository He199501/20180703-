<?php
// +----------------------------------------------------------------------
// | YFCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015-2016 http://www.rainfer.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: rainfer <81818832@qq.com>
// +----------------------------------------------------------------------
namespace app\admin\controller;

use think\Db;
use app\common\model\Menu as MenuModel;
use app\common\widget\Widget;

class Menu extends Base
{
    public function initialize()
	{
		parent::initialize();
    }
    /**
     * 前台菜单列表
     */
	public function menuIndex()
	{
        $lang=input('lang',$this->lang);
        $map=[];
        if($lang){
            $map[]= ['lang','=',$lang];
        }
        $data=MenuModel::where($map)->order('lang Desc,sort')->select();
        foreach ($data as &$value){
            $value['add']=url('menuAdd',['id'=>$value['id']]);
        }
        //表格字段
        $fields=[
            ['title'=>'ID','field'=>'id'],
            ['title'=>'排序','field'=>'sort','type'=>'input'],
            ['title'=>'类型','field'=>'type','type'=>'array','array'=>[1=>'栏目',2=>'列表',3=>'链接']],
            ['title'=>'标题','field'=>'name'],
            ['title'=>'语言','field'=>'lang'],
            ['title'=>'状态','field'=>'status','type'=>'switch','url'=>url('menuState'),'options'=>[0=>'隐藏',1=>'显示']]
        ];
        //主键
        $pk='id';
        //右侧操作按钮
        $right_action=[
            'add'=>['field'=>'add','title'=>'添加子菜单','icon'=>'ace-icon fa fa-plus-circle bigger-130','class'=>'blue','is_pop'=>1],
            'edit'=>['href'=>url('menuEdit'),'is_pop'=>1],
            'delete'=>url('menuDel')
        ];
        $search=[
            ['select','lang','',['zh-cn'=>'中文','en-us'=>'英文'],$lang,'','',['is_formgroup'=>false],'class'=>''],
        ];
        $form=[
            'href'=>url('menuIndex'),
            'class'=>'form-search',
        ];
        $order=url('menuOrder');
        //实例化表单类
        $widget=new Widget();
        if(request()->isAjax()) {
            return $widget
                ->form('table', $fields, $pk, $data, $right_action, '', $order, '',1);
        }else{
            return $widget
                ->addToparea(['add'=>['href'=>url('menuAdd'),'is_pop'=>1]],[],$search,$form)
                ->addtable($fields,$pk,$data,$right_action,'',$order)
                ->setButton()
                ->fetch();
        }
	}
    /**
     * 前台菜单添加显示
     */
	public function menuAdd()
	{
		$pid=input('id',0);
		$menu_model=new MenuModel;
		if($pid){
			$lang=$menu_model->where('id',$pid)->value('lang');
		}else{
            $lang='';
        }
        $where=array();
        if(!empty($lang)){
            $where[]=array('lang','=',$lang);
        }
		$tpls=get_tpls($lang);
        $menu_text=$menu_model->where($where)->order('lang Desc,sort')->select();
        $menu_text = menu_left($menu_text,'id','pid');
        $data=[];
        foreach ($menu_text as $value){
            $data[$value['id']]=$value['lefthtml'].$value['name'];
        }

        $widget=new Widget();
        return $widget
            ->addRadio('type','菜单类型',['1'=>'栏目','2'=>'列表','3'=>'链接'],2)
            ->addText('url','链接地址','','外链或单页地址','','text')
            ->addSelect('pid','父级权限',$data,$pid,'','',['default'=>'顶级'])
            ->addSelect('lang','选择语言',['zh-cn'=>'中文','en-us'=>'英文'],$lang,'','',['default'=>'请选择语言'])
            ->addText('name','菜单名','','*','required','text')
            ->addText('enname','菜单英文名','','','','text')
            ->addSwitch('status','是否启用',0)
            ->addText('sort','排序',50,'* 从小到大排序','required','number')
            ->addSelect('tpl_list','列表页模板',$tpls,'','','',['default'=>'请选择模板'])
            ->addSelect('tpl_detail','详情页模板',$tpls,'','','',['default'=>'请选择模板'])
            ->addText('seo_title','SEO标题','','','','text')
            ->addText('seo_kwd','SEO关键词','','','','text')
            ->addText('seo_dec','SEO描述','','','','text')
            ->setTrigger('type','3','url'.false)
            ->setTrigger('pid','','lang'.false)
            ->setUrl(url('menuSave'))
            ->setAjax('ajaxForm-noJump')
            ->fetch();
	}
    /**
     * 前台菜单添加操作
     */
	public function menuSave()
	{
        $name=input('name');
        if(!$name) $this->error('name不为为空','menuIndex');
	    $menu_model=new MenuModel;
	    //处理语言
        $pid=input('pid',0,'intval');
        $menu_pid=[];
        if($pid){
            $menu_pid=$menu_model->find($pid);
            $lang=$menu_pid['lang'];
        }else{
            $lang=input('lang',$this->lang);
        }
        $type=input('type',2,'intval');
        //构建数组
        $data=array(
            'name'=>$name,
            'lang'=>$lang,
            'enname'=>input('enname'),
            'type'=>$type,
            'pid'=>$pid,
            'tpl_list'=>input('tpl_list','list.html'),
            'tpl_detail'=>input('tpl_detail','detail.html'),
            'url'=>$type==3?input('url'):'',
            'status'=>input('status',0),
            'sort'=>input('sort'),
            'seo_title'=>input('seo_title'),
            'seo_kwd'=>input('seo_kwd'),
            'seo_dec'=>input('seo_dec')
        );
        // 启动事务
        Db::startTrans();
        try {
            $menu_model::create($data);
            if($menu_pid && $menu_pid['type']==2){
                $menu_model->where('id',$pid)->setField('type',1);
            }
            // 提交事务
            Db::commit();
            cache('site_nav_main',null);
            $this->success('菜单添加成功','menuIndex');
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $this->error('菜单添加失败','menuIndex');
        }
	}
    /**
     * 前台菜单编辑显示
     */
	public function menuEdit()
	{
        $id=input('id',0);
        if(!$id) $this->error('菜单不存在','menuIndex');
        $menu_model=new MenuModel;
        $menu=$menu_model->find($id);
        if(!$menu) $this->error('菜单不存在','menuIndex');
        $tpls=get_tpls($menu['lang']);
        $menu_text=$menu_model->where('lang',$menu['lang'])->order('lang Desc,sort')->select();
        $menu_text = menu_left($menu_text,'id','pid');
        $data=[];
        foreach ($menu_text as $value){
            $data[$value['id']]=$value['lefthtml'].$value['name'];
        }

        $widget=new Widget();
        return $widget
            ->addText('id','',$id,'','','hidden')
            ->addRadio('type','菜单类型',['1'=>'栏目','2'=>'列表','3'=>'链接'],$menu['type'])
            ->addText('url','链接地址',$menu['url'],'外链或单页地址','','text')
            ->addSelect('pid','父级权限',$data,$menu['pid'],'','',['default'=>'顶级'])
            ->addSelect('lang','选择语言',['zh-cn'=>'中文','en-us'=>'英文'],$menu['lang'],'','',['default'=>'请选择语言'])
            ->addText('name','菜单名',$menu['name'],'*','required','text')
            ->addText('enname','菜单英文名',$menu['enname'],'','','text')
            ->addSwitch('status','是否启用',$menu['status'])
            ->addText('sort','排序',$menu['sort'],'* 从小到大排序','required','number')
            ->addSelect('tpl_list','列表页模板',$tpls,$menu['tpl_list'],'','',['default'=>'请选择模板'])
            ->addSelect('tpl_detail','详情页模板',$tpls,$menu['tpl_detail'],'','',['default'=>'请选择模板'])
            ->addText('seo_title','SEO标题',$menu['seo_title'],'','','text')
            ->addText('seo_kwd','SEO关键词',$menu['seo_kwd'],'','','text')
            ->addText('seo_dec','SEO描述',$menu['seo_dec'],'','','text')
            ->setTrigger('type','3','url'.false)
            ->setTrigger('pid','','lang'.false)
            ->setUrl(url('menuSave'))
            ->setAjax('ajaxForm-noJump')
            ->fetch();
	}
    /**
     * 前台菜单编辑操作
     */
	public function menuUpdate()
	{
        $id=input('id',0);
        if(!$id) $this->error('菜单不存在','menuIndex');
        $name=input('name');
        if(!$name) $this->error('name不为为空','menuIndex');
        $menu_model=new MenuModel;
        //处理语言
        $pid=input('pid',0,'intval');
        $menu_pid=[];
        if($pid){
            $menu_pid=$menu_model->find($pid);
            $lang=$menu_pid['lang'];
        }else{
            $lang=input('lang',$this->lang);
        }
        $type=input('type',2,'intval');
        //构建数组
        $data=array(
            'id'=>$id,
            'name'=>$name,
            'lang'=>$lang,
            'enname'=>input('enname'),
            'type'=>$type,
            'pid'=>$pid,
            'tpl_list'=>input('tpl_list','list.html'),
            'tpl_detail'=>input('tpl_detail','detail.html'),
            'url'=>$type==3?input('url'):'',
            'status'=>input('status',0),
            'sort'=>input('sort'),
            'seo_title'=>input('seo_title'),
            'seo_kwd'=>input('seo_kwd'),
            'seo_dec'=>input('seo_dec')
        );
        // 启动事务
        Db::startTrans();
        try {
            $menu_model::update($data);
            if($menu_pid && $menu_pid['type']==2){
                $menu_model->where('id',$pid)->setField('type',1);
            }
            // 提交事务
            Db::commit();
            cache('site_nav_main',null);
            $this->success('菜单编辑成功','menuIndex');
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $this->error('菜单编辑失败','menuIndex');
        }
	}
    /**
     * 前台菜单删除
     */
	public function menuDel()
	{
		$id=input('id',0,'intval');
        $menu_model=new MenuModel();
        if(!$id) $this->error('菜单不存在','menuIndex');
        $menu=$menu_model->find($id);
        if(!$menu) $this->error('菜单不存在','menuIndex');
		$ids=get_menu_byid($id,1,2);//返回含自身id及子菜单id数组
        // 启动事务
        Db::startTrans();
        try {
            MenuModel::destroy($ids);
            $menu_pid=$menu_model->find($menu['pid']);
            //判断其父菜单是否还存在子菜单，如无子菜单，且父菜单类型为1
            if($menu['pid'] && $menu_pid['type']==1){
                $child=$menu_model->where('pid',$menu_pid['id'])->select();
                if(empty($child)){
                    $menu_model->where('id',$menu['pid'])->update(['type'=>2]);
                }
            }
            // 提交事务
            Db::commit();
            cache('site_nav_main',null);
            $this->success('菜单删除成功','menuIndex');
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $this->error('菜单删除失败','menuIndex');
        }
	}
    /**
     * 前台菜单排序
     */
	public function menuOrder(){
        $datas=input('post.');
        $data=[];
        foreach ($datas as $id => $sort){
            $data[]=['id'=>$id,'sort'=>$sort];
        }
        $menu_model=new MenuModel();
        $rst=$menu_model->saveAll($data);
        if($rst !==false){
            cache('site_nav_main',null);
            $this->success('排序更新成功','menuIndex');
        }else{
            $this->error('排序更新失败','menuIndex');
        }
	}
    /**
     * 前台菜单开启/禁止
     */
	public function menuState()
	{
        $id=input('id',0,'intval');
        if(!$id) $this->error('菜单不存在','authGroupIndex');
        $status=MenuModel::where('id',$id)->value('status');
        $status=$status?0:1;
        MenuModel::where('id',$id)->setField('status',$status);
        cache('site_nav_main',null);
        $this->success($status?'启用':'禁用',null,['result'=>$status]);
	}
}