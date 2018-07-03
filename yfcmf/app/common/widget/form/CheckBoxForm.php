<?php
// +----------------------------------------------------------------------
// | YFCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015-2016 http://www.rainfer.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: rainfer <81818832@qq.com>
// +----------------------------------------------------------------------
namespace app\common\widget\form;

/*
 * 多选表单
 *
 */
class CheckBoxForm
{
    protected $default = [
        'name' => '',//name
        'title' => '',//标签
        'options'=>[],////['value'=>'name',...]
        'value'=>'',
        'disabled'=>[],
        'attr' => ['size'=>'sm','style'=>'1','label_class'=>'col-sm-3','div_class'=>'col-sm-9'],//属性
        'extra_attr'=>'',//input的额外属性
        'extra_class' => ''//input的额外class
    ];

    /**
     * 渲染
     *
     * @param string $name 复选框名
     * @param string $title 复选框标题
     * @param array $options 复选框数据
     * @param string $default 默认值
     * @param array $disabled 复选禁止
     * @param array $attr 属性，
     *      size-尺寸(sm,lg)，默认sm
     *      style-样式(1,2)，默认1
     * @param string $extra_class 额外css类名
     * @param string $extra_attr 额外属性
     * @return string
     */
    public function fetch($name,$title,$options=[],$default='',$disabled =[],$attr=[],$extra_class='',$extra_attr='')
    {
        $data=[
            'name' => $name,//name
            'title' => $title,//标签
            'options' => $options,//['value'=>'name',...]
            'value'=>$default,
            'disabled'=>$disabled,//[value,...]
            'attr'=>$attr,
            'extra_class' => $extra_class,//input的class
            'extra_attr' => $extra_attr//input的css
        ];
        $data['attr']=isset($data['attr'])?array_merge($this->default['attr'], $data['attr']):$this->default['attr'];
        $data = array_merge($this->default, $data);
        $html = '<div class="form-group">';
        $html .= '<label class="'.$data['attr']['label_class'].' control-label no-padding-right"> '.$data['title'].' </label>';
        $html .= '<div class="'.$data['attr']['div_class'].'">';
        if($data['options']){
            $i=0;
            $values=explode(',',$data['value']);
            foreach ($data['options'] as $key=>$vo){
                $i++;
                $html .='<div class="checkbox checkbox-inline"><label class="no-padding-left">';
                $html .= '<input type="checkbox" name="' . $data['name'] . '" id="checkbox-'.$data['name'].'-'.$i.'" value="'.$key.'" class="ace ace-checkbox-'.$data['attr']['style'].' input-'.$data['attr']['size'].' ' . $data['extra_class'] . '" '.((in_array($key,$values))?'checked':'').' style=" ' . $data['extra_attr'] . '" '.(($data['disabled'] && in_array($key,$data['disabled']))?'disabled':''). ' />';
                $html .='<span class="lbl"> '.$vo.'</span></label></div>';
            }
        }
        $html .= '</div></div>';
        return $html;
    }
}