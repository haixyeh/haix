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
            'password' => Hash::make($data['password']),
            'api_token' => $api_token
        ]);
        
        if ($Create) {
            $this->response['message'] = '建立成功';
            // 成功後建立用戶資料
            $CreateUserInfo=UserInfo::create([
                'email' => $data['email'],
                'account' => $data['name'],
            ]);

            $users = Users::where([
                'name' => $data['name'],
            ])->first();

            $this->setMessage($users->id, '<span>' . $this->timeNow . '</span>:'.'恭喜註冊成功 - 歡迎使用【黑司生活購物網】');
        } else {
            $this->response['message'] = '建立失敗';
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
    // 修改
    public function update(Request $request) {
        $users = Users::where('id',$id);
        $request->validate([
            'name',
            'email' => 'unique:users|email',
            'password',
        ]);

        Auth::user()->update($request->all());

        echo '資料修改成功，以下爲修改結果';
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
            'id' => ['required', 'number'],
            'level' => ['required', 'string'],
        ],[
            'id.required' => '列表key值有誤',
            'level.required' => '等級資料有誤',
        ]);

        $users = Users::where([
            'id' => $data['id'],
        ]);
        if (!$users) {
            $this->response['code'] = 2002;
            $this->response['message'] = '無此會員帳號';
            return response()->json($this->response);
        }
        // 更新api token 使客端登出
        if ($users->update(['level' => $data['level'], 'api_token'=> Str::random(10)])) {
            $this->response['message'] = '會員:'. $users->first()['name'].',等級更改成功';
            return response()->json($this->response);
        };
        $this->response['code'] = 2003;
        $this->response['message'] = '會員:'. $users->first()['name'] .',等級更改失敗';
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
