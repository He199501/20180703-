<?php
// +----------------------------------------------------------------------
// | YFCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015-2016 http://www.rainfer.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: rainfer <81818832@qq.com>
// +----------------------------------------------------------------------

namespace app\common\model;

use think\Model;

/**
 * 文章模型
 * @package app\admin\model
 */
class News extends Model
{
	public function user()
	{
		return $this->belongsTo('User','id');
	}
	public function menu()
	{
		return $this->belongsTo('Menu','id');
	}
}
