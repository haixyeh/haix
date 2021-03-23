<?php

use Illuminate\Http\Request;

Route::middleware('captchauth')->post('/haix', 'UsersController@store');    // 註冊
Route::get('/haix/flat', 'CaptchasController@store');   // 驗證碼
Route::get('/haix/userInit', 'UsersLoginController@UserInit'); // 初始資料
Route::middleware('captchauth')->post('/haix/login','UsersLoginController@UsersLogin'); // 登入
Route::get('/haix/goods/list','GoodsController@mapCategories'); // 商品列表
Route::get('/haix/goods/single/{id}','GoodsController@single'); // 查看單一商品
Route::post('/haix/getPwd', 'UsersController@getPwd'); // 取得密碼 - 寄送至電子郵件
Route::post('/haix/changePwd/', 'UsersController@fixUpdatePwd');  // 客端忘記密碼修改
Route::post('/haix/contact', 'UserInfoController@contact');  // 會員聯絡我們

Route::group(['middleware' => ['haixauth:api']], function(){
    Route::get('/haix/userInfo', 'UserInfoController@show');  // 會員資料顯示
    Route::get('/haix/userData', 'UsersController@userDataGet');  // 會員資料取得
    Route::middleware('captchauth')->post('/haix/userInfo/edit', 'UserInfoController@edit');  // 編輯會員資料
    Route::get('/haix/memLevel', 'LevelController@memLevel');  // 會員等級
    Route::get('/haix/out','UsersLogoutController@UsersLogout'); // 會員登出
    Route::post('/haix/goods/car/{id}', 'GoodsController@addShopCar');  // 加入購物車
    Route::get('/haix/goods/car/show', 'GoodsController@showShopCar');  // 查看購物車
    Route::put('/haix/goods/car/{id}', 'GoodsController@delSingleShopCar');  // 刪除購物車項目
    Route::post('/haix/goods/buy', 'OrdersController@checkout'); // 結帳：建立訂單
    Route::get('/haix/goods/order/list','OrdersController@showGuest'); // 客戶訂單號列表
    Route::put('/haix/goods/order/{id}','OrdersController@cancelOrder'); // 取消訂單
    Route::post('/haix/goods/order/fix/goods','OrdersController@fixGoods'); // 貨品修改
    Route::post('/haix/goods/order/reback','RebackController@apply'); // 退貨申請
    Route::get('/haix/goods/reback/{orderNumber}','RebackController@showSingle'); // 退貨申請
    Route::get('/haix/getMessage', 'UsersController@getMessage'); // 獲得訊息
    Route::put('/haix/message/{id}', 'UsersController@delMsg'); // 刪除訊息
    // Route::get('/haix/userInfo/add', 'UserInfoController@create');  // 會員註冊
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
    Route::post('/admin/user/level', 'UsersController@updateLevel');  // 編輯會員等級
    Route::middleware('captchauth')->post('/admin/changePwd/', 'UsersController@updatePwd');  // 客端會員密碼修改
    // 商品列表操作
    Route::get('/admin/categories', 'CategoriesController@showAll');  // 後台取商品分類
    Route::post('/admin/goods/add','GoodsController@create'); // 新增商品
    Route::post('/admin/goods/edit/{id}','GoodsController@create'); // 編輯商品
    Route::get('/admin/goods/list','GoodsController@showAllList'); // 商品列表
    Route::put('/admin/goods/down/{id}', 'GoodsController@down');  // 商品下架
    Route::put('/admin/goods/del/{id}', 'GoodsController@destroy');  // 商品刪除
    Route::get('/admin/goods/single/{id}','GoodsController@single'); // 查看單一商品
    // 等級管理
    Route::post('/admin/goods/level/add','LevelController@addLevel'); // 新增等級設定
    Route::post('/admin/goods/level/edit','LevelController@edit'); // 新增等級設定
    Route::get('/admin/goods/level/list','LevelController@show'); // 新增等級設定
    Route::put('/admin/goods/level/{id}','LevelController@destroy'); // 刪除最後等級
    // 訂單操作列表
    Route::get('/admin/goods/order/list','OrdersController@show'); // 查看訂單號列表
    Route::put('/admin/goods/order/{id}','OrdersController@cancelOrder')->name("hospital"); // 取消訂單
    Route::post('/admin/goods/order/status','OrdersController@orderSatus'); // 訂單處理狀態
    Route::post('/admin/goods/order/memoFix','OrdersController@memoFix'); // 備註狀態處理
    // 退貨管理
    Route::get('/admin/goods/order/back/list','OrdersController@showBack'); // 查看訂單號列表
    Route::get('/admin/goods/order/apply/list','RebackController@show'); // 新增等級設定
    Route::post('/admin/goods/order/apply/cross','RebackController@cross'); // 成功 - 退貨申請
    Route::post('/admin/goods/order/apply/cancel','RebackController@cancel'); // 取消 - 退貨申請
    Route::post('/admin/goods/order/back/finish','RebackController@finish'); // 取消 - 退貨申請
});
