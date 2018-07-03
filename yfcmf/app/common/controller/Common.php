<?php
// +----------------------------------------------------------------------
// | YFCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015-2016 http://www.rainfer.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: rainfer <81818832@qq.com>
// +----------------------------------------------------------------------
namespace app\common\controller;

use app\admin\model\Admin as AdminModel;
use think\Controller;
use think\facade\Lang;
use think\captcha\Captcha;
use think\facade\Env;

class Common extends Controller
{
	protected $adminpath;
    protected $lang;
    protected function initialize()
    {
		parent::initialize();
        if (!defined('__ROOT__')) {
            define('__ROOT__', $this->request->rootUrl());
        }
        if (!file_exists(Env::get('root_path').'data/install.lock')) {
            //不存在，则进入安装
            header('Location: ' . url('install/Index/index'));
            exit();
        }
        $staticPath =__ROOT__. '/public';
        $this->assign('static_path', $staticPath);
        $this->adminpath=config('adminpath');
        $this->assign('admin_path', $this->adminpath);
        // 多语言
        if(config('lang_switch_on')){
            $this->lang=Lang::detect();
        }else{
            $this->lang=config('default_lang');
        }
        $this->lang=$this->lang?:'zh-cn';
        $this->assign('lang',$this->lang);
	}
	protected function verify_build($id)
	{
		ob_end_clean();
        $captcha = new Captcha(config('verify'));
        return $captcha->entry($id);
    }
    protected function check_admin_login()
    {
        $admin=new AdminModel();
        return $admin->is_login();
    }
    protected function verify_check($id)
    {
        $verify = new Captcha ();
        if (!$verify->check(input('verify'), $id)) {
            $this->error('验证码错误', url($this->request->module() . '/Login/login'));
        }
    }
}