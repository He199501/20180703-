<?php
// +----------------------------------------------------------------------
// | YFCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015-2016 http://www.rainfer.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: rainfer <81818832@qq.com>
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\admin\model\Admin as AdminModel;
use app\admin\model\AuthGroup as AuthGroupModel;
use app\admin\model\News as NewsModel;
use app\common\model\User as UserModel;
use app\admin\model\AuthGroup;
use app\admin\model\AuthGroupAccess as AuthGroupAccessModel;
use app\admin\model\AuthRule as AuthRuleModel;
use think\Db;
use think\facade\Cache;
use app\common\widget\Widget;
use think\facade\Session;

class Admin extends Base
{
	/**
	 * 管理员列表
	 */
	public function adminIndex()
	{
		$search_name=input('search_name');
		$this->assign('search_name',$search_name);
		$map=[];
		if($search_name){
			$map[]= ['username','like',"%".$search_name."%"];
		}
        $admin_list=AdminModel::with('groups')->where($map)->order('id')->paginate(config('paginate.list_rows'),false,['query'=>get_query()]);
		$page = $admin_list->render();
        $page=preg_replace("(<a[^>]*page[=|/](\d+).+?>(.+?)<\/a>)","<a href='javascript:ajax_page($1);'>$2</a>",$page);
        $data=$admin_list->items();
        //表格字段
        $fields=[
            ['title'=>'ID','field'=>'id'],
            ['title'=>'用户名','field'=>'username'],
            ['title'=>'邮箱','field'=>'email'],
            ['title'=>'用户组','field'=>'groups.0.title'],//因为是多对多,默认只取第1个组
            ['title'=>'真实姓名','field'=>'realname'],
            ['title'=>'登录次数','field'=>'logtimes'],
            ['title'=>'登录IP','field'=>'last_ip'],
            ['title'=>'创建时间','field'=>'create_time','type'=>'datetime']
        ];
        //主键
        $pk='id';
        //右侧操作按钮
        $right_action=[
            'edit'=>['href'=>url('adminEdit'),'is_pop'=>1],
            'delete'=>url('adminDel')
        ];
        $search=[
            ['text','search_name','',$search_name,'','','text',['placeholder'=>'输入用户名','is_formgroup'=>false],'search-query'],
            ['button','搜索',['class'=>'btn btn-purple btn-sm search-query ajax-search-form','type'=>'submit','icon_l'=>'ace-icon fa fa-search icon-on-right bigger-110']]
        ];
        $form=[
            'href'=>url('adminIndex'),
            'class'=>'form-search',
        ];
        //实例化表单类
        $widget=new Widget();
        if(request()->isAjax()) {
            return $widget
                ->form('table', $fields, $pk, $data, $right_action, $page, '', '', 1);
        }else{
            return $widget
                ->addToparea(['add'=>['href'=>url('adminAdd'),'is_pop'=>1]],[],$search,$form)
                ->addtable($fields,$pk,$data,$right_action,$page,'')
                ->setButton()
                ->fetch();
        }
	}
	/**
	 * 管理员添加
	 */
	public function adminAdd()
	{
		$auth_group=AuthGroupModel::column('title','id');
        $widget=new Widget();
        return $widget
            ->addSelect('group_id','所属用户组',$auth_group,'','*','required',['default'=>'请选择所属组'])
            ->addText('username','用户名','','*','required','text',['placeholder'=>'英文数字'])
            ->addText('password','密码','','*','required','text',['placeholder'=>'输入密码'])
            ->addText('email','邮箱','','','','email',['placeholder'=>'输入邮箱'])
            ->addText('realname','真实姓名','','','','text',['placeholder'=>'真实姓名'])
            ->setUrl(url('adminSave'))
            ->setAjax('ajaxForm-noJump')
            ->fetch();
	}
	/**
	 * 管理员添加操作
	 */
	public function adminSave()
	{
	    $rst=AdminModel::add(input('username'),'',input('password'),input('email',''),input('realname',''),input('group_id',1,'intval'));
		if(is_int($rst) && $rst){
			$this->success('管理员添加成功','adminIndex',['is_frame'=>1]);
		}elseif(is_string($rst)){
			$this->error($rst,'adminIndex',['is_frame'=>1]);
		}else{
            $this->error('管理员添加失败','adminIndex',['is_frame'=>1]);
        }
	}
	/**
	 * 管理员修改
	 */
	public function adminEdit()
	{
	    $id=input('id',0,'intval');
		$admin=AdminModel::with('groups')->find($id);
		if(!$admin) $this->error('管理员不存在','adminIndex');
        $auth_group=AuthGroupModel::column('title','id');
        $widget=new Widget();
        $return=input('return','');
        if($return) $widget=$widget->addText('return','',$return,'','','hidden');
        return $widget
            ->addText('id','',$admin['id'],'','','hidden')
            ->addSelect('group_id','所属用户组',$auth_group,$admin['groups'][0]['id'],'*','required',['default'=>'请选择所属组'])
            ->addText('username','用户名',$admin['username'],'*','readonly','text',['placeholder'=>'英文数字'])
            ->addText('password','密码','','','','text',['placeholder'=>'如不需改密码请留空'])
            ->addText('email','邮箱',$admin['email'],'','','email',['placeholder'=>'输入邮箱'])
            ->addText('realname','真实姓名',$admin['realname'],'','','text',['placeholder'=>'真实姓名'])
            ->setUrl(url('adminUpdate'))
            ->setAjax('ajaxForm-noJump')
            ->fetch();
	}
	/**
	 * 管理员修改操作
	 */
	public function adminUpdate()
	{
		$data=input('post.');
		$rst=AdminModel::edit($data);
		$data['return']=(isset($data['return']) && $data['return'])?$data['return']:'adminIndex';
		if($rst!==false){
			$this->success('管理员修改成功',$data['return'],['is_frame'=>1]);
		}else{
			$this->error('管理员修改失败',$data['return'],['is_frame'=>1]);
		}
	}
	/**
	 * 管理员删除
	 */
	public function adminDel()
	{
		$admin_id=input('id');
		if (empty($admin_id)){
			$this->error('用户ID不存在','adminIndex');
		}
		if($admin_id == session('admin_auth.aid')){
            $this->error('不能删除自身','adminIndex');
        }
        if($admin_id == 1){
            $this->error('不能删除ID为1的管理员','adminIndex');
        }
        $access_model=new AuthGroupAccessModel;
        // 启动事务
        Db::startTrans();
        try {
            $admin=AdminModel::get($admin_id);
            $access_model->where('uid',$admin_id)->delete();
            AdminModel::destroy($admin_id);
            //删除对应uid
            UserModel::del($admin['uid']);
            // 提交事务
            Db::commit();
            $this->success('管理员删除成功','adminIndex');
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $this->error('管理员删除失败','adminIndex');
        }
	}
	/**
	 * 用户组列表
	 */
	public function authGroupIndex()
	{
        $data=AuthGroup::all();
        foreach ($data as &$value){
            $value['setting']=url('authGroupSetting',['id'=>$value['id']]);
        }
        //表格字段
        $fields=[
            ['title'=>'ID','field'=>'id'],
            ['title'=>'组名','field'=>'title'],
            ['title'=>'状态','field'=>'status','type'=>'switch','url'=>url('authGroupStatus')],
            ['title'=>'创建时间','field'=>'create_time','type'=>'datetime']
        ];
        //主键
        $pk='id';
        //右侧操作按钮
        $right_action=[
            'setting'=>['field'=>'setting','title'=>'配置规则','icon'=>'ace-icon fa fa-cog bigger-130','class'=>'blue','is_pop'=>1],
            'edit'=>['href'=>url('authGroupEdit'),'is_pop'=>1],
            'delete'=>url('authGroupDel')
        ];
        //实例化表单类
        $widget=new Widget();
        if(request()->isAjax()) {
            return $widget
                ->form('table', $fields, $pk, $data, $right_action, '', '', '', 1);
        }else{
            return $widget
                ->addToparea(['add'=>['href'=>url('authGroupAdd'),'is_pop'=>1]])
                ->addtable($fields,$pk,$data,$right_action,'','')
                ->setButton()
                ->fetch();
        }
	}
	/**
	 * 用户组添加
	 */
	public function authGroupAdd()
	{
        $widget=new Widget();
        return $widget
            ->addText('title','管理组名','','*','required','text',['placeholder'=>'输入管理组名'])
            ->addSwitch('status','是否启用',1)
            ->setUrl(url('authGroupSave'))
            ->setAjax('ajaxForm-noJump')
            ->fetch();
	}
	/**
	 * 用户组添加操作
	 */
	public function authGroupSave()
	{
        $sldata=array(
            'title'=>input('title',''),
            'status'=>input('status',0),
            'create_time'=>time(),
        );
        $rst=AuthGroup::create($sldata);
        if($rst!==false){
            $this->success('用户组添加成功','authGroupIndex',['is_frame'=>1]);
        }else{
            $this->error('用户组添加失败','authGroupIndex',['is_frame'=>1]);
        }
	}
    /**
     * 用户组编辑
     */
    public function authGroupEdit()
    {
        $id=input('id',0,'intval');
        if(!$id) $this->error('用户组不存在','authGroupIndex');
        if($id==1) $this->error('超级管理员组不允许修改','authGroupIndex');
        $group=AuthGroup::get($id);
        $widget=new Widget();
        return $widget
            ->addText('id','',$id,'','','hidden')
            ->addText('title','管理组名',$group['title'],'*','required','text',['placeholder'=>'输入管理组名'])
            ->addSwitch('status','是否启用',$group['status'])
            ->setUrl(url('authGroupUpdate'))
            ->setAjax('ajaxForm-noJump')
            ->fetch();
    }
    /**
     * 用户组编辑操作
     */
    public function authGroupUpdate()
    {
        $id=input('id',0,'intval');
        if(!$id) $this->error('用户组不存在','authGroupIndex',['is_frame'=>1]);
        if($id==1) $this->error('超级管理员组不允许修改','authGroupIndex',['is_frame'=>1]);
        $sldata=array(
            'id'=>$id,
            'title'=>input('title'),
            'status'=>input('status'),
        );
        $rst=AuthGroup::update($sldata);
        if($rst!==false){
            $this->success('用户组修改成功','authGroupIndex',['is_frame'=>1]);
        }else{
            $this->error('用户组修改失败','authGroupIndex',['is_frame'=>1]);
        }
    }
	/**
	 * 用户组删除操作
	 */
	public function authGroupDel()
	{
		$id=input('id',0,'intval');
		if(!$id) $this->error('用户组不存在','authGroupIndex');
	    if($id==1) $this->error('超级管理员组不允许删除','authGroupIndex');
	    $rst=AuthGroup::destroy(input('id',0,'intval'));
		if($rst!==false){
			$this->success('用户组删除成功','authGroupIndex');
		}else{
			$this->error('用户组删除失败','authGroupIndex');
		}
	}
	/**
	 * 用户组开启/禁用
	 */
	public function authGroupState()
	{
        $id=input('id',0,'intval');
        if(!$id) $this->error('用户组不存在','authGroupIndex');
        if($id==1) $this->error('超级管理员组不允许修改','authGroupIndex');
		$status=AuthGroup::where('id',$id)->value('status');
        $status=$status?0:1;
        AuthGroup::where('id',$id)->setField('status',$status);
        $this->success($status?'启用':'禁用',null,['result'=>$status]);
	}
	/**
	 * 权限配置
	 */
	public function authGroupSetting()
	{
		$admin_group=AuthGroup::get(input('id'));
		$data=AuthRuleModel::getRuelsTree();
		$this->assign('admin_group',$admin_group);
		$this->assign('datab',$data);
		return $this->fetch();
	}
	/**
	 * 权限配置保存
	 */
	public function authGroupAccess()
	{
		$new_rules = input('new_rules/a');
		$imp_rules = implode(',', $new_rules);
		$sldata=array(
			'id'=>input('id'),
			'rules'=>$imp_rules,
		);
		if(AuthGroup::update($sldata) !==false){
			Cache::clear();
			$this->success('权限配置成功','authGroupIndex',['is_frame'=>1]);
		}else{
			$this->error('权限配置失败','authGroupIndex',['is_frame'=>1]);
		}
	}
	/*
	 * 管理员信息
	 */
	public function profile()
	{
		$admin_model=new AdminModel();
        $admin=$admin_model->alias("a")->join(config('database.prefix').'auth_group_access b','a.id =b.uid')
                ->join(config('database.prefix').'auth_group c','b.group_id = c.id')
                ->where(array('a.id'=>session('admin_auth.aid')))->find();
        $news_count=NewsModel::where('author',session('admin_auth.uid'))->count();
        $admin['news_count']=$news_count;
		$this->assign('admin', $admin);
		//头像剪裁
        $attr_data['jcrop']=[
            'upload_path'=>'/data/upload/avatar',
            'upload_url'=>url('avatar'),
            'id'=>'jcrop_avatar',
            'title'=>'头像修改'
        ];
        $this->assign('attr_data', $attr_data);
		return $this->fetch();
	}
    /*
 * 管理员头像
 */
    public function avatar()
    {
        $imgurl=input('imgurl');
        //去'/'
        $imgurl=str_replace('/','',$imgurl);
        $url='/data/upload/avatar/'.$imgurl;
        if(config('storage.storage_open')){
            //七牛
            $upload = \Qiniu::instance();
            $info = $upload->uploadOne('.'.$url,"image/");
            if ($info) {
                $imgurl= config('storage.domain').$info['key'];
                @unlink('.'.$url);
            }
        }
        $rst=Db::name('admin')->where('id',session('admin_auth.aid'))->setField('avatar',$imgurl);
        if($rst!==false){
            $this->success ('头像更新成功','admin/Admin/profile');
        }else{
            $this->error ('头像更新失败','admin/Admin/profile');
        }
    }
}