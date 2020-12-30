<?php

namespace App\Http\Controllers;

use App\Users;
use App\UserInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class UsersController extends Controller
{
    private $response;
    /**
     * MenuController constructor.
     */
    public function __construct()
    {
        $this->response = $this->normalOutput();
    }

    // 註冊
    public function store(Request $request)
    {
        $data = $request->all();
        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'min:4', 'max:15'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'min:6', 'max:15']
        ],[
            'name.required' => '請填寫帳號',
            'name.min' => '帳號：請輸入4～15字元',
            'name.max' => '帳號：請輸入4～15字元',
            'eamil.required' => '請填寫郵件信箱',
            'password.required' => '請填寫密碼',
            'password.min' => '帳號：請輸入6～15字元',
            'password.max' => '帳號：請輸入6～15字元',
        ]);

        if ($validator->fails()) {
            $error = $validator->errors()->first();

            switch (true) {
                case $validator->errors()->has('name'):
                    $this->response['code'] = 1003; // 請填寫帳號
                    break;
                case $validator->errors()->has('email');
                    $this->response['code'] = 1005; // 請填寫郵件信箱
                    break;
                default:
                    # code...
                    break;
            }

            $this->response['message'] = $error;
            return response()->json($this->response);
        }
        $api_token= Str::random(10);
        $Create=Users::create([
            'name' =>$data['name'],
            'email' =>$data['email'],
            'password' => $data['password'],
            'api_token' => $api_token,
        ]);
        
        if ($Create) {
            $this->response['message'] = '建立成功';
            // 成功後建立用戶資料
            // exit();
            $CreateUserInfo=UserInfo::create([
                'email' => $data['email'],
                'account' => $data['name'],
            ]);
        } else {
            $this->response['message'] = '建立失敗';
        }

        return response()->json($this->response);
            
    }

    // 查詢所有會員
    public function show() {
        $usersAll = Users::get()->all();
        $data = $this->success($usersAll);

        return $data;
    }
    // 修改
    public function update(Request $request) {
        $users = Users::where('id',$id);
        $request->validate([
            'name',
            'email' => 'unique:users|email',
            'password',
        ]);

        Auth::user()->update($request->all());

        echo  '資料修改成功，以下爲修改結果';
        return  $request->all();
    }

    // 修改密碼
    public function updatePwd(Request $request) {
        $data = $request->all();
        $validator = Validator::make($data, [
            'account' => ['required', 'string'],
            'id' => ['required', 'number'],
            'password' => ['required', 'string', 'min:6', 'max:15'],
        ],[
            'id.required' => '列表key值有誤',
            'account.required' => '帳號判斷有誤',
            'password.required' => '請填寫修改密碼',
            'password.min' => '帳號：請輸入6～15字元',
            'password.max' => '帳號：請輸入6～15字元',
        ]);
        
        $users = Users::where([
            'name' => $data['account'],
            'id' => $data['id']
        ]);
        if (!$users) {
            $this->response['code'] = 2002;
            $this->response['message'] = '無此會員帳號';
            return response()->json($this->response);
        }
        // 更新api token 使客端登出
        if ($users->update(['password' => $data['password'], 'api_token'=> Str::random(10)])) {
            $this->response['message'] = '會員:'. $data['account'].',密碼更改成功';
            return response()->json($this->response);
        };
        $this->response['code'] = 2003;
        $this->response['message'] = '會員:'. $data['account'] .',密碼更改失敗';
        return response()->json($this->response);
    }
    // 刪除
    public function destroy(Request $request, $id) {
        $users = Users::where('id',$id);
        $userAccount = $users->first()['name'];

        $data = array();
        if ($users && $users -> delete()) {
            $this->response['message'] = '刪除成功';

            // 詳細資料一併刪除
            $usersTable = UserInfo::where([
                'account' => $userAccount,
            ]);
            if ($usersTable && $usersTable -> delete()) {
                return response()->json($this->response);
            }
            $this->response['code'] = 200;
            $this->response['message'] = '刪除成功、詳細資料表未移除（請聯繫客服）';
            return response()->json($this->response);
        }
        $this->response['code'] = 2001;
        $this->response['message'] = '未成功刪除';
        return response()->json($this->response);
    }

}
