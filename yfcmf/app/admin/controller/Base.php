<?php

namespace app\admin\controller;

use app\common\controller\Common;
use app\admin\model\AuthRule as AuthRuleModel;

class Base extends Common
{
    /**
     * 初始化
     */
    public function initialize()
    {
        parent::initialize();
        //检测登录
        if(!$this->check_admin_login()) $this->redirect(config('adminpath').'/Login/index');//未登录
        $auth=new AuthRuleModel;

        //检测权限
        $id_curr=$auth->getUrlId();
        if(!$auth->checkAuth($id_curr)) $this->error('没有权限',config('adminpath').'/Index/index');

        //获取有权限的菜单
        $menus=$auth->getRules();
        $this->assign('menus',$menus);
        $id_curr_arr=$auth->getParents($id_curr);
        $this->assign('id_curr_arr',$id_curr_arr);
    }
}