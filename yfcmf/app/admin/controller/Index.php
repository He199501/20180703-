<?php

namespace app\admin\controller;

use think\helper\Time;
use app\common\model\News as NewsModel;
use app\common\model\User as UserModel;
use app\common\model\Common as CommonModel;
use think\facade\Cache;

class Index extends Base
{
    public function index()
    {
        $news_model=new NewsModel;
        $user_model=new UserModel;
        $comments_model=new CommonModel();
        $comments_model=$comments_model->setTable(config('database.prefix').'comments')->setPk('id');
        $guestbook_model=new CommonModel();
        $guestbook_model=$guestbook_model->setTable(config('database.prefix').'guestbook')->setPk('id');
        //热门文章排行
        $news_list=$news_model->where('lang',$this->lang)->order('hits desc')->limit(0,10)->select();
        $this->assign('news_list',$news_list);
        //总文章数
        $news_count=$news_model->count();
        $this->assign('news_count',$news_count);
        //总会员数
        $user_count=$user_model->count();
        $this->assign('user_count',$user_count);
        //总留言数
        $sugs_count=$guestbook_model->count();
        $this->assign('sugs_count',$sugs_count);
        //总评论数
        $coms_count=$comments_model->count();
        $this->assign('coms_count',$coms_count);

        //日期时间戳
        list($start_t, $end_t) = Time::today();
        list($start_y, $end_y) = Time::yesterday();

        //今日发表文章数
        $tonews_count=$news_model->whereTime('create_time', 'between', [$start_t, $end_t])->count();
        $this->assign('tonews_count',$tonews_count);

        //昨日文章数
        $ztnews_count=$news_model->whereTime('create_time', 'between', [$start_y, $end_y])->count();
        $this->assign('ztnews_count',$ztnews_count);
        //今日提升比
        $difday=($ztnews_count>0)?($tonews_count-$ztnews_count)/$ztnews_count*100:0;
        $this->assign('difday',$difday);

        //今日增加会员
        $touser_count=$user_model->whereTime('create_time', 'between', [$start_t, $end_t])->count();
        $this->assign('touser_count',$touser_count);
        //昨日会员数
        $ztuser_count=$user_model->whereTime('create_time', 'between', [$start_y, $end_y])->count();
        $this->assign('ztuser_count',$ztuser_count);
        //今日提升比
        $difday_m=($ztuser_count>0)?($touser_count-$ztuser_count)/$ztuser_count*100:0;
        $this->assign('difday_m',$difday_m);

        //今日留言
        $tosugs_count=$guestbook_model->whereTime('create_time', 'between', [$start_t, $end_t])->count();
        $this->assign('tosugs_count',$tosugs_count);
        //昨日留言
        $ztsugs_count=$guestbook_model->whereTime('create_time', 'between', [$start_y, $end_y])->count();
        $this->assign('ztsugs_count',$ztsugs_count);
        //今日提升比
        $difday_s=($ztsugs_count>0)?($tosugs_count-$ztsugs_count)/$ztsugs_count*100:0;
        $this->assign('difday_s',$difday_s);

        //今日评论
        $tocoms_count=$comments_model->whereTime('create_time', 'between', [$start_t, $end_t])->count();
        $this->assign('tocoms_count',$tocoms_count);
        //昨日评论
        $ztcoms_count=$comments_model->whereTime('create_time', 'between', [$start_y, $end_y])->count();
        $this->assign('ztcoms_count',$ztcoms_count);
        //今日提升比
        $difday_c=($ztcoms_count>0)?($tocoms_count-$ztcoms_count)/$ztcoms_count*100:0;
        $this->assign('difday_c',$difday_c);

        return $this->fetch();
    }
    /**
     * 后台多语言切换
     */
    public function lang()
    {
        if (!request()->isAjax()){
            $this->error('提交方式不正确');
        }else{
            $lang=input('lang_s');
            session('login_http_referer',$_SERVER["HTTP_REFERER"]);
            switch ($lang) {
                case 'cn':
                    cookie('think_var', 'zh-cn');
                    break;
                case 'en':
                    cookie('think_var', 'en-us');
                    break;
                //其它语言
                default:
                    cookie('think_var', 'zh-cn');
            }
            Cache::clear();
            $this->success('切换成功',session('login_http_referer'));
        }
    }
    /*
     * 清理缓存
     */
    public function clear()
    {
        Cache::clear();
        $this->success ('清理缓存成功');
    }
}