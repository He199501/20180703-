<?php
// +----------------------------------------------------------------------
// | YFCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015-2016 http://www.rainfer.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: rainfer <81818832@qq.com>
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\common\model\User as UserModel;
use app\common\widget\Widget;
use app\common\model\Common as CommonModel;

class User extends Base
{
	/*
     * 用户管理
     */
	public function userIndex(){
		$search_name=input('search_name');
		$opentype_check=input('opentype_check','');
		$activetype_check=input('activetype_check','');
		$where=[];
		if($opentype_check !== ''){
			$where[]=['open','=',$opentype_check];
		}
		if($activetype_check !== ''){
			$where[]=['status','=',$activetype_check];
		}
		if($search_name){
            $where[]=['username|email','like',"%".$search_name."%"];
        }
        $user_list=UserModel::where($where)->order('id')->paginate(config('paginate.list_rows'),false,['query'=>get_query()]);
        $page = $user_list->render();
        $page=preg_replace("(<a[^>]*page[=|/](\d+).+?>(.+?)<\/a>)","<a href='javascript:ajax_page($1);'>$2</a>",$page);
        $data=$user_list->items();
        //表格字段
        $fields=[
            ['title'=>'ID','field'=>'id'],
            ['title'=>'用户名','field'=>'username'],
            ['title'=>'邮箱','field'=>'email'],
            ['title'=>'昵称','field'=>'nickname'],
            ['title'=>'来源','field'=>'user_from','default'=>'本地'],
            ['title'=>'性别','field'=>'sex','type'=>'array','array'=>[1=>'程序猿',2=>'程序媛',3=>'保密']],
            ['title'=>'创建时间','field'=>'create_time','type'=>'datetime'],
            ['title'=>'激活','field'=>'status','type'=>'switch','url'=>url('userState'),'options'=>[0=>'未激活',1=>'已激活']],
            ['title'=>'禁用','field'=>'open','type'=>'switch','url'=>url('userOpen')],
            ['title'=>'创建时间','field'=>'create_time','type'=>'datetime']
        ];
        //主键
        $pk='id';
        //右侧操作按钮
        $right_action=[
            'edit'=>['href'=>url('userEdit'),'is_pop'=>1],
            'delete'=>url('userDel')
        ];
        $search=[
            ['select','opentype_check','',['1'=>'已启用','0'=>'未启用'],$opentype_check,'','',['is_formgroup'=>false,'default'=>'按启用状态'],'ajax_change'],
            ['select','activetype_check','',['1'=>'已激活','0'=>'未激活'],$activetype_check,'','',['is_formgroup'=>false,'default'=>'按激活状态'],'ajax_change'],
            ['text','search_name','',$search_name,'','','text',['placeholder'=>'输入用户名','is_formgroup'=>false],'search-query'],
            ['button','搜索',['class'=>'btn btn-purple btn-sm search-query ajax-search-form','type'=>'submit','icon_l'=>'ace-icon fa fa-search icon-on-right bigger-110']]
        ];
        $form=[
            'href'=>url('userIndex'),
            'class'=>'form-search',
        ];
        //实例化表单类
        $widget=new Widget();
        if(request()->isAjax()) {
            return $widget
                ->form('table', $fields, $pk, $data, $right_action, $page, '', '', 1);
        }else{
            return $widget
                ->addToparea(['add'=>['href'=>url('userAdd'),'is_pop'=>1]],[],$search,$form)
                ->addtable($fields,$pk,$data,$right_action,$page,'')
                ->setButton()
                ->fetch();
        }
	}
	/*
     * 添加用户显示
     */
	public function userAdd(){
        $region=new CommonModel;
        $province = $region->setTable(config('database.prefix').'region')->setPk('id')->where ( 'pid',1)->column('name','id');
        $linkage=[
            ['name'=>'province','title'=>'','data'=>$province,'default'=>'请选择省份'],
            ['name'=>'city','title'=>'','url'=>url('admin/Ajax/getRegion',['type'=>2]),'default'=>'请选择城市'],
            ['name'=>'town','title'=>'','url'=>url('admin/Ajax/getRegion',['type'=>3]),'default'=>'请选择乡镇']
        ];
        $widget=new Widget();
        return $widget
            ->addText('username','用户名','','*','required','text',['placeholder'=>'英文数字'])
            ->addText('password','密码','','*','required','text',['placeholder'=>'输入密码'])
            ->addText('nickname','昵称','','','','text',['placeholder'=>'输入昵称'])
            ->addLinkage('所在地',$linkage)
            ->addSelect('sex','性别',['1'=>'程序猿','2'=>'程序媛','3'=>'保密'],3,'','required')
            ->addText('mobile','手机','','','','text',['placeholder'=>'输入手机号码'])
            ->addText('user_url','个人网站','','','','text',['placeholder'=>'http://www.rainfer.cn'])
            ->addTextarea('signature','签名','无签名,不个性','')
            ->addText('email','邮箱','','','','email',['placeholder'=>'输入邮箱'])
            ->addSwitch('open','是否启用',1)
            ->addSwitch('status','是否激活',1)
            ->setUrl(url('userSave'))
            ->setAjax('ajaxForm-noJump')
            ->fetch();
	}

