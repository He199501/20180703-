<?php
// +----------------------------------------------------------------------
// | YFCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015-2016 http://www.rainfer.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: rainfer <81818832@qq.com>
// +----------------------------------------------------------------------
namespace app\common\controller;

use think\facade\Config;
use think\facade\Cache;
use app\common\model\Addon as AddonModel;
use think\View;

/**
 * 插件基类
 * Class Addons
 * @package think\addons
 */
abstract class Addons
{
    /**
     * 视图实例对象
     * @var view
     * @access protected
     */
    protected $view = null;

    // 当前错误信息
    protected $error;
    public $info = [];
    public $addons_path = '';
    public $config_file = '';
 
    /**
     * 构造方法
     * @access public
     */
    public function __construct()
    {
        // 获取当前插件目录
        $this->addons_path = Config::get('addon_path') . $this->getName() . DIRECTORY_SEPARATOR;
        // 读取当前插件配置信息
        if (is_file($this->addons_path . 'config.php')) {
            $this->config_file = $this->addons_path . 'config.php';
        }

        // 初始化视图模型
        $config = ['view_path' => $this->addons_path];
        $config = array_merge(Config::get('template.'), $config);
        $view=new View();
        $this->view = $view->init($config);

        // 控制器初始化
        if (method_exists($this, 'initialize')) {
            $this->initialize();
        }
    }

