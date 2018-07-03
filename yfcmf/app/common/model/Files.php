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
 * 本地附近模型
 * @package app\admin\model
 */
class Files extends Model
{
    protected $autoWriteTimestamp = true;
    /**
     * 增加
     *
     * @param array $data 数据
     * @return mixed
     */
    public function add($data)
    {
        return self::insertGetId($data);
    }
    /**
     * 修改
     *
     * @param array $where 条件
     * @param array $data 数据
     * @return mixed
     */
    public function edit($where,$data)
    {
        return self::where($where)->update($data);
    }
}
