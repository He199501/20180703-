<?php


namespace app\admin\model;

use think\Model;

/**
 * 管理群组模型
 * @package app\admin\model
 */
class AuthGroup extends Model
{
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = false;

    public function groups()
    {
        return $this->belongsToMany('Admin','auth_group_access','uid','group_id');
    }
}