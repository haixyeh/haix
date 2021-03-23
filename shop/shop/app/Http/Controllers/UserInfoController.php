<?php

namespace App\Http\Controllers;

use App\Users;
use App\UserInfo;
use App\Http\Controllers\UsersLoginController;
use App\Http\Controllers\EmailController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserInfoController extends Controller
{
    private static $self;
    private $response;
    private $userMethod;
    private $userStash;
    private $adminEmail = 'haixshop@gmail.com'; // 管理者Email
    /**
     * MenuController constructor.
     */
    public function __construct()
    {
        $this->response = $this->normalOutput();
        $this->emailEvent = new EmailController;
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
    // 獲取使用者資訊
    public function UserGet($request) {
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
            'firstName'=> ['required', 'string', 'max:10', 'regex:/^[^0-9,:;!@#$%^&*?<>()+=`|[\]{}\\".~\-\0]*$/'],
            'lastName' => ['required', 'string', 'max:20', 'regex:/^[^0-9,:;!@#$%^&*?<>()+=`|[\]{}\\".~\-\0]*$/'],
            'address' => ['required', 'string', 'max:50', 'regex:/^[^0-9,:;!@#$%^&*?<>()+=`|[\]{}\\".~\-\0]*$/'],
            'phone' => ['required', 'string', 'min:3', 'max:30', 'regex:/^[0-9+][0-9]*/'],
        ],[
            'email.required' => '請填寫郵件信箱',
            'email.max' => '郵件帳號：最多不超過50字',
            'account.required' => '帳號未填',
            'account.min' => '帳號：4~15字元',
            'account.max' => '帳號：4~15字元',
            'firstName.required' => '請填寫姓氏',
            'firstName.max' => '最多填寫10個字',
            'firstName.regex' => '姓氏：不可填寫特殊符號',
            'lastName.required' => '請填名字',
            'lastName.max' => '最多填寫20個字',
            'lastName.regex' => '名字：不可填寫特殊符號',
            'address.required' => '請填寫收貨地址',
            'address.regex' => '地址：不可填寫特殊符號',
            'phone.required' => '請填寫手機號碼',
            'phone.min' => '電話：請輸入3～30字元',
            'phone.max' => '電話：請輸入3～30字元',
            'phone.regex' => '電話：僅可輸入數字以及字首+號',
        ]);

        if ($validator->fails()) {
            $error = $validator->errors()->first();
            $this->response['code'] = 1039;
            $this->response['message'] = $error;
            return response()->json($this->response);
        }
        if ($user['name'] !== $data['account']) {
            $this->response['code'] = 1050;
            $this->response['message'] = '帳號有誤, 請重整頁面(1050)';
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

        /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function contact(Request $request)
    {
        $data = $request->all();
        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'min:2', 'max:15'],
            'email' => ['required', 'string', 'email', 'max:50'],
            'phone' => ['required', 'string', 'min:3', 'max:30', 'regex:/^[0-9+][0-9]*/'],
            'message' => ['required', 'min:10','max:500'],
        ],[
            'email.required' => '請填寫通訊信箱',
            'email.max' => '通訊信箱：最多不超過50字',
            'email.email' => '通訊信箱：不符合郵件格式',
            'name.required' => '聯絡人姓名未填',
            'name.min' => '聯絡人姓名：2~15字元',
            'name.max' => '聯絡人姓名：2~15字元',
            'phone.required' => '請填聯絡電話',
            'phone.min' => '聯絡電話：請輸入3～30字元',
            'phone.max' => '聯絡電話：請輸入3～30字元',
            'phone.regex' => '聯絡電話：僅可輸入數字以及字首+號',
            'message.required' => '請填寫留言內容',
            'message.min' => '最少需輸入10字',
            'message.max' => '最多只能輸入500字',
        ]);

        if ($validator->fails()) {
            $error = $validator->errors()->first();
            $this->response['code'] = 1039;
            $this->response['message'] = $error;
            return response()->json($this->response);
        }
        $content = '電話：' . $data['phone'] . '<br> 信箱：' . $data['email'] . ', <br> 留言內容:'. $data['message'];
        $this->emailEvent->send($this->adminEmail, '【用戶留言】' . $data['name'], $content, $data['name']);
        $this->response['message'] = '修改成功';

        return response()->json($this->response);
    }
}
