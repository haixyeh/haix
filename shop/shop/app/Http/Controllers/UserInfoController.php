<?php

namespace App\Http\Controllers;

use App\Users;
use App\UserInfo;
use App\Http\Controllers\UsersLoginController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserInfoController extends Controller
{
    private $response;
    private $userMethod;
    /**
     * MenuController constructor.
     */
    public function __construct()
    {
        $this->response = $this->normalOutput();
        $this->userMethod = new UsersLoginController();
    }
    function UserGet($request) {
        $user = $this->userMethod->UserGet($request);
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
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        $user = $this->UserGet($request);
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
        $data = $this->success($userInfo);

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
            'account' => ['required', 'string', 'min:6', 'max:15'],
            'email' => ['required', 'string', 'email', 'max:50'],
            'firstName'=> ['required', 'string', 'max:10'],
            'lastName' => ['required', 'string', 'max:20'],
            'address' => ['required', 'string', 'max:50'],
            'phone' => ['required', 'string', 'max:20'],
        ],[
            'email.required' => '請填寫郵件信箱',
            'email.max' => '郵件帳號：最多不超過50字',
            'firstName.required' => '請填寫姓氏帳號：請輸入6～15字元',
            'firstName.max' => '最多填寫10個字',
            'lastName.required' => '請填名字',
            'lastName.max' => '最多填寫20個字',
            'address.required' => '請填寫密碼',
            'phone.required' => '請填寫手機號碼',
        ]);
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
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    // public function store(Request $request)
    // {
    //     //
    // }


    // /**
    //  * Show the form for editing the specified resource.
    //  *
    //  * @param  int  $id
    //  * @return \Illuminate\Http\Response
    //  */
    // public function edit($id)
    // {
    //     //
    // }

    // /**
    //  * Remove the specified resource from storage.
    //  *
    //  * @param  int  $id
    //  * @return \Illuminate\Http\Response
    //  */
    // public function destroy($id)
    // {
    //     //
    // }
}