	/*
     * 添加用户操作
     */
	public function userSave(){
	    $user_model=new UserModel();
	    $rst=$user_model->add(input('username'),'',input('password'),input('nickname'),input('email'),input('mobile'),input('open',0),input('status',0),input('province',0),input('city',0),input('town',0),input('sex',3),input('user_url','http://www.rainfer.cn'),input('signature','无签名,不个性'));
	    if(is_string($rst)){
            $this->error($rst,'userIndex',['is_frame'=>1]);
        }elseif(is_int($rst) && $rst){
            if($rst!==false){
                $this->success('会员添加成功','userIndex',['is_frame'=>1]);
            }else{
                $this->error('会员添加失败','userIndex',['is_frame'=>1]);
            }
        }else{
            $this->error('会员添加失败','userIndex',['is_frame'=>1]);
        }
	}

	/*
     * 修改用户信息界面
     */
	public function userEdit(){
	    $id=input('id',0,'intval');
	    if(!$id) $this->error('会员不存在','userIndex');
	    $user=UserModel::get($id);
	    if(!$user) $this->error('会员不存在','userIndex');
        $region=new CommonModel;
        $province = $region->setTable(config('database.prefix').'region')->setPk('id')->where ( 'pid',1)->column('name','id');
        $city=$user['province']?($region->setTable(config('database.prefix').'region')->setPk('id')->where ( 'pid',$user['province'])->column('name','id')):[];
        $town=$user['city']?($region->setTable(config('database.prefix').'region')->setPk('id')->where ( 'pid',$user['city'])->column('name','id')):[];
        $linkage=[
            ['name'=>'province','title'=>'','data'=>$province,'default'=>'请选择省份','value'=>$user['province']],
            ['name'=>'city','title'=>'','url'=>url('admin/Ajax/getRegion',['type'=>2]),'default'=>'请选择城市','data'=>$city,'value'=>$user['city']],
            ['name'=>'town','title'=>'','url'=>url('admin/Ajax/getRegion',['type'=>3]),'default'=>'请选择乡镇','data'=>$town,'value'=>$user['town']]
        ];
        $widget=new Widget();
        return $widget
            ->addText('id','',$user['id'],'','','hidden')
            ->addText('username','用户名',$user['username'],'','readonly','text',['placeholder'=>'英文数字'])
            ->addText('password','密码','','','','text',['placeholder'=>'输入密码'])
            ->addText('nickname','昵称',$user['nickname'],'','','text',['placeholder'=>'输入昵称'])
            ->addLinkage('所在地',$linkage)
            ->addSelect('sex','性别',['1'=>'程序猿','2'=>'程序媛','3'=>'保密'],$user['sex'],'','required')
            ->addText('mobile','手机',$user['mobile'],'','','text',['placeholder'=>'输入手机号码'])
            ->addText('user_url','个人网站',$user['user_url'],'','','text',['placeholder'=>'http://www.rainfer.cn'])
            ->addTextarea('signature','签名',$user['signature'],'')
            ->addText('email','邮箱',$user['email'],'','','email',['placeholder'=>'输入邮箱'])
            ->addSwitch('open','是否启用',$user['open'])
            ->addSwitch('status','是否激活',$user['status'])
            ->setUrl(url('userUpdate'))
            ->setAjax('ajaxForm-noJump')
            ->fetch();
	}
	/*
	 * 修改用户操作
	 */
	public function userUpdate(){
        $data=input('');
        $data['province']=(int)$data['province'];
        $data['city']=(int)$data['city'];
        $data['town']=(int)$data['town'];
        if($data['password']){
            $data['pwd_salt']=random(10);
            $data['password']=encrypt_password($data['password'],$data['pwd_salt']);
        }else{
            unset($data['password']);
        }
        $rst=UserModel::update($data);
        if($rst!==false){
            $this->success('会员修改成功','userIndex',['is_frame'=>1]);
        }else{
            $this->error('会员修改失败','userIndex',['is_frame'=>1]);
        }
	}
	/*
     * 会员激活
     */
	public function userState(){
		$id=input('id');
        $status=UserModel::where('id',$id)->value('status');
        $status=$status?0:1;
        UserModel::where('id',$id)->setField('status',$status);
        $this->success($status?'已激活':'未激活',null,['result'=>$status]);
	}
	/*
     * 会员禁用
     */
    public function userOpen(){
        $id=input('id');
        $open=UserModel::where('id',$id)->value('open');
        $open=$open?0:1;
        UserModel::where('id',$id)->setField('open',$open);
        $this->success($open?'启用':'禁用',null,['result'=>$open]);
    }

	/*
     * 会员删除
     */
	public function userDel()
	{
		$id=input('id');
		$rst=UserModel::where('id',$id)->delete();
        if($rst!==false){
            $this->success('会员删除成功','userIndex');
        }else{
            $this->error('会员删除失败','userIndex');
        }
	}
}