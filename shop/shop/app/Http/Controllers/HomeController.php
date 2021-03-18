<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    private $response;
    /**
     * HomeController constructor.
     */
    public function __construct()
    {
        $this->response = $this->normalOutput();
    }

    /**
     * 客端的導覽列
     */
    public function menuList(Request $request)
    {
        $isLogin = 'N';
        $list = array([
            array(
                'link' => '/web',
                'name' => 'home',
                'open' => 'Y'
            ),
            array(
                'link' => '/shopping',
                'name' => 'shopping',
                'open' => 'Y'
            ),
            array(
                'link' => '/member',
                'name' => 'member',
                'open' => $isLogin
            ),
        ]);
        if (request()->cookie('SESSION')) {
            $isLogin = 'Y';
        }
        $data = $this->success($list);
        return $data;
    }
}
