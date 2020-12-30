<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class UsersLogoutController extends Controller
{
    private $response;
    /**
     * MenuController constructor.
     */
    public function __construct()
    {
        $this->response = $this->normalOutput();
    }
    public function UsersLogout (Request $request)
    {
        $cookie = Cookie::forget('SESSION');
        $this->response['code'] = 1002;

        $this->response['message'] = "已登出";

        return response()->json($this->response)->withCookie($cookie);
    }
}
