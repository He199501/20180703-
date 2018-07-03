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

/**
 * 配置模型
 * @package app\admin\model
 */
class Options extends Model
{
    /*
     * 获取系统基本设置
     */
    public static function getOption($name='',$lang='zh-cn')
    {
        $option = cache($name.'_'.$lang);
        if(empty($option)){
            self::where([['name','=',$name],['lang','=',$lang]])->column('value','name');
        }
        return $option;
    }
    /*
     * 获取系统基本设置(组)
     */
    public static function getOptions($group='',$lang='')
    {
        $options = cache($group.'_'.$lang);
        if(empty($options)){
            $where=[];
            if($group){
                $where[]=['group','=',$group];
            }
            if($lang){
                $where[]=['lang','=',$lang];
            }
            $options=self::where($where)->column('value','name');
            cache($group.'_'.$lang,$options);
        }
        return $options;
    }
    /*
     * 设置系统基本设置
     */
    public static function setOption($name='',$value='',$lang='zh-cn')
    {
        $where[]=['name','=',$name];
        if($lang){
            $where[]=['lang','=',$lang];
        }
        self::where($where)->update(['value'=>$value]);
        cache($name.'_'.$lang,null);
    }
    /*
     * 设置系统基本设置(组)
     */
    public static function setOptions($options=[],$lang='zh-cn')
    {
        if(is_array($options) && $options){
            foreach ($options as $name=>$option){
                self::setOption($name,$option,$lang);
            }
        }
    }
    /*
     * 清设置缓存
     */
    public static function delCache($name='',$group='',$lang='zh-cn')
    {
        if($name && $lang){
            cache($name.'_'.$lang,null);
        }
        if($group && $lang){
            cache($group.'_'.$lang,null);
        }
    }
}
