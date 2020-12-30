<?php

use Illuminate\Http\Request;

Route::middleware('captchauth')->post('/haix', 'UsersController@store');    // 註冊
Route::get('/haix/flat', 'CaptchasController@store');   // 驗證碼
Route::get('/haix/userInit', 'UsersLoginController@UserInit'); // 初始資料
Route::middleware('captchauth')->post('/haix/login','UsersLoginController@UsersLogin'); // 登入

Route::group(['middleware' => ['haixauth:api']], function(){
    // 會員資料
    Route::get('/haix/userInfo', 'UserInfoController@show');  // 資料顯示
    Route::get('/haix/userInfo/add', 'UserInfoController@create');  // 建立
    Route::middleware('captchauth')->post('/haix/userInfo/edit', 'UserInfoController@edit');  // 編輯
    Route::get('/haix/out','UsersLogoutController@UsersLogout'); // 登出
});


// 後台用戶登入
Route::middleware('captchauth')->post('/admin/login','AdminUsersController@UsersLogin'); // 登入
Route::get('/admin/userInit', 'AdminUsersController@UserInit'); // 初始資料

// 後台登入驗證
Route::group(['middleware' => ['adminauth:api']], function(){
    // 後台操作
    Route::get('/admin/out','AdminUsersController@UsersLogout'); // 登出
    // 會員列表操作
    Route::get('/admin/userList', 'UsersController@show');  // 客端會員資料全部顯示
    Route::put('/admin/destroy/{id}', 'UsersController@destroy');  // 客端會員資料刪除
    Route::middleware('captchauth')->post('/admin/changePwd/', 'UsersController@updatePwd');  // 客端會員資料刪除
    // 商品列表操作
    Route::get('/admin/categories', 'CategoriesController@showAll');  // 後台取商品分類
    Route::post('/admin/goods/add','GoodsController@create'); // 新增商品
    Route::get('/admin/goods/list','GoodsController@show'); // 商品列表
    Route::put('/admin/goods/down/{id}', 'GoodsController@down');  // 商品下架
    Route::put('/admin/goods/del/{id}', 'GoodsController@destroy');  // 商品刪除
});
