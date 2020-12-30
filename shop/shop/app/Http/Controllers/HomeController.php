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
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // return view('home');
    }
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
