<?php
// +----------------------------------------------------------------------
// | YFCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015-2016 http://www.rainfer.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: rainfer <81818832@qq.com>
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\common\widget\Widget;

class Help extends Base
{
    public function softIndex()
	{
        //表格字段
        $fields=[
            ['title'=>'软件名称','field'=>'name'],
            ['title'=>'说明','field'=>'desc'],
            ['title'=>'上传日期','field'=>'date'],
        ];
        //主键
        $pk='id';
        //数据
        $data=[
            ['id'=>1,'name'=>'谷歌浏览器','desc'=>'更好的体验html5+css3效果，下载后解压进行安装','date'=>'2015-11-5','download'=>'http://dlsw.baidu.com/sw-search-sp/soft/9d/14744/ChromeStandalone_50.0.2661.87_Setup.1461306176.exe'],
            ['id'=>2,'name'=>'winrar压缩解压软件','desc'=>'用于解压压缩包文件，这里主要用于解压本系统软件包。','date'=>'2015-11-5','download'=>'http://dlsw.baidu.com/sw-search-sp/soft/2e/10849/wrar_5.30.0.0sc.1452057954.exe']
        ];
        //右侧操作按钮
        $right_action=[
            'download'=>['title'=>'下载','field'=>'download','icon'=>'fa fa-cloud-download'],
        ];
        //实例化表单类
        $widget=new Widget();
        $options=['sh'=>'上海','gz'=>'广州','sz'=>'深圳','wh'=>'武汉'];
        return $widget
            ->addtable($fields,$pk,$data,$right_action)
            ->setButton([])
            ->fetch();
    }
    public function formtest()
    {
        $widget=new Widget();
        return $widget
            ->addFiles('name1','多文件','',$help_text='帮助文本')
            ->setUrl(url('newsSave'))
            ->setAjax('ajaxForm-noJump')
            ->fetch();
    }
}