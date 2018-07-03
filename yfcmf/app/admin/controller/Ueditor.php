<?php
// +----------------------------------------------------------------------
// | YFCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015-2016 http://www.rainfer.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: rainfer <81818832@qq.com>
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\common\controller\Common;
use think\Db;
use think\facade\Env;
use app\common\model\Files;

class Ueditor extends Common
{
	protected $config;
	protected $type;
	protected function initialize()
	{
	    parent::initialize();
		$adminid=session('admin_auth.aid');
		$userid=session('hid');
		if(empty($adminid) && empty($userid)){
			exit("非法上传！");
		}
	}
	/**
	 * 上传
	 */
	public function upload()
	{
		//类型
	    $this->type=input('edit_type','');
		date_default_timezone_set("Asia/chongqing");
		error_reporting(E_ERROR);
		header("Content-Type: text/html; charset=utf-8");
		$CONFIG = json_decode(preg_replace("/\/\*[\s\S]+?\*\//", "", file_get_contents("./public/ueditor/config.json")), true);
		$storage_domain=config('storage.domain');
		$config_qiniu=array(
			"imageUrlPrefix"=> $storage_domain, /* 图片访问路径前缀 */
			"scrawlUrlPrefix" => $storage_domain, /* 图片访问路径前缀 */
			"snapscreenUrlPrefix" => $storage_domain, /* 图片访问路径前缀 */
			"catcherUrlPrefix" => $storage_domain, /* 图片访问路径前缀 */
			"fileUrlPrefix" => $storage_domain, /* 文件访问路径前缀 */
			"imageManagerUrlPrefix" => $storage_domain, /* 图片访问路径前缀 */
			"videoUrlPrefix" => $storage_domain, /* 视频访问路径前缀 */
		);
		if(config('storage.storage_open') && $this->type!='mats'){
			$CONFIG=array_merge($CONFIG,$config_qiniu);
		}
		$this->config=$CONFIG;
		//类型
		$action = input('action');
		switch ($action) {
			case 'config':
				$result =  json_encode($CONFIG);
				break;

				/* 上传图片 */
			case 'uploadimage':
				$result = $this->_ueditor_upload();
				break;
				/* 上传涂鸦 */
			case 'uploadscrawl':
				$result = $this->_ueditor_upload_scrawl();
				break;
				/* 上传视频 */
			case 'uploadvideo':
				$result = $this->_ueditor_upload(array('maxSize' => 1073741824,/*1G*/'exts'=>array('mp4', 'avi', 'wmv','rm','rmvb','mkv')));
				break;
				/* 上传文件 */
			case 'uploadfile':
				$result = $this->_ueditor_upload(array('exts'=>array('jpg', 'gif', 'png', 'jpeg','txt','pdf','doc','docx','xls','xlsx','zip','rar','ppt','pptx',)));
				break;

				/* 列出图片 */
			case 'listimage':
				/* 列出文件 */
			case 'listfile':
				$result = $this->_ueditor_list($action);
				break;
				/* 抓取远程文件 */
			case 'catchimage':
				$result = $this->_ueditor_upload_catch();
				break;

			default:
				$result = json_encode(array('state'=> '请求地址出错'));
				break;
		}

		/* 输出结果 */
		$callback=input('callback','');
		if ($callback && false ) {
			if (preg_match("/^[\w_]+$/", $callback)) {
				echo htmlspecialchars($callback) . '(' . $result . ')';
			} else {
				echo json_encode(array(
						'state'=> 'callback参数不合法'
				));
			}
		} else {
			exit($result) ;
		}
	}
	private function _ueditor_list($action)
	{
		/* 判断类型 */
		switch ($action) {
			/* 列出文件 */
			case 'listfile':
				$allowFiles = $this->config['fileManagerAllowFiles'];
				$listSize = $this->config['fileManagerListSize'];
				$prefix='file/';
				break;
			/* 列出图片 */
			case 'listimage':
			default:
				$allowFiles = $this->config['imageManagerAllowFiles'];
				$listSize = $this->config['imageManagerListSize'];
				$prefix='image/';
		}
		$allowFiles = substr(str_replace(".", "|", join("", $allowFiles)), 1);
		/* 获取参数 */
		$size=input('size',0,'intval');
        $start=input('start',0,'intval');
		$size = $size ? htmlspecialchars($size) : $listSize;
		$start = $start ? htmlspecialchars($start) : 0;
		$end = intval($start) + intval($size);
        if(config('storage.storage_open')){
			//七牛
			$upload = \Qiniu::instance();
			$files = $upload->listfile($prefix);
			if (!count($files)) {
				return json_encode(array(
					"state" => "no match file",
					"list" => array(),
					"start" => $start,
					"total" => count($files)
				));
			}
			/* 获取指定范围的列表 */
			$len = count($files);
			for ($i = min($end, $len) - 1, $list = array(); $i < $len && $i >= 0 && $i >= $start; $i--){
				$tmp['url']= $files[$i]['key'];
				$tmp['mtime']= $files[$i]['putTime'];
				$list[]=$tmp;
			}
		}else{
            /* 获取文件列表 */
            $path = './data/upload/';
            $files = $this->getfiles($path, $allowFiles);
            if (!count($files)) {
                return json_encode(array(
                    "state" => "no match file",
                    "list" => array(),
                    "start" => $start,
                    "total" => count($files)
                ));
            }
            /* 获取指定范围的列表 */
            $len = count($files);
            for ($i = min($end, $len) - 1, $list = array(); $i < $len && $i >= 0 && $i >= $start; $i--){
                $list[] = $files[$i];
            }
		}
		/* 返回数据 */
		$result = json_encode(array(
			"state" => "SUCCESS",
			"list" => $list,
			"start" => $start,
			"total" => count($files)
		));
		return $result;
	}
	/**
	 * 遍历获取目录下的指定类型的文件
	 * @author rainfer <81818832@qq.com>
	 * @param string $path
	 * @param string $allowFiles
	 * @param array $files
	 * @return array
	 */
	private function getfiles($path, $allowFiles, &$files = array())
	{
		if (!is_dir($path)) return null;
		if(substr($path, strlen($path) - 1) != '/') $path .= '/';
		$handle = opendir($path);
		while (false !== ($file = readdir($handle))) {
			if ($file != '.' && $file != '..') {
				$path2 = $path . $file;
				if (is_dir($path2)) {
					$this->getfiles($path2, $allowFiles, $files);
				} else {
					if (preg_match("/\.(".$allowFiles.")$/i", $file)) {
						$files[] = array(
							'url'=> __ROOT__.substr($path2,1),
							'mtime'=> filemtime($path2)
						);
					}
				}
			}
		}
		return $files;
	}
	//上传
	private function _ueditor_upload($config=array())
	{
		$title = '';
		$url='';
		$file_id=0;
		$code=0;
		if(!empty($config)){
			$this->config=array_merge($this->config,$config);;
		}
		if(config('storage.storage_open')){
			//七牛
			$upload = \Qiniu::instance();
			$info = $upload->upload();
			$error = $upload->getError();
			if ($info) {
				$state = 'SUCCESS';
				$url= $info[0]['key'];
				$title =$info[0]['name'];
                $code=200;
			}else{
				$state = $error;
			}
		}else{
			//本地上传
			$file = request()->file('upfile');
            if(!$file) $file = request()->file('file');
			if($file){
				$validate=array(
					'size'=>3145728 ,// 设置附件上传大小
					//'ext'=>array('jpg', 'gif', 'png', 'jpeg'),
				);
				$info = $file->validate($validate)->rule('uniqid')->move(Env::get('root_path')  . config('upload_path') . DIRECTORY_SEPARATOR . date('Y-m-d'));
				if($info) {
					$name=$info->getFilename();
				    $file_url=config('upload_path'). '/' . date('Y-m-d') . '/' . $name;//SplFileObject类方法
					//写入本地文件数据库
                    $data=[
                        'name'=>$name,
                        'path'=>$file_url,
                        'ext'=>$info->getExtension(),
                        'size'=>$info->getSize(),
                        'md5'=>md5_file('.'.$file_url),
                        'status'=>1
                    ];
					$files_model=new Files();
					$file_id=$files_model->add($data);
					if(!$file_id){
                        $state = '附件保存失败';
                    }else{
                        $title =$name;//SplFileObject类方法
                        $state = 'SUCCESS';
                        $url= __ROOT__.$file_url;
                        $code=200;
                    }
				}else{
					$state = $file->getError();
				}
			}else{
				$state = '未接收到文件';
			}
		}
		$response=array(
			"state" => $state,
			"url" => $url,
			"title" => $title,
			"original" =>$title,
            "id"=>$file_id,
            "code"=>$code
		);
		return json_encode($response);
	}
	//涂鸦
	private function _ueditor_upload_scrawl()
	{		
		$data = input('post.' . $this->config ['scrawlFieldName']);
        $url='';
        $title = '';
        $oriName = '';
        $file_id=0;
        $code=0;
		if (empty ($data)) {
			$state= 'Scrawl Data Empty!';
		} else {
            if(config('storage.storage_open')){
				//七牛
				$upload = \Qiniu::instance();
				$info = $upload->uploadOne('data:image/png;base64,'.$data,"image/");
				$error = $upload->getError();
				if ($info) {
					$state = 'SUCCESS';
					$url= $info['key'];
					$title =$info['name'];
					$code=200;
				}else{
					$state = $error;
				}
			}else{
				//本地存储
				$filedata = base64_decode($data);
				$savepath = save_storage_content('png', $filedata);
				if ($savepath['path']) {
                    //写入本地文件数据库
                    $data=[
                        'name'=>$savepath['name'],
                        'path'=>'/'.$savepath['path'],
                        'ext'=>'png',
                        'size'=>strlen($filedata),
                        'md5'=>md5_file('./'.$savepath['path']),
                        'status'=>1
                    ];
                    $files_model=new Files();
                    $file_id=$files_model->add($data);
                    if(!$file_id){
                        $state = '附件保存失败';
                    }else{
                        $title =$savepath['name'];//SplFileObject类方法
                        $state = 'SUCCESS';
                        $url = __ROOT__.'/'.$savepath['path'];
                        $code=200;
                    }
				} else {
					$state = 'Save scrawl file error!';
				}
			}
		}
		$response=array(
            "state" => $state,
            "url" => $url,
            "title" => $title,
            "original" =>$oriName,
            "id"=>$file_id,
            "code"=>$code
		);
		return json_encode($response);
	}
	//抓取远程文件
	private function _ueditor_upload_catch()
	{
		set_time_limit(0);
		$sret = array(
			'state' => '',
			'list' => null,
            'code'=>0
		);
		$savelist = array();
		$flist = input('post.' . $this->config ['catcherFieldName'].'/a');
		if (empty ($flist)) {
			$sret ['state'] = 'ERROR';
		} else {
			$sret ['state'] = 'SUCCESS';
            $sret ['code'] = 200;
			foreach ($flist as $f) {
				if (preg_match('/^(http|ftp|https):\\/\\//i', $f)) {
					$ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
					if (in_array('.' . $ext, $this->config ['catcherAllowFiles'])) {
						if ($filedata = file_get_contents($f)) {
                            if(config('storage.storage_open') && stripos($f,config('storage.domain'))===false){
								//七牛
								$upload = \Qiniu::instance();
								$info = $upload->uploadcatch($f,'','',$ext);
								if ($info) {
									$savelist [] = array(
										'state' => 'SUCCESS',
										'url' => $info[0]['key'],
										'size' => 0,
										'title' => '',
										'original' => '',
										'source' => htmlspecialchars($f)
									);
								}else{
									$savelist [] = array(
										'state' => 'Save remote file error!',
										'url' => '',
										'size' => '',
										'title' => '',
										'original' => '',
										'source' => htmlspecialchars($f),
									);
								}
							}else{
								//本地
								$savepath = save_storage_content($ext, $filedata);
								if ($savepath['path']) {
                                    //写入本地文件数据库
                                    $data=[
                                        'name'=>$savepath['name'],
                                        'path'=>'/'.$savepath['path'],
                                        'ext'=>'png',
                                        'size'=>strlen($filedata),
                                        'md5'=>md5_file('./'.$savepath['path']),
                                        'status'=>1
                                    ];
                                    $files_model=new Files();
                                    $file_id=$files_model->add($data);
                                    if(!$file_id){
                                        $savelist [] = array(
                                            'state' => 'Save remote file error!',
                                            'url' => '',
                                            'size' => '',
                                            'title' => '',
                                            'original' => '',
                                            'source' => htmlspecialchars($f),
                                        );
                                    }else{
                                        $savelist [] = array(
                                            'state' => 'SUCCESS',
                                            'url' => __ROOT__.'/'.$savepath['path'],
                                            'size' => strlen($filedata),
                                            'title' => '',
                                            'original' => '',
                                            'source' => htmlspecialchars($f)
                                        );
                                    }
								} else {
									$savelist [] = array(
										'state' => 'Save remote file error!',
										'url' => '',
										'size' => '',
										'title' => '',
										'original' => '',
										'source' => htmlspecialchars($f),
									);
								}
							}
						} else {
							$savelist [] = array(
							'state' => 'Get remote file error',
							'url' => '',
							'size' => '',
							'title' => '',
							'original' => '',
							'source' => htmlspecialchars($f),
							);
						}
					} else {
						$sret ['state'] = 'File ext not allowed';
					}
				} else {
					$savelist [] = array(
						'state' => 'not remote image',
						'url' => '',
						'size' => '',
						'title' => '',
						'original' => '',
						'source' => htmlspecialchars($f),
					);
				}
			}
			$sret ['list'] = $savelist;
		}
		return json_encode($sret);
	}
}