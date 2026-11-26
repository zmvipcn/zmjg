<?php
use think\facade\Route;
use app\middleware\Auth;
use app\middleware\AdminAuth;

// 登录与退出（公开）
Route::get('login', 'Auth/login');
Route::post('login', 'Auth/doLogin');
Route::get('logout', 'Auth/logout');

// 前台（需登录）
Route::group(function () {
    Route::get('/', 'Index/index');

    // 项目
    Route::get('project/current', 'Project/current');
    Route::post('project/create', 'Project/create');
    Route::post('project/update/:id', 'Project/update');
    Route::post('project/default/:id', 'Project/setDefault');
    Route::post('project/end/:id', 'Project/end');
    Route::get('project/history', 'Project/history');
    Route::get('project/view/:id', 'Project/view');
    Route::post('project/wage/:id', 'Project/updateWage');

    // 工天记录
    Route::post('record/save/:projectId', 'WorkRecord/save');
    Route::post('record/delete/:id', 'WorkRecord/delete');

    // 个人中心 / 设置
    Route::get('profile', 'Profile/index');
    Route::get('setting', 'Setting/index');
    Route::post('setting/profile', 'Setting/updateProfile');
    Route::post('setting/password', 'Setting/updatePassword');
    Route::post('setting/avatar', 'Setting/avatar');
})->middleware(Auth::class);

// 管理后台（需登录 + 管理员角色）
Route::group('admin', function () {
    Route::get('/', 'admin.Index/index');
    Route::redirect('front', '/');

    // 用户管理
    Route::get('user', 'admin.User/index');
    Route::post('user/create', 'admin.User/create');
    Route::post('user/delete/:id', 'admin.User/delete');
    Route::post('user/role/:id', 'admin.User/setRole');

    // 文件管理
    Route::get('file', 'admin.File/index');
    Route::post('file/save', 'admin.File/save');
    Route::post('file/delete/:id', 'admin.File/delete');

    // 系统配置
    Route::get('config', 'admin.Config/index');
    Route::post('config/save', 'admin.Config/save');

    // 帮助
    Route::get('help', 'admin.Help/index');
    Route::get('help/edit/:id?', 'admin.Help/edit');
    Route::post('help/save', 'admin.Help/save');
    Route::post('help/delete/:id', 'admin.Help/delete');
})->middleware([Auth::class, AdminAuth::class]);
