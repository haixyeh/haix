<?php

namespace App\Http\Controllers;

use App\Users;
use Hash;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use RedisServer;


class UsersLoginController extends Controller
{
    private $response;
    private $users;
    private $timeNow;
    /**
     * MenuController constructor.
     */
    public function __construct()
    {
        $this->response = $this->normalOutput();
        $this->timeNow = date("Y-m-d H:i:s" , mktime(date('H')+8, date('i'), date('s'), date('m'), date('d'), date('Y')));
    }

    // 選擇登入機制 （帳號 or email）
    public function UserTypeLoginFind ($data)
    {
        $users = array([]);
        $password = Hash::make($data['password']);

        if (array_key_exists('account', $data)) {
            try {
                $queryStatus = true;
                $users = Users::where([
                    'name' => $data['account'],
                    ])->having('isDel', 'N');
            } catch (\Throwable $th) {
                $queryStatus = false;
            }
        }

        if (array_key_exists('email', $data)) {
            try {
                $queryStatus = true;
                $users = Users::where([
                    'email' => $data['email']
                    ])->having('isDel', 'N');
            } catch (\Throwable $th) {
                $queryStatus = false;
            }
        }

        // 無搜尋到帳號
        if (empty($users->first())) {
            return null;
        }

        $dbpassword = $users->first()->password;
        $booleanValue = Hash::check($data['password'], $dbpassword);

        // Hash 確認密碼是否符合
        if (!$booleanValue) {
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
            $users = Users::where([
                'api_token' => $session,
            ]);
            return $users->first();
        }
        if (empty($users->first())) {
            $this->response['code'] = 1017;
            $this->response['message'] = "找不到此帳號";
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
            'menu' => array(),
        );
        if ($session) {
            $redis = RedisServer::connection();
            $users = $this->UserGet($request);

            if (!$users) {
                $reslut['is_login'] = 'N';
            }
            if ($users) {
                $userId = $users->id;
                $reslut['is_login'] = 'Y';
                $reslut['coupon'] = $users->coupon;
                $reslut['user'] = $users->name;
                $reslut['isSeeMsg'] = $redis->get('user'. $users->id . 'seeMsg');
            }
        }
        $reslut['menu'] = array(
            array(
                'link' => '/web',
                'name' => 'home',
                'open' => 'Y'
            ),
            array(
                'link' => '/web/member',
                'name' => 'member',
                'open' => $reslut['is_login']
            ),
            array(
                'link' => '/web/order',
                'name' => 'order',
                'open' => $reslut['is_login']
            ),
            array(
                'link' => '/web/contact',
                'name' => 'contact',
                'open' => 'Y'
            ),
        );
        $this->response['data'] = $reslut;
        RedisServer::set('name', json_encode($reslut));

        return response()->json($this->response);
    }
}