    /**
     * 获取插件的配置的值
     * @param string $name 插件名
     * @return array|mixed|null
     */
    final public function getConfigValue($name = '')
    {
        static $_config = array();
        if (empty($name)) {
            $name = $this->getName();
        }
        if (isset($_config[$name])) {
            return $_config[$name];
        }
        $config = Cache::get('addon_config_'.$name);
        if(!$config){
            $config = AddonModel::where('name',$name)->value('config');
            if($config){
                $config=json_decode($config,true);
                Cache::set('addon_config_'.$name,$config);
            }
        }
        if(!$config){
            //默认值
            if (is_file($this->config_file)) {
                $temp_arr = include $this->config_file;
                $i=0;
                foreach ($temp_arr as $value) {
                    $type=isset($value[0])?$value[0]:'';
                    $i++;
                    switch ($type){
                        case 'checkbox':
                        case 'radio':
                        case 'select':
                        case 'selects':
                            $k=isset($value[1])?$value[1]:($type.$i);
                            $v=isset($value[4])?$value[4]:'';
                            $config[$k]=$v;
                            break;
                        case 'color':
                        case 'date':
                        case 'daterange':
                        case 'datetime':
                        case 'file':
                        case 'files':
                        case 'icon':
                        case 'image':
                        case 'images':
                        case 'mask':
                        case 'range':
                        case 'switch':
                        case 'tag':
                        case 'text':
                        case 'textarea':
                        case 'time':
                        case 'ueditor':
                            $k=isset($value[1])?$value[1]:($type.$i);
                            $v=isset($value[3])?$value[3]:'';
                            $config[$k]=$v;
                            break;
                        case 'linkage':
                            $datas=isset($value[2])?$value[2]:[];
                            if(is_array($datas) && $datas){
                                foreach ($datas as $data){
                                    $k=$data['name'];
                                    $v=$data['value'];
                                    $config[$k]=$v;
                                }
                            }
                            break;
                        case 'group':
                            $groups=isset($value[1])?$value[1]:[];
                            if(is_array($groups) && $groups){
                                foreach ($groups as $group){
                                    if(isset($group['dropdown']) && $group['dropdown']){
                                        foreach ($group['dropdown'] as $dropdown){
                                            $items=isset($dropdown['items'])?$dropdown['items']:[];
                                            if($items){
                                                foreach ($items as $item){
                                                    $_type=isset($item[0])?$item[0]:'';
                                                    $i++;
                                                    switch ($_type){
                                                        case 'checkbox':
                                                        case 'radio':
                                                        case 'select':
                                                        case 'selects':
                                                            $k=isset($item[1])?$item[1]:($_type.$i);
                                                            $v=isset($item[4])?$item[4]:'';
                                                            $config[$k]=$v;
                                                            break;
                                                        case 'color':
                                                        case 'date':
                                                        case 'daterange':
                                                        case 'datetime':
                                                        case 'file':
                                                        case 'files':
                                                        case 'icon':
                                                        case 'image':
                                                        case 'images':
                                                        case 'mask':
                                                        case 'range':
                                                        case 'switch':
                                                        case 'tag':
                                                        case 'text':
                                                        case 'textarea':
                                                        case 'time':
                                                        case 'ueditor':
                                                            $k=isset($item[1])?$item[1]:($_type.$i);
                                                            $v=isset($item[3])?$item[3]:'';
                                                            $config[$k]=$v;
                                                            break;
                                                        case 'linkage':
                                                            $datas=isset($item[2])?$item[2]:[];
                                                            if(is_array($datas) && $datas){
                                                                foreach ($datas as $data){
                                                                    $k=$data['name'];
                                                                    $v=$data['value'];
                                                                    $config[$k]=$v;
                                                                }
                                                            }
                                                            break;
                                                    }
                                                }
                                            }
                                        }
                                    }else{
                                        $items=isset($group['items'])?$group['items']:[];
                                        if($items){
                                            foreach ($items as $item){
                                                $_type=isset($item[0])?$item[0]:'';
                                                $i++;
                                                switch ($_type){
                                                    case 'checkbox':
                                                    case 'radio':
                                                    case 'select':
                                                    case 'selects':
                                                        $k=isset($item[1])?$item[1]:($_type.$i);
                                                        $v=isset($item[4])?$item[4]:'';
                                                        $config[$k]=$v;
                                                        break;
                                                    case 'color':
                                                    case 'date':
                                                    case 'daterange':
                                                    case 'datetime':
                                                    case 'file':
                                                    case 'files':
                                                    case 'icon':
                                                    case 'image':
                                                    case 'images':
                                                    case 'mask':
                                                    case 'range':
                                                    case 'switch':
                                                    case 'tag':
                                                    case 'text':
                                                    case 'textarea':
                                                    case 'time':
                                                    case 'ueditor':
                                                        $k=isset($item[1])?$item[1]:($_type.$i);
                                                        $v=isset($item[3])?$item[3]:'';
                                                        $config[$k]=$v;
                                                        break;
                                                    case 'linkage':
                                                        $datas=isset($item[2])?$item[2]:[];
                                                        if(is_array($datas) && $datas){
                                                            foreach ($datas as $data){
                                                                $k=$data['name'];
                                                                $v=$data['value'];
                                                                $config[$k]=$v;
                                                            }
                                                        }
                                                        break;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                            break;
                    }
                }
                unset($temp_arr);
            }
        }
        $_config[$name] = $config;
        return $config;
    }

    /**
     * 获取当前模块名
     * @return string
     */
    final public function getName()
    {
        $data = explode('\\', get_class($this));
        return strtolower(array_pop($data));
    }

    /**
     * 检查配置信息是否完整
     * @return bool
     */
    final public function checkInfo()
    {
        $info_check_keys = ['name', 'title', 'description', 'status', 'author', 'version','admin'];
        foreach ($info_check_keys as $value) {
            if (!array_key_exists($value, $this->info)) {
                return false;
            }
        }
        return true;
    }

    /**
     * 加载模板和页面输出 可以返回输出内容
     * @access public
     * @param string $template 模板文件名或者内容
     * @param array $vars 模板输出变量
     * @param array $config 模板参数
     * @return mixed
     * @throws \Exception
     */
    public function fetch($template = '', $vars = [], $config = [])
    {
        if (!is_file($template)) {
            $template = '/' . $template;
        }
        // 关闭模板布局
        $this->view->engine->layout(false);

        echo $this->view->fetch($template, $vars, $config);
    }

    /**
     * 渲染内容输出
     * @access public
     * @param string $content 内容
     * @param array $vars 模板输出变量
     * @param array $replace 替换内容
     * @param array $config 模板参数
     * @return mixed
     */
    public function display($content, $vars = [], $replace = [], $config = [])
    {
        // 关闭模板布局
        $this->view->engine->layout(false);

        echo $this->view->display($content, $vars, $replace, $config);
    }

    /**
     * 渲染内容输出
     * @access public
     * @param string $content 内容
     * @param array $vars 模板输出变量
     * @return mixed
     */
    public function show($content, $vars = [])
    {
        // 关闭模板布局
        $this->view->engine->layout(false);

        echo $this->view->fetch($content, $vars, [], [], true);
    }

    /**
     * 模板变量赋值
     * @access protected
     * @param mixed $name 要显示的模板变量
     * @param mixed $value 变量的值
     * @return void
     */
    public function assign($name, $value = '')
    {
        $this->view->assign($name, $value);
    }

    /**
     * 获取当前错误信息
     * @return mixed
     */
    public function getError()
    {
        return $this->error;
    }

    //必须实现安装
    abstract public function install();

    //必须卸载插件方法
    abstract public function uninstall();
}
