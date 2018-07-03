<?php

namespace app\common\widget\form;


class FileForm
{
    protected $default = [
        'name' => '',
        'title' => '',
        'value' => '',
        'attr'=>[
            'size'=>0,
            'ext'=>'',
            'url'=>'',
            'label_class'=>'col-sm-3',//标签class
            'div_class'=>'col-sm-9'//input上层div的class
        ],
        'help_text'=>'',//帮助文本
        'extra_class' => 'col-xs-10 col-sm-5'
    ];

    /**
     * 渲染
     *
     * @param string $name
     * @param string $title 标题
     * @param string $default
     * @param string $help_text
     * @param array $attr 属性，
     * @param string $extra_class
     * @return string
     */
    public function fetch($name='',$title='',$default='',$help_text='',$attr=[],$extra_class='col-xs-10 col-sm-5')
    {
        $data=[
            'name' => $name,//id
            'title' => $title,//标签
            'value'=>$default,
            'attr'=>$attr,
            'help_text'=>$help_text,
            'extra_class'=>$extra_class
        ];
        $data['attr']=isset($data['attr'])?array_merge($this->default['attr'], $data['attr']):$this->default['attr'];
        $data = array_merge($this->default, $data);
        $html = '<div class="form-group">';
        $html .= '<label class="'.$data['attr']['label_class'].' control-label no-padding-right"> '.$data['title'].' </label>';
        $html .= '<div class="'.$data['attr']['div_class'].'">';
        $html .= '<div class="upload-file no-padding '.$data['extra_class'].'">';
        $html .='<ul class="list-group uploader-list" id="file_list_'.$data['name'].'">';
        if($data['value']){
            $html .='<li class="list-group-item file-item">';
            $html .='<i class="fa fa-file"></i>';
            $html .=$data['value'];
            $html .='[<a href="" class="download-file">下载</a>] [<a href="javascript:void(0);" class="remove-file">删除</a>]</li>';
        }
        $html .='</ul>';
        $html .='<input type="hidden" name="'.$data['name'].'" data-multiple="false" data-url="'.$data['attr']['url'].'" data-size="'.$data['attr']['size'].'" data-ext="'.$data['attr']['ext'].'" id="'.$data['name'].'" value="'.$data['value'].'">';
        $html .='<div id="picker_'.$data['name'].'">上传单个文件</div></div>';
        if($data['help_text']){
            $html .='<span class="middle help-text">'.$data['help_text'].'</span>';
        }
        $html .='</div></div>';
        return $html;
    }
}