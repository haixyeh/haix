<?php

namespace App\Http\Controllers;

use App\AdminUser;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cookie;

class AdminUsersController extends Controller
{
    private $response;
    private $users;
    /**
     * MenuController constructor.
     */
    public function __construct()
    {
        $this->response = $this->normalOutput();
    }

    // 選擇登入機制 （帳號 or email）
    public function UserTypeLoginFind ($data)
    {
        $users = array([]);
        if (array_key_exists('account', $data)) {
            $users = AdminUser::where([
                'name' => $data['account'],
                'password' => $data['password']
                ]);
        }

        if (array_key_exists('email', $data)) {
            $users = AdminUser::where([
                'email' => $data['email'],
                'password' => $data['password']
                ]);
        }

        // 無搜尋到帳號
        if (empty($users->first())) {
            return null;
        }

        return $users->first();
    }

    // 用戶登入
    public function UsersLogin (Request $request)
    {
        $data = $request->all();
        $users = $this->UserTypeLoginFind($data);
        
        $apiToken = Str::random(10);
        
        if (empty($users)) {
            $this->response['code'] = 1018;
            $this->response['message'] = "帳號/郵件信箱 or 密碼 輸入錯誤";
        }
        if ($users && $users->update(['api_token'=>$apiToken])) {
            $this->response['message'] = "歡迎購物 $users->name";
        }

        return response()->json($this->response)->cookie('SESSION', $apiToken, time() + (86400 * 30), "/");
    }
    // 獲取使用者共用
    public function UserGet (Request $request)
    {
        $session = request()->cookie('SESSION');
        $users = array([]);
        if ($session) {
            $users = AdminUser::where([
                'api_token' => $session,
            ])->first();
            return $users;
        }

        return response()->json($this->response);
    }
    // init 基本資料
    public function UserInit (Request $request)
    {
        $session = request()->cookie('SESSION');
        $reslut = array(
            'is_login'=> 'N',
            'user' => '',
        );
        if ($session) {
            $users = $this->UserGet($request);
            $reslut['is_login'] = 'Y';
            $reslut['user'] = $users->name;
        }

        $this->response['data'] = $reslut;

        return response()->json($this->response);
    }
    // 用戶登出
    public function UsersLogout (Request $request)
    {
        $cookie = Cookie::forget('SESSION');
        $this->response['code'] = 1002;
        $this->response['message'] = "已登出";

        return response()->json($this->response)->withCookie($cookie);
    }
}
