<?php
// +----------------------------------------------------------------------
// | YFCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015-2016 http://www.rainfer.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: rainfer <81818832@qq.com>
// +----------------------------------------------------------------------

namespace app\common\behavior;

use app\common\model\Hook as HookModel;
use app\common\model\HookAddon as HookAddonModel;
use app\common\model\Addon as AddonModel;
use think\facade\Env;

/**
 * 注册钩子
 * @package app\common\behavior
 */
class Hook
{
    /**
     * 执行行为 run方法是Behavior唯一的接口
     * @access public
     * @param mixed $params  行为参数
     * @return void
     */
    public function run($params)
    {
        if (!file_exists(Env::get('root_path').'data/install.lock')) {
            return;
        }
        $hook_addons = cache('hook_addons');
        $hooks        = cache('hooks');
        $addons      = cache('addons');
        if (!$hook_addons) {
            // 所有钩子
            $hooks = HookModel::where('status', 1)->column('status', 'name');
            // 所有插件
            $addons = AddonModel::where('status', 1)->column('status', 'name');
            // 钩子对应的插件
            $hook_addons = HookAddonModel::where('status', 1)->order('hook,sort')->select();
            // 非开发模式，缓存数据
            if (config('yfcmf.app_debug') == false) {
                cache('hook_addons', $hook_addons);
                cache('hooks', $hooks);
                cache('addons', $addons);
            }
        }
        if ($hook_addons) {
            foreach ($hook_addons as $value) {
                if (isset($hooks[$value['hook']]) && isset($addons[$value['addon']])) {
                    \think\facade\Hook::add($value['hook'], get_addon_class($value['addon']));
                }
            }
        }
    }
}