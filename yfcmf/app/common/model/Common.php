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
 * 公共模型
 * @package app\admin\model
 */
class Common extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '';
    protected $pk = '';
    public function setTable($table){
        $this->table=$table;
        return $this;
    }
    public function setPk($pk){
        $this->pk=$pk;
        return $this;
    }
    /**
     * 获取子孙级ids
     * @param array $lists
     * @param bool $only_id
     * @param int $pid
     * @param bool $self
     * @param string $pid_name
     * @param string $id_name
     * @param array $where
     * @return array
     */
    public function getAllChilds($lists=[],$pid=0,$only_id=true,$self=false,$pid_name='pid',$id_name='id',$where=[])
    {
        $result = cache($this->table.'_allchilds_'.$pid.'_'.$only_id.'_'.$self);
        if(!$result){
            //根据条件取数据
            if(is_array($lists) && !$lists) $lists=$this->where($where)->column('*',$id_name);
            if(is_array($lists) && $lists){
                foreach ($lists as $id => $a) {
                    if ($a[$pid_name] == $pid) {
                        $result[] = $only_id?$a[$id_name]:$a;
                        unset($lists[$id]);
                        $result = array_merge($result, $this->getAllChilds($lists,$a[$id_name],$only_id,$self));
                    }elseif($self && $a[$id_name] == $pid){
                        $result[] = $only_id?$a[$id_name]:$a;
                    }
                }
            }
            cache($this->table.'_allchilds_'.$pid.'_'.$only_id.'_'.$self,$result);
        }
        return $result;
    }
}
