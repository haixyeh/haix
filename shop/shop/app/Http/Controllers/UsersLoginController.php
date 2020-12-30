<?php

namespace App\Http\Controllers;

use App\Users;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class UsersLoginController extends Controller
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
            $users = Users::where([
                'name' => $data['account'],
                'password' => $data['password']
                ])->first();
        }

        if (array_key_exists('email', $data)) {
            $users = Users::where([
                'email' => $data['email'],
                'password' => $data['password']
                ])->first();
        }

        return $users;
    }

    // 用戶登入
    public function UsersLogin (Request $request)
    {
        $data = $request->all();
        $users = $this->UserTypeLoginFind($data);
        
        $apiToken = Str::random(10);
        
        if (!$users) {
            $this->response['code'] = 1006;
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
            $users = Users::where([
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
            'menu' => array()
        );
        if ($session) {
            $users = $this->UserGet($request);
            if (!$users) {
                $reslut['is_login'] = 'N';
            }
            if ($users) {
                $reslut['is_login'] = 'Y';
                $reslut['user'] = $users->name;
            }
        }
        $reslut['menu'] = array(
            array(
                'link' => '/web',
                'name' => 'home',
                'open' => 'Y'
            ),
            array(
                'link' => '/web/shopping',
                'name' => 'shopping',
                'open' => 'Y'
            ),
            array(
                'link' => '/web/member',
                'name' => 'member',
                'open' => $reslut['is_login']
            ),
        );
        $this->response['data'] = $reslut;

        return response()->json($this->response);
    }

}