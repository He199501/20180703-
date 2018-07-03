<?php

namespace app\admin\controller;

use app\common\controller\Common;
use app\admin\model\Admin as AdminModel;

class Login extends Common
{
    protected function initialize()
    {
        parent::initialize();
    }
    public function index()
    {
        return $this->fetch();
    }
    /**
     * 验证码
     */
    public function verify()
    {
        return $this->verify_build('aid');
    }
    /**
     * 登录验证
     */
    public function login()
    {
        if (!request()->isAjax()){
            $this->error("提交方式错误！",$this->adminpath.'/Login/index');
        }else{
            $this->verify_check('aid');
            $username=input('username');
            $password=input('password');
            $rememberme=input('rememberme');
            $admin=new AdminModel;
            if($admin->login($username,$password,$rememberme)){
                $this->success('恭喜您，登陆成功',$this->adminpath.'/Index/index');
            }else{
                $this->error($admin->getError(),$this->adminpath.'/Login/index');
            }
        }
    }
    /**
     * 退出登录
     */
    public function logout()
    {
        session('admin_auth',null);
        session('admin_auth_sign',null);
        cookie('aid', null);
        cookie('signin_token', null);
        $this->redirect($this->adminpath.'/Login/index');
    }
}