<?php

namespace App\Http\Controllers;

use App\Order;
use App\Goods;
use App\Http\Controllers\GoodsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use RedisServer;

class OrdersController extends Controller
{
    private $response;
    private $goodsMehtod;
    /**
     * MenuController constructor.
     */
    public function __construct()
    {
        $this->response = $this->normalOutput();
        $this->goodsMehtod = new GoodsController;
    }
    
    // 結帳：建立訂單
    public function checkout(Request $request)
    {
        $data = $request->all();
        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'min:2', 'max:30'],
            'phone' => ['required', 'string', 'min:3', 'max:30'],
            'address' => ['required', 'string', 'min:5', 'max:100']
        ],[
            'name.required' => '請填寫帳號',
            'name.min' => '收件者：請輸入2～30字元',
            'name.max' => '收件者：請輸入2～30字元',
            'phone.required' => '請填寫聯絡電話',
            'phone.min' => '電話：請輸入3～30字元',
            'phone.max' => '電話：請輸入3～30字元',
            'address.required' => '請填寫收件地址',
            'address.min' => '收件地址：請輸入5～100字元',
            'address.max' => '收件地址：請輸入5～100字元',
        ]);

        if ($validator->fails()) {
            $error = $validator->errors()->first();

            $this->response['message'] = $error;
            return response()->json($this->response);
        }
        $goodsCar = $this->goodsMehtod->shopCarData($request, true);
        $goodsArray = array();
        foreach ($goodsCar['goodsIndo'] as $key => $item) {
            array_push($goodsArray,
                array(
                    'count'=>$item['count'],
                    'id'=>$item['id']
                )
            );
        }

        $Create=Order::create([
            'userId' => $goodsCar['userId'],
            'name' =>$data['name'],
            'phone' =>$data['phone'],
            'address' => $data['address'],
            'totalAmount' => $goodsCar['totalAmount'],
            'goodsIndo' => json_encode($goodsArray),
            'status'=> 'N'
        ]);
        
        if ($Create) {
            $this->response['message'] = '建立成功';
            RedisServer::set('user'. $goodsCar['userId'] . 'car', '');
        } else {
            $this->response['message'] = '建立失敗';
        }

        return response()->json($this->response);
    }
    public function show() {
        $list = Order::get()->all();
        foreach ($list as $key => $item) {
            $goodsCountID = json_decode($item['goodsIndo']);
            $goodsIds = array();
            $goodsCounts = array();
            foreach ($goodsCountID as $itemKey => $itemInfo) {
                array_push($goodsIds, $itemInfo->id);
                array_push($goodsCounts, $itemInfo->count);
            }
            $goods = Goods::whereIn('id', $goodsIds)->select('id', 'name', 'total')->get();
            foreach ($goods  as $goodsKey => $goodsItem) {
                $goodsItem['counts'] = $goodsCounts[$goodsKey];
            }
            $item['goods'] = $goods;
            unset($item['goodsIndo']);
            unset($item['userId']);
        }

        $this->response['data'] = $list;
        
    
        return response()->json($this->response);
    }
}
