<?php
// +----------------------------------------------------------------------
// | YFCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015-2016 http://www.rainfer.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: rainfer <81818832@qq.com>
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\common\model\Common as CommonModel;
use app\common\model\Files;
class Ajax
{
	/*
     * 返回行政区域json字符串
     */
	public function getRegion()
	{
		$map[]=['pid','=',input('id')];
        $region=new CommonModel;
        $list = $region->setTable(config('database.prefix').'region')->setPk('id')->where($map)->select();
		return json(['list'=>$list,'code'=>1]);
	}
	/*
     * 返回模块下控制器json字符串
     */
	public function getController()
	{
		$module=input('request_module','admin');
		$list=\ReadClass::readDir(APP_PATH . $module. DIRECTORY_SEPARATOR .'controller');
		return json($list);
	}
    /*
     * 返回地图位置
     */
    public function getMap()
    {
        $keyword=input('keyword');
        $map=[];
        if($keyword){
            $strUrl='http://api.map.baidu.com/place/v2/search?query='.$keyword.'&region=全国&city_limit=false&output=json&ak='.config('baidumap_ak');//自己去申请ak
            $jsonStr = file_get_contents($strUrl);
            $arr = json_decode($jsonStr,true);
            if($arr['results'] && $arr['results'][0]['location']){
                $map['map_lat']=$arr['results'][0]['location']['lat'];
                $map['map_lng']=$arr['results'][0]['location']['lng'];
            }
        }
        return json($map);
    }
    /*
     * 上传前检查
     */
    public function uploadCheck()
    {
        $md5=input('md5','');
        $rst=[
            'code'=>0,
            'id'=>0,
            'path'=>'',
            'state'=>'error'
        ];
        if($md5){
            $file_model=new Files();
            $file=$file_model->where('md5',$md5)->find();
            if($file){
                $rst['code']=1;
                $rst['id']=$file['id'];
                $rst['path']=$file['path'];
                $rst['state']='SUCCESS';
            }
        }
        return json($rst);
    }
}