<?php

namespace App\Http\Controllers;

use App\Users;
use App\UserInfo;
use App\Http\Controllers\UsersLoginController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserInfoController extends Controller
{
    private static $self;
    private $response;
    private $userMethod;
    private $userStash;
    /**
     * MenuController constructor.
     */
    public function __construct()
    {
        $this->response = $this->normalOutput();
        $this->userMethod = new UsersLoginController();
    }
    /**
     * 初始化
    */
    public static function instance()
    {
        if (!isset(self::$self)) {
            self::$self = new self();
        }
        return self::$self;
    }
    public function UserGet($request) {
        $user;
        // 防止重複取User資料
        if (empty($userStash)) {
            $user = $this->userMethod->UserGet($request);
            $userStash = $user;
        } else {
            $user = $userStash;
        }
        
        return $user;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $user = $this->UserGet($request);
        if (!$user) {
            echo 'error';
            exit();  
        }
        $Create=UserInfo::create([
            'account' => $user['name'],
            'email' => $user['email'],
            'lastName' => $user['name']
        ]);
    }
    /**
     * 用戶詳細資料(數據)
     *
     * @return \Illuminate\Http\Response
     */
    public function showData(Request $request, $enterUser = array())
    {
        if (empty($enterUser)) {
            $user = $this->UserGet($request);
        } else {
            $user = $enterUser;
        }
        
        $userInfo = array(
            'account' => $user['name'],
            'firstName' => '',
            'lastName' => '',
            'address' => '',
            'phone' => '',
            'email' => $user['email'],
        );

        $infoTable = UserInfo::where([
            'account' => $user['name'],
        ])->first();

        if ($infoTable) {
            $userInfo['firstName'] = $infoTable['firstName'];
            $userInfo['lastName'] = $infoTable['lastName'];
            $userInfo['address'] = $infoTable['address'];
            $userInfo['phone'] = $infoTable['phone'];
            $userInfo['email'] = $infoTable['email'];
        }

        return $userInfo;
    }
    /**
     * 用戶詳細資料
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        $data = $this->success($this->showData($request));

        return $data;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request)
    {
        $user = $this->UserGet($request);
        $data = $request->all();
        $validator = Validator::make($data, [
            'account' => ['required', 'string', 'min:4', 'max:15'],
            'email' => ['required', 'string', 'email', 'max:50'],
            'firstName'=> ['required', 'string', 'max:10'],
            'lastName' => ['required', 'string', 'max:20'],
            'address' => ['required', 'string', 'max:50'],
            'phone' => ['required', 'string', 'max:20'],
        ],[
            'email.required' => '請填寫郵件信箱',
            'email.max' => '郵件帳號：最多不超過50字',
            'account.required' => '帳號未填',
            'account.min' => '帳號：4~15字元',
            'account.max' => '帳號：4~15字元',
            'firstName.required' => '請填寫姓氏帳號：請輸入6～15字元',
            'firstName.max' => '最多填寫10個字',
            'lastName.required' => '請填名字',
            'lastName.max' => '最多填寫20個字',
            'address.required' => '請填寫收貨地址',
            'phone.required' => '請填寫手機號碼',
        ]);
        if ($validator->fails()) {
            $error = $validator->errors()->first();
            $this->response['code'] = 1039;
            $this->response['message'] = $error;
            return response()->json($this->response);
        }
        
        unset($data['captcha']);
        UserInfo::where([
            'account' => $user['name'],
        ])->update($data);

        if($user['email'] !== $data['email']) {
            Users::where('name',$user['name'])->update([
                'email'=> $data['email']
            ]);
        }

        $this->response['message'] = '修改成功';

        return response()->json($this->response);
    }
}
