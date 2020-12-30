<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Categories;

class CategoriesController extends Controller
{
    /**
     * 建立商品分類
     * @param request Illuminate\Http\Request
     * @return response->json()
     */
    public function create(Request $request)
    {
        $user = $this->UserGet($request);
        if (!$user) {
            echo 'error';
            exit();  
        }
        $Create=Categories::create([
            'account' => $user['name'],
            'email' => $user['email'],
            'lastName' => $user['name']
        ]);
    }
    /**
     * 查詢所有商品分類
     * @return response->json()
     */
    public function showAll2() {
        $usersAll = Categories::get()->all();
        $data = $this->success($usersAll);
        $allowed  = ['id', 'name'];
        
        return $data;
    }
    /**
     * 查詢所有商品分類顯示 id & name
     * @return response->json()
     */
    public function showAll() {
        $usersAll = Categories::get()->all();
        $usersAll2 = Categories::all()-> pluck(
            'name',
            'id'
        ); // { id: name}

        $data = $this->success($usersAll2->all());

        return $data;
    }
     /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return response->json()
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

        $infoTable = Categories::where([
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
}
