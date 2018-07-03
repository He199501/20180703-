<?php
// +----------------------------------------------------------------------
// | YFCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015-2016 http://www.rainfer.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: rainfer <81818832@qq.com>
// +----------------------------------------------------------------------
namespace app\admin\controller;

use think\Db;
use app\admin\model\News as NewsModel;
use app\common\model\Menu as MenuModel;
use app\common\widget\Widget;
use app\common\model\Common as CommonModel;
use think\facade\Cache;

class News extends Base
{
    /**
     * 文章列表
     */
	public function newsIndex()
	{
		$keytype=input('keytype','title');
		$key=input('key','');
		$lang=input('lang','');
		$status=input('status','');
		$cid=input('cid','');
		$diyflag=input('diyflag','');
		//查询：时间格式过滤 获取格式 2015-11-12 - 2015-11-18
		$sldate=input('reservation','');
		$arr = explode(" - ",$sldate);
        $map=[];
        if(count($arr)==2){
            $arrdateone=strtotime($arr[0]);
            $arrdatetwo=strtotime($arr[1].' 23:55:55');
            $map[] =['a.create_time','between',[$arrdateone,$arrdatetwo]];
        }
		//map架构查询条件数组
		$map[]= ['is_back','=',0];
		if(!empty($key)){
			if($keytype=='title'){
				$map[]= ['a.title','like',"%".$key."%"];
			}elseif($keytype=='username'){
				$map[]= array('b.username','like',"%".$key."%");
			}else{
				$map[]= [$keytype,'=',$key];
			}
		}
		if ($status!=''){
			$map[]= array('a.status','=',$status);
		}
		if (!empty($lang)){
			$map[]= array('a.lang','=',$lang);
		}
        if(!config('lang_switch_on')){
            $map[]=['a.lang','=',$this->lang];
        }
		if ($cid){
			$ids=get_menu_byid($cid,1,2);
			$map[]= array('a.cid','in',implode(",", $ids));
		}
        if($diyflag){
		    $map[]=['','exp',Db::raw("FIND_IN_SET('$diyflag',flags)")];;
        }
		$news_model=new NewsModel;
		$news=$news_model->alias("a")->field('a.*,b.username,c.name')
				->join(config('database.prefix').'user b','a.author =b.id')
				->join(config('database.prefix').'menu c','a.cid =c.id')
				->where($map)->order('a.create_time desc')->paginate(config('paginate.list_rows'),false,['query'=>get_query()]);
        $page = $news->render();
        $page=preg_replace("(<a[^>]*page[=|/](\d+).+?>(.+?)<\/a>)","<a href='javascript:ajax_page($1);'>$2</a>",$page);
        $data=$news->items();
		//文章属性数据
        $common_model=new CommonModel();
        $diyflag_list=Cache::get('flags');
        if(!$diyflag_list){
            $diyflag_list=$common_model->setTable(config('database.prefix').'flags')->setPk('id')->column('name','value');
            Cache::set('flags',$diyflag_list);
        }
		//栏目数据
		$menu_text=menu_text($this->lang);
        //表格字段
        $fields=[
            ['title'=>'排序','field'=>'sort','type'=>'input'],
            ['title'=>'ID','field'=>'id'],
            ['title'=>'作者','field'=>'username'],
            ['title'=>'文章标题','field'=>'title'],
            ['title'=>'所属栏目','field'=>'name'],
            ['title'=>'状态','field'=>'status','type'=>'switch','url'=>url('newsState')],
            ['title'=>'发布时间','field'=>'create_time','type'=>'date']
        ];
        $pk='id';
        //右侧操作按钮
        $right_action=[
            'edit'=>['href'=>url('newsEdit'),'is_pop'=>1],
            'delete'=>url('newsDel')
        ];
        $order=url('newsOrder');
        $delall=url('newsAlldel');
        $search=[
            ['select','keytype','',['title'=>'按标题','author'=>'按发布人ID','username'=>'按发布人名'],$keytype,'','',['is_formgroup'=>false],'class'=>''],
            ['select','cid','',$menu_text,$cid,'','',['is_formgroup'=>false,'default'=>'按栏目'],'class'=>'ajax_change'],
            ['select','lang','',['zh-cn'=>'中文','en-us'=>'英语'],$lang,'','',['is_formgroup'=>false,'default'=>'按语言'],'ajax_change'],
            ['select','diyflag','',$diyflag_list,$diyflag,'','',['is_formgroup'=>false,'default'=>'按属性'],'ajax_change'],
            ['select','status','',['1'=>'已启用','0'=>'未启用'],$status,'','',['is_formgroup'=>false,'default'=>'按状态'],'ajax_change'],
            ['daterange','reservation','',$sldate,'',['is_formgroup'=>false,'placeholder'=>'点击选择日期范围'],'','height:30px;margin:auto 2px;'],
            ['text','key','',$key,'','','text',['placeholder'=>'输入需查询的关键字','is_formgroup'=>false],'search-query'],
            ['button','搜索',['class'=>'btn btn-purple btn-sm search-query ajax-search-form','type'=>'submit','icon_l'=>'ace-icon fa fa-search icon-on-right bigger-110']]
        ];
        $form=[
            'href'=>url('newsIndex'),
            'class'=>'form-search',
            'id'=>'list-filter'
        ];
        //实例化表单类
        $widget=new Widget();
		if(request()->isAjax()){
            return $widget
                ->form('table',$fields,$pk,$data,$right_action,$page,$order,$delall,1);
		}else{
            return $widget
                ->addToparea(['add'=>['href'=>url('newsAdd'),'is_pop'=>1]],[],$search,$form)
                ->addtable($fields,$pk,$data,$right_action,$page,$order,$delall)
                ->setButton()
                ->fetch();
		}		
	}
    /**
     * 添加显示
     */
	public function newsAdd()
	{
		$cid=input('cid',0,'intval');
        $menu_text=menu_text($this->lang);
        $common_model=new CommonModel();
        $diyflag_list=Cache::get('flags');
        if(!$diyflag_list){
            $diyflag_list=$common_model->setTable(config('database.prefix').'flags')->setPk('id')->column('name','value');
            Cache::set('flags',$diyflag_list);
        }
        $source=Cache::get('source');
        if(!$source){
            $source=$common_model->setTable(config('database.prefix').'source')->setPk('id')->column('name');
            Cache::set('source',$source);
        }
        $help='<label class="input_last">常用：';
        foreach ($source as $value){
            $help .='<a class="btn btn-minier btn-yellow" href="javascript:;" onclick="return souadd("'.$value.'");">'.$value.'</a>&nbsp;';
        }
        $help .='</label>';
        //实例化表单类
        $widget=new Widget();
        return $widget
            ->addSelect('cid','文章所属主栏目',$menu_text,$cid)
            ->addText('title','文章标题','','','required','text',['placeholder'=>'必填：文章标题'])
            ->addText('stitle','文章短标题','','','','text',['placeholder'=>'简短标题，建议6~12字数'])
            ->addCheckbox('flag[]','文章属性',$diyflag_list)
            ->addText('tags','标签','','多个以,隔开','','text',['placeholder'=>'标签'])
            ->addText('url','跳转地址','','正确格式：http(s):// 开头','','text',['placeholder'=>'跳转地址'])
            ->addText('keyword','文章关键字','','','','text',['placeholder'=>'输入文章关键字，以英文,逗号隔开'])
            ->addText('source','文章来源','YFCMF',$help,'','text',['id'=>'news_source'])
            ->addImage('img','封面图片上传','','上传前先用PS处理成等比例图片后上传，最后都统一比例')
            ->addImages('imgs','多图')
            ->addSwitch('status','是否启用',0)
            ->addText('sort','排序',50,'从小到大','','number')
            ->addDate('show_time','显示日期',date('Y-m-d'))
            ->addTextarea('scontent','文章简介','','已限制在100个字以内','',['maxlength'=>100,'autosize'=>1])
            ->addUeditor('content','文章主内容')
            ->setTrigger('flag[]',"j",'url',false)
            ->setUrl(url('newsSave'))
            ->setAjax('ajaxForm-noJump')
            ->fetch();
	}
    /**
     * 增加
     */
    public function newsSave()
    {
        //获取文章属性
        $flags=input('post.flag/a');
        $flag=array();
        if(!empty($flags)){
            foreach ($flags as $v){
                $flag[]=$v;
            }
        }
        $flagdata=implode(',',$flag);
        $cid=input('cid',0,'intval');
        $sl_data=array(
            'title'=>input('title'),
            'stitle'=>input('stitle',''),
            'cid'=>$cid,
            'author'=>session('hid'),
            'flags'=>$flagdata,
            'url'=>input('url',''),
            'keyword'=>input('keyword',''),
            'tags'=>input('tags',''),
            'source'=>input('source',''),
            'imgs'=>input('imgs',''),//多图路径
            'img'=>input('img',''),//封面图片路径
            'status'=>input('status',0),
            'scontent'=>input('scontent',''),
            'content'=>htmlspecialchars_decode(input('content')),
            'uid'=>session('admin_auth.uid'),
            'create_time'=>time(),
            'show_time'=>input('show_time',time(),'intval'),
            'sort'=>input('sort',50,'intval'),
        );
        //根据栏目id,获取语言
        $lang=MenuModel::where('id',$cid)->value('lang');
        $sl_data['lang']=$lang?:'zh-cn';
        $rst=NewsModel::create($sl_data);
        if($rst){
            $this->success('文章添加成功,返回列表页','newsIndex',['is_frame'=>1]);
        }else{
            $this->error('文章添加失败,返回列表页','newsIndex',['is_frame'=>1]);
        }
    }
    /**
     * 编辑显示
     */
	public function newsEdit()
	{
        $id = input('id');
        if (empty($id)){
            $this->error('参数错误','newsIndex');
        }
        $news_list=NewsModel::get($id);
        $common_model=new CommonModel();
        $diyflag=Cache::get('flags');
        if(!$diyflag){
            $diyflag=$common_model->setTable(config('database.prefix').'flags')->setPk('id')->column('name','value');
            Cache::set('flags',$diyflag);
        }
        $source=Cache::get('source');
        if(!$source){
            $source=$common_model->setTable(config('database.prefix').'source')->setPk('id')->column('name');
            Cache::set('source',$source);
        }
        $help='<label class="input_last">常用：';
        foreach ($source as $value){
            $help .='<a class="btn btn-minier btn-yellow" href="javascript:;" onclick="return souadd("'.$value.'");">'.$value.'</a>&nbsp;';
        }
        $help .='</label>';
        $menu_text=menu_text($this->lang);
        //实例化表单类
        $widget=new Widget();
        return $widget
            ->addText('id','',$id,'','','hidden')
            ->addSelect('cid','文章所属主栏目',$menu_text,$news_list['cid'])
            ->addText('title','文章标题',$news_list['title'],'','required','text',['placeholder'=>'必填：文章标题'])
            ->addText('stitle','文章短标题',$news_list['stitle'],'','','text',['placeholder'=>'简短标题，建议6~12字数'])
            ->addCheckbox('flag[]','文章属性',$diyflag,$news_list['flags'])
            ->addText('tags','标签',$news_list['tags'],'多个以,隔开','','text',['placeholder'=>'标签'])
            ->addText('url','跳转地址',$news_list['url'],'正确格式：http(s):// 开头','','text',['placeholder'=>'跳转地址'])
            ->addText('keyword','文章关键字',$news_list['keyword'],'','','text',['placeholder'=>'输入文章关键字，以英文,逗号隔开'])
            ->addText('source','文章来源',$news_list['source'],$help,'','text',['id'=>'news_source'])
            ->addImage('img','封面图片上传',$news_list['img'],'上传前先用PS处理成等比例图片后上传，最后都统一比例')
            ->addImages('imgs','多图',$news_list['imgs'])
            ->addSwitch('status','是否启用',$news_list['status'])
            ->addText('sort','排序',$news_list['sort'],'从小到大','','number')
            ->addDate('show_time','显示日期',date('Y-m-d',$news_list['show_time']))
            ->addTextarea('scontent','文章简介',$news_list['scontent'],'已限制在100个字以内','',['maxlength'=>100,'autosize'=>1])
            ->addUeditor('content','文章主内容',$news_list['content'])
            ->setTrigger('flag[]',"j",'url',false)
            ->setUrl(url('newsUpdate'))
            ->setAjax('ajaxForm-noJump')
            ->fetch();
	}
    /**
     * 文章更新
     */
    public function newsUpdate()
    {
        //获取文章属性
        $flags=input('post.flag/a');
        $flag=array();
        if(!empty($flags)){
            foreach ($flags as $v){
                $flag[]=$v;
            }
        }
        $flagdata=implode(',',$flag);
        $showtime=input('show_time','');
        $showtime=($showtime=='')?time():strtotime($showtime);
        $cid=input('cid',0,'intval');
        $sl_data=array(
            'id'=>input('id'),
            'title'=>input('title'),
            'stitle'=>input('stitle',''),
            'cid'=>$cid,
            'flags'=>$flagdata,
            'url'=>input('url',''),
            'keyword'=>input('keyword',''),
            'tags'=>input('tags',''),
            'img'=>input('img',''),
            'imgs'=>input('imgs',''),
            'source'=>input('source',''),
            'status'=>input('status',0),
            'scontent'=>input('scontent',''),
            'content'=>htmlspecialchars_decode(input('content')),
            'sort'=>input('sort',50,'intval'),
            'update_time'=>time(),
            'show_time'=>$showtime
        );
        //根据栏目id,获取语言
        $lang=MenuModel::where('id',$cid)->value('lang');
        $sl_data['lang']=$lang?:'zh-cn';
        $rst=NewsModel::update($sl_data);
        if($rst!==false){
            $this->success('文章修改成功','newsIndex',['is_frame'=>1]);
        }else{
            $this->error('文章修改失败','newsIndex',['is_frame'=>1]);
        }
    }
    /**
     * 文章排序
     */
	public function newsOrder()
	{
		if (!request()->isAjax()){
			$this->error('提交方式不正确','newsIndex');
		}else{
			$list=[];
			foreach (input('post.') as $id => $sort){
				$list[]=['id'=>$id,'sort'=>$sort];
			}
			$news_model=new NewsModel;
			$news_model->saveAll($list);
			$this->success('排序更新成功','newsIndex');
		}
	}
    /**
     * 删除至回收站(单个)
     */
	public function newsDel()
	{
		$news_model=new NewsModel;
		$rst=$news_model->where('id',input('id'))->setField('is_back',1);
		if($rst!==false){
			$this->success('文章已转入回收站','newsIndex');
		}else{
			$this -> error("删除文章失败！",'newsIndex');
		}
	}
    /**
     * 删除至回收站(全选)
     */
	public function newsAlldel()
	{
		$ids = input('ids/a');
		if(empty($ids)){
			$this -> error("请选择删除文章",'newsIndex');//判断是否选择了文章ID
		}
		if(is_array($ids)){//判断获取文章ID的形式是否数组
			$where = 'id in('.implode(',',$ids).')';
		}else{
			$where = 'id='.$ids;
		}
		$news_model=new NewsModel;
		$rst=$news_model->where($where)->setField('is_back',1);//转入回收站
		if($rst!==false){
			$this->success("成功把文章移至回收站！",'newsIndex');
		}else{
			$this -> error("删除文章失败！",'newsIndex');
		}
	}
    /**
     * 文章审核/取消审核
     */
	public function newsState()
	{
        $id=input('id',0,'intval');
        if(!$id) $this->error('文章不存在','newsIndex');
        $status=NewsModel::where('id',$id)->value('status');
        $status=$status?0:1;
        NewsModel::where('id',$id)->setField('status',$status);
        $this->success($status?'启用':'禁用',null,['result'=>$status]);
	}
    /**
     * 回收站列表
     */
	public function backIndex()
	{
        $keytype=input('keytype','title');
        $key=input('key','');
        $lang=input('lang','');
        $status=input('status','');
        $cid=input('cid','');
        $diyflag=input('diyflag','');
        //查询：时间格式过滤 获取格式 2015-11-12 - 2015-11-18
        $sldate=input('reservation','');
        $arr = explode(" - ",$sldate);
        $map=[];
        if(count($arr)==2){
            $arrdateone=strtotime($arr[0]);
            $arrdatetwo=strtotime($arr[1].' 23:55:55');
            $map[] =['a.create_time','between',[$arrdateone,$arrdatetwo]];
        }
        //map架构查询条件数组
        $map[]= ['is_back','=',1];
        if(!empty($key)){
            if($keytype=='title'){
                $map[]= ['a.title','like',"%".$key."%"];
            }elseif($keytype=='username'){
                $map[]= array('b.username','like',"%".$key."%");
            }else{
                $map[]= [$keytype,'=',$key];
            }
        }
        if ($status!=''){
            $map[]= array('a.status','=',$status);
        }
        if (!empty($lang)){
            $map[]= array('a.lang','=',$lang);
        }
        if(!config('lang_switch_on')){
            $map[]=['a.lang','=',$this->lang];
        }
        if ($cid){
            $ids=get_menu_byid($cid,1,2);
            $map[]= array('a.cid','in',implode(",", $ids));
        }
        if($diyflag){
            $map[]=['','exp',Db::raw("FIND_IN_SET('$diyflag',flags)")];;
        }
        $news_model=new NewsModel;
        $news=$news_model->alias("a")->field('a.*,b.username,c.name')
            ->join(config('database.prefix').'user b','a.author =b.id')
            ->join(config('database.prefix').'menu c','a.cid =c.id')
            ->where($map)->order('a.create_time desc')->paginate(config('paginate.list_rows'),false,['query'=>get_query()]);
        $page = $news->render();
        $page=preg_replace("(<a[^>]*page[=|/](\d+).+?>(.+?)<\/a>)","<a href='javascript:ajax_page($1);'>$2</a>",$page);
        $data=$news->items();
        foreach ($data as &$v){
            $v['back_url']=url('backState',['id'=>$v['id']]);
        }
        //文章属性数据
        $common_model=new CommonModel();
        $diyflag_list=Cache::get('flags');
        if(!$diyflag_list){
            $diyflag_list=$common_model->setTable(config('database.prefix').'flags')->setPk('id')->column('name','value');
            Cache::set('flags',$diyflag_list);
        }
        //栏目数据
        $menu_text=menu_text($this->lang);
        //表格字段
        $fields=[
            ['title'=>'ID','field'=>'id'],
            ['title'=>'作者','field'=>'username'],
            ['title'=>'文章标题','field'=>'title'],
            ['title'=>'所属栏目','field'=>'name'],
            ['title'=>'状态','field'=>'status','type'=>'switch','url'=>url('newsState')],
            ['title'=>'发布时间','field'=>'create_time','type'=>'date']
        ];
        $pk='id';
        //右侧操作按钮
        $right_action=[
            'back'=>['field'=>'back_url','title'=>'还原','extra_attr'=>'data-info="你确定要还原文章到文章列表吗？"','class'=>'red confirm-rst-url-btn','icon'=>'fa fa-check'],
            'delete'=>url('backDel')
        ];
        $delall=url('backAlldel');
        $search=[
            ['select','keytype','',['title'=>'按标题','author'=>'按发布人ID','username'=>'按发布人名'],$keytype,'','',['is_formgroup'=>false],'class'=>''],
            ['select','cid','',$menu_text,$cid,'','',['is_formgroup'=>false,'default'=>'按栏目'],'class'=>'ajax_change'],
            ['select','lang','',['zh-cn'=>'中文','en-us'=>'英语'],$lang,'','',['is_formgroup'=>false,'default'=>'按语言'],'ajax_change'],
            ['select','diyflag','',$diyflag_list,$diyflag,'','',['is_formgroup'=>false,'default'=>'按属性'],'ajax_change'],
            ['select','status','',['1'=>'已启用','0'=>'未启用'],$status,'','',['is_formgroup'=>false,'default'=>'按状态'],'ajax_change'],
            ['daterange','reservation','',$sldate,'',['is_formgroup'=>false,'placeholder'=>'点击选择日期范围'],'','height:30px;margin:auto 2px;'],
            ['text','key','',$key,'','','text',['placeholder'=>'输入需查询的关键字','is_formgroup'=>false],'search-query'],
            ['button','搜索',['class'=>'btn btn-purple btn-sm search-query ajax-search-form','type'=>'submit','icon_l'=>'ace-icon fa fa-search icon-on-right bigger-110']]
        ];
        $form=[
            'href'=>url('backIndex'),
            'class'=>'form-search',
            'id'=>'list-filter'
        ];
        //实例化表单类
        $widget=new Widget();
        if(request()->isAjax()){
            return $widget
                ->form('table',$fields,$pk,$data,$right_action,$page,'',$delall,1);
        }else{
            return $widget
                ->addToparea([],[],$search,$form)
                ->addtable($fields,$pk,$data,$right_action,$page,'',$delall)
                ->setButton()
                ->fetch();
        }
	}
    /**
     * 还原文章
     */
	public function backState()
	{
		$news_model=new NewsModel;
		$rst=$news_model->where('id',input('id'))->setField('is_back',0);//转入正常
		if($rst!==false){
			$this->success('文章还原成功','backIndex');
		}else{
			$this -> error("文章还原失败！",'backIndex');
		}
	}
    /**
     * 彻底删除(单个)
     */
	public function backDel()
	{
		$id=input('id');
		$news_model=new NewsModel;
		if (empty($id)){
			$this->error('参数错误','backIndex');
		}else{
			$rst=$news_model->where('id',$id)->delete();
			if($rst!==false){
				$this->success('文章彻底删除成功','backIndex');
			}else{
				$this -> error("文章彻底删除失败！",'backIndex');
			}
		}
	}
    /**
     * 彻底删除(全选)
     */
	public function backAlldel()
	{
		$ids = input('ids/a');
		if(empty($ids)){
			$this -> error("请选择删除文章",'backIndex');//判断是否选择了文章ID
		}
		if(is_array($ids)){//判断获取文章ID的形式是否数组
			$where = 'id in('.implode(',',$ids).')';
		}else{
			$where = 'id='.$ids;
		}
		$news_model=new NewsModel;
		$rst=$news_model->where($where)->delete();
		if($rst!==false){
			$this->success("成功把文章删除，不可还原！",'backIndex');
		}else{
			$this -> error("文章彻底删除失败！",'backIndex');
		}
	}
}