<?php
use think\facade\Route;

// 定义插件路由
Route::any('addons/execute/:route', "\\app\\common\\controller\\Base@execute");

Route::rule(config('adminpath').'/:c/:a','admin/:c/:a');
Route::rule(config('adminpath').'/:c','admin/:c/index');
Route::rule(config('adminpath'),'admin/index/index');
//阻止admin
/*Route::rule('admin',function(){
    return '404 Not Found';
});*/
return [
    // 下载
    'download' => 'index/index/download',
];