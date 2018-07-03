<?php
// +----------------------------------------------------------------------
// | YFCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015-2016 http://www.rainfer.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: rainfer <81818832@qq.com>
// +----------------------------------------------------------------------
namespace addons\info;

use app\common\controller\Addons;
use think\facade\App;

/**
 * 后台首页信息显示
 */
class Info extends Addons
{
    public $info = [
        'name' => 'Info',
        'title' => '后台信息',
        'description' => '后台首页信息显示',
        'status' => 1,
        'author' => 'rainfer',
        'version'=> '0.1',
        'admin'  => '0',//是否有管理页面
    ];

    /**
     * @var string 原数据库表前缀
     * 用于在导入插件sql时，将原有的表前缀转换成系统的表前缀
     * 一般插件自带sql文件时才需要配置
     */
    public $database_prefix = '';

    /**
     * @var array 插件钩子
     */
    public $hooks = [
        // 钩子名称 => 钩子说明
        'gitinfo'=>'git信息钩子',
        'sysinfo' => '框架信息钩子'
    ];

    /**
     * @var array 插件管理方法,格式:['控制器/操作方法',[参数数组]])
     */
    public $admin_actions = [
        'index'=>[],//管理首页
        'config'=>['Admin/config'],//设置页
        'edit' => [],//编辑页
        'add'=>[],//增加页
    ];

    /**
     * 插件安装方法
     * @return bool
     */
    public function install()
    {
        return true;
    }

    /**
     * 插件卸载方法
     * @return bool
     */
    public function uninstall()
    {
        return true;
    }

    /**
     * 实现的gitinfo钩子方法
     * @return mixed
     */
    public function gitinfo()
    {
        $config=$this->getConfigValue();
        if(isset($config['display']) && $config['display']) return $this->fetch('gitinfo');
    }
	    /**
     * 实现的sysinfo钩子方法
     * @return mixed
     */
    public function sysinfo()
    {
        $config=$this->getConfigValue();
		if(isset($config['display']) && $config['display']){
			//系统信息
			$info = array(
				'PCTYPE'=>PHP_OS,
				'RUNTYPE'=>$_SERVER["SERVER_SOFTWARE"],
				'ONLOAD'=>ini_get('upload_max_filesize'),
				'ThinkPHPTYE'=>App::version(),
			);
			$this->assign('info',$info);
            $yfcmf_version=config('yfcmf_version');
            $this->assign('yfcmf_version',$yfcmf_version);
			return $this->fetch('sysinfo');
		}
    }
}