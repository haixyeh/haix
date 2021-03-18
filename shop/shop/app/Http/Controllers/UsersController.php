<?php

namespace App\Http\Controllers;

use Hash;
use RedisServer;
use App\Users;
use App\UserInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\LevelController;
use App\Http\Controllers\EmailController;

class UsersController extends Controller
{
    private $response;
    private $timeNow;
    /**
     * MenuController constructor.
     */
    public function __construct()
    {
        $this->response = $this->normalOutput();
        $this->emailEvent = new EmailController;
        $this->timeNow = date("Y-m-d H:i:s" , mktime(date('H')+8, date('i'), date('s'), date('m'), date('d'), date('Y')));
    }

    // 獲取使用者共用
    public function UserGet(Request $request)
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

    // 註冊
    public function store(Request $request)
    {
        $data = $request->all();
        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'min:4', 'max:15', 'regex:/^[A-Za-z][A-Za-z0-9_]+$/'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'min:6', 'max:15', 'regex:/^[A-Za-z0-9]+$/']
        ],[
            'name.required' => '請填寫帳號',
            'name.min' => '帳號：請輸入4～15字元',
            'name.max' => '帳號：請輸入4～15字元',
            'name.regex' => '帳號：請輸入英文加數字, 字首需是英文',
            'email.required' => '請填寫郵件信箱',
            'email.email' => '請輸入郵件格式',
            'password.required' => '請填寫密碼',
            'password.min' => '密碼：請輸入6～15字元',
            'password.max' => '密碼：請輸入6～15字元',
            'password.regex' => '密碼：不能輸入特殊符號',
        ]);

        if ($validator->fails()) {
            $error = $validator->errors()->first();

            switch (true) {
                case $validator->errors()->has('name'):
                    $this->response['code'] = 1003; // 帳號驗證問題
                    break;
                case $validator->errors()->has('password');
                    $this->response['code'] = 1004; // 密碼驗證問題
                    break;
                case $validator->errors()->has('email');
                    $this->response['code'] = 1005; // 郵件信箱問題
                    break;
                default:
                    $this->response['code'] = 1008;
                    $this->response['message'] =  '註冊問題, 請聯繫客服';
                    break;
            }

            $this->response['message'] = $error;
            return response()->json($this->response);
        }
        $api_token= Str::random(10);
        try {
            $queryStatus = true;
            Users::create([
                'name' =>$data['name'],
                'email' =>$data['email'],
                'password' => Hash::make($data['password']),
                'api_token' => $api_token
            ]);
        } catch (\Throwable $th) {
            $queryStatus = false;
            $errorCode = $th->getCode();
            switch ($errorCode) {
                case '23000':
                    $this->response['code'] = 1006;
                    $this->response['message'] =  '帳號或是郵件帳號已申請過';
                    break;
                default:
                    $this->response['code'] = 1007;
                    $this->response['message'] =  '註冊問題, 請聯繫客服';
                    break;
            }
        }
        
        
        if ($queryStatus) {
            $this->response['message'] = '建立成功';
            // 成功後建立用戶資料
            $CreateUserInfo=UserInfo::create([
                'email' => $data['email'],
                'account' => $data['name'],
            ]);
            $content = '註冊帳號：' . $data['name'] . ', 歡迎您參與【黑司生活購物網】, 即日起購物都需要花錢';
            $this->emailEvent->send($data['email'], '【黑司生活購物網】 - 恭喜註冊成功', $content, $data['name']);
            $users = Users::where([
                'name' => $data['name'],
            ])->first();

            $this->setMessage($users->id, '<span>' . $this->timeNow . '</span>:'.'恭喜註冊成功 - 歡迎使用【黑司生活購物網】');
        }

        return response()->json($this->response);
            
    }

    // 查詢所有會員
    public function show() {
        $usersAll = Users::get()->all();
        $result = array(
            'userList'=> $usersAll,
            'levelList'=> LevelController::instance()->getAllLevel()
        );
        $data = $this->success($result);

        return $data;
    }

    // 取得郵件密碼
    public function getPwd(Request $request) {
        $data = $request->all();
        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'min:4', 'max:15', 'regex:/^[A-Za-z][A-Za-z0-9_]+$/'],
            'email' => ['required', 'string', 'email'],
        ],[
            'name.required' => '請填寫帳號',
            'name.min' => '帳號：請輸入4～15字元',
            'name.max' => '帳號：請輸入4～15字元',
            'name.regex' => '帳號：請輸入英文加數字, 字首需是英文',
            'email.required' => '請填寫郵件信箱',
            'email.email' => '請輸入郵件格式'
        ]);

        if ($validator->fails()) {
            $error = $validator->errors()->first();

            switch (true) {
                case $validator->errors()->has('name'):
                    $this->response['code'] = 1003; // 帳號驗證問題
                    break;
                case $validator->errors()->has('email');
                    $this->response['code'] = 1005; // 郵件信箱問題
                    break;
                default:
                    $this->response['code'] = 1008;
                    $this->response['message'] =  '忘記密碼問題, 請聯繫客服';
                    break;
            }

            $this->response['message'] = $error;
            return response()->json($this->response);
        }

        try {
            $queryStatus = true;
            $user = Users::where([
                'name' => $data['name'],
                'email' => $data['email'],
            ])->first();
        } catch (\Throwable $th) {
            $queryStatus = false;
        }
        if (!$queryStatus) {
            $this->response['code'] = 1011;
            $this->response['message'] =  '找不到對應帳戶';
            return response()->json($this->response);
        }
        
        $parms = 'token=' . $user->api_token . '&name=' . $user->name . '&email=' . $user->email;
        $url = 'haix.com:3000/web/changePwd?params=' . base64_encode($parms);
        $content = '忘記密碼, 帳號：' . $user->name . ', 請重新設定密碼：' . $url;
        $this->emailEvent->send($user->email, '【黑司生活購物網】 - 忘記密碼', $content, $user->name);

        return response()->json($this->response);
    }

    // 客端忘記密碼修改
    public function fixUpdatePwd(Request $request) {
        $data = $request->all();

        // 會員是否為登入狀態
        if (!empty($isHasUser)) {
            $this->response['code'] = 2030;
            $this->response['message'] = '您已在登入狀態';
            return response()->json($this->response);
        }

        $validator = Validator::make($data, [
            'account' => ['required', 'string', 'min:4', 'max:15', 'regex:/^[A-Za-z][A-Za-z0-9_]+$/'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'min:6', 'max:15', 'regex:/^[A-Za-z0-9]+$/'],
            'token' => ['required', 'string']
        ],[
            'account.required' => '請填寫帳號',
            'account.min' => '帳號：請輸入4～15字元',
            'account.max' => '帳號：請輸入4～15字元',
            'account.regex' => '帳號：請輸入英文加數字, 字首需是英文',
            'email.required' => '請填寫郵件信箱',
            'email.email' => '請輸入郵件格式',
            'password.required' => '請填寫密碼',
            'password.min' => '密碼：請輸入6～15字元',
            'password.max' => '密碼：請輸入6～15字元',
            'password.regex' => '密碼：不能輸入特殊符號',
            'token.required' => '連結無效',
            'token.string' => '連結無效'
        ]);

        if ($validator->fails()) {
            $error = $validator->errors()->first();

            switch (true) {
                case $validator->errors()->has('account'):
                    $this->response['code'] = 1003; // 帳號驗證問題
                    break;
                case $validator->errors()->has('password');
                    $this->response['code'] = 1004; // 密碼驗證問題
                    break;
                case $validator->errors()->has('id');
                    $this->response['code'] = 1009; // id 驗證問題
                    break;
                default:
                    $this->response['code'] = 1012;
                    $this->response['message'] =  '忘記密碼問題, 請聯繫客服';
                    break;
            }

            $this->response['message'] = $error;
            return response()->json($this->response);
        }
        try {
            $queryStatus = true;
            $users = Users::where([
                'name' => $data['account'],
                'email' => $data['email'],
                'api_token' => $data['token']
            ])->first();
        } catch (\Throwable $th) {
            $queryStatus = false;
        }

        if (empty($users)) {
            $this->response['code'] = 2002;
            $this->response['message'] = '此郵件更改密碼已失效, 請重新申請忘記密碼';
            return response()->json($this->response);
        }
        // 更新api token 使客端登出
        if ($users->update(['password' => Hash::make($data['password']), 'api_token'=> Str::random(10)])) {
            $this->response['message'] = '會員:'. $data['account'].',密碼更改成功, 請重新登入';
            return response()->json($this->response);
        };
        $this->response['code'] = 2003;
        $this->response['message'] = '會員:'. $data['account'] .',密碼更改失敗';
        return response()->json($this->response);
    }


    // 後台修改 客端會員密碼
    public function updatePwd(Request $request) {
        $data = $request->all();
        $validator = Validator::make($data, [
            'account' => ['required', 'string'],
            'id' => ['required', 'number'],
            'password' => ['required', 'string', 'min:6', 'max:15', 'regex:/^[A-Za-z0-9]+$/'],
        ],[
            'id.required' => '列表key值有誤',
            'account.required' => '帳號判斷有誤',
            'password.required' => '請填寫修改密碼',
            'password.min' => '帳號：請輸入6～15字元',
            'password.max' => '帳號：請輸入6～15字元',
            'password.regex' => '密碼：不能輸入特殊符號',
        ]);

        if ($validator->fails()) {
            $error = $validator->errors()->first();

            switch (true) {
                case $validator->errors()->has('account'):
                    $this->response['code'] = 1003; // 帳號驗證問題
                    break;
                case $validator->errors()->has('password');
                    $this->response['code'] = 1004; // 密碼驗證問題
                    break;
                case $validator->errors()->has('id');
                    $this->response['code'] = 1009; // id 驗證問題
                    break;
                default:
                    $this->response['code'] = 1008;
                    $this->response['message'] =  '註冊問題, 請聯繫客服';
                    break;
            }

            $this->response['message'] = $error;
            return response()->json($this->response);
        }
        
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
        if ($users->update(['password' => Hash::make($data['password']), 'api_token'=> Str::random(10)])) {
            $this->response['message'] = '會員:'. $data['account'].',密碼更改成功';
            return response()->json($this->response);
        };
        $this->response['code'] = 2003;
        $this->response['message'] = '會員:'. $data['account'] .',密碼更改失敗';
        return response()->json($this->response);
    }

    // 修改等級
    public function updateLevel(Request $request) {
        $data = $request->all();
        $validator = Validator::make($data, [
            'id' => ['required'],
            'level' => ['required', 'string'],
        ],[
            'id.required' => '列表key值有誤',
            'level.required' => '等級資料有誤',
        ]);

        if ($validator->fails()) {
            $error = $validator->errors()->first();

            switch (true) {
                case $validator->errors()->has('id');
                    $this->response['code'] = 1014; // 等級修改 id 驗證問題
                    break;
                case $validator->errors()->has('level'):
                    $this->response['code'] = 1015; // 帳號驗證問題
                    break;
                default:
                    $this->response['code'] = 1016;
                    $this->response['message'] =  '修改等級問題, 請聯繫客服';
                    break;
            }

            $this->response['message'] = $error;
            return response()->json($this->response);
        }

        $users = Users::where([
            'id' => $data['id'],
        ]);

        // 找不到該會員
        if (!$users->first()) {
            $this->response['code'] = 1018;
            $this->response['message'] = '無此會員帳號';
            return response()->json($this->response);
        }
        // 更新api token 使客端登出
        if ($users->update(['level' => $data['level'], 'api_token'=> Str::random(10)])) {
            $this->response['message'] = '會員:'. $users->first()['name'].',等級更改成功';
            return response()->json($this->response);
        };
        $this->response['code'] = 1019;
        $this->response['message'] = '會員:'. $users->first()['name'] .',等級更改失敗';
        return response()->json($this->response);
    }

    // 刪除
    public function destroy(Request $request, $id = null) {
        if (empty($id)) {
            $this->response['code'] = 1028;
            $this->response['message'] = '未成功刪除';
            return response()->json($this->response);
        }
        $users = Users::where('id',$id);
        $userAccount = $users->first()['name'];

        $data = array();
        if ($users->first() && $users -> delete()) {
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

        $this->response['code'] = 1021;
        $this->response['message'] = '未成功刪除';
        return response()->json($this->response);
    }

    // 設定通知用戶信息
    public function setMessage($userId = null, $msg = null) {
        if (empty($msg) || empty($userId)) {
            return false;
        }
        $redis = RedisServer::connection();
        $userMsg = json_decode($redis->get('user'. $userId . 'msg'));

        if (empty($userMsg)) {
            $userMsg = array();
        }

        array_unshift($userMsg, $msg);

        $over = $redis->set('user'. $userId . 'msg', json_encode($userMsg));

        if ($over) {
            $redis->set('user'. $userId . 'seeMsg', 'N');
            return true;
        } else {
            return false;
        }

        return true;
    }

    // get Msg 獲取用戶訊息
    public function getMessage(Request $request)
    {
        $redis = RedisServer::connection();
        $users = $this->UserGet($request);
        $userMsg = json_decode($redis->get('user'. $users->id . 'msg'));

        if (empty($userMsg)) {
            return response()->json($this->response);
        }

        $data = json_decode($redis->get('user'. $users->id . 'msg'));
        $redis->set('user'. $users->id . 'seeMsg', 'Y');
        $this->response['data'] = $data;

        return response()->json($this->response);
    }
    public function delMsg(Request $request, $id)
    {
        $redis = RedisServer::connection();
        $users = $this->UserGet($request);
        $userMsg = json_decode($redis->get('user'. $users->id . 'msg'));

        if (empty($userMsg)) {
            $this->response['code'] = 2033;
            $this->response['msg'] = '已無訊息可刪';
            return response()->json($this->response);
        }
        array_splice($userMsg, $id, 1);

        $redis->set('user'. $users->id . 'msg', json_encode($userMsg));

        return response()->json($this->response);
    }
}
