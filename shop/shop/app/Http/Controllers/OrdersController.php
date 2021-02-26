<?php

namespace App\Http\Controllers;

use App\Order;
use App\Goods;
use App\Users;
use App\Level;
use RedisServer;
use Illuminate\Routing\UrlGenerator;
use App\Http\Controllers\GoodsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Haruncpi\LaravelIdGenerator\IdGenerator;
use App\Http\Controllers\UserInfoController;

class OrdersController extends Controller
{
    /**
     * N: 未確認訂單
     * Y: 已確定訂單
     * S: 出貨中
     * E: 已完成
     * R: 退貨中
     * T: 待確認退貨商品
     * F: 退貨完成
     */
    private $response;
    private $goodsMehtod;
    private $timeNow;
    /**
     * MenuController constructor.
     */
    public function __construct()
    {
        $this->response = $this->normalOutput();
        $this->goodsMehtod = new GoodsController;
        $this->timeNow = date("Y-m-d H:i:s" , mktime(date('H')+8, date('i'), date('s'), date('m'), date('d'), date('Y')));
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

        // 購物車空
        if (empty($goodsCar['goodsIndo'])) {
            $this->response['code'] = 55661;
            $this->response['message'] = '購物車空了';
            return response()->json($this->response);
        }

        $goodsArray = $this->goodsLoopArray($goodsCar['goodsIndo']);

        if (empty($goodsArray)) {
            $this->response['code'] = 5567;
            $this->response['message'] = '貨品不足, 請查看商品實際剩餘貨量';
            return response()->json($this->response);
        }
        $orderNumber = $this->generateOrderNR();

        $Create=Order::create([
            'account' => $goodsCar['userAccount'],
            'orderNumber' => $orderNumber,
            'name' =>$data['name'],
            'phone' =>$data['phone'],
            'address' => $data['address'],
            'totalAmount' => $goodsCar['totalAmount'],
            'promsPrice' => $goodsCar['promsPrice'],
            'currentProms' => $goodsCar['currentProms'],
            'goodsIndo' => json_encode($goodsArray)
        ]);
        
        if ($Create) {
            $this->response['message'] = '建立成功, 您的訂單編號:' . $orderNumber ;
            RedisServer::set('user'. $goodsCar['userId'] . 'car', '');
        } else {
            $this->response['code'] = 5566;
            $this->response['message'] = '建立失敗';
        }

        return response()->json($this->response);
    }

    // 貨品數量處理
    function goodsLoopArray($goodsAll, $method = 'cut') {
        $goodsArray = array();

        foreach ($goodsAll as $key => $item) {
            $goods = Goods::where('id', $item->id)->get()->first();

            if ($method === 'cut') {
                $leftover = (int) $goods['total'] - (int) $item->count;
            } else {
                $leftover = (int) $goods['total'] + (int) $item->count;
            }

            if (!$goods || $leftover < 0) {
                return array();
            }

            $goods->update([
                'total' => $leftover
            ]);

            $amount = isset($item->amount) ? $item->amount : 0;

            array_push($goodsArray,
                array(
                    'count'=>$item->count,
                    'id'=>$item->id,
                    'amount'=> $amount
                )
            );
        }

        return $goodsArray;
    }
    // 訂單號轉換
    public function generateOrderNR()
    {
        $orderObj = Order::select('orderNumber')->orderBy('id', 'desc')->first();
        if ($orderObj) {
            $orderNr = $orderObj->orderNumber;
            $removed1char = substr($orderNr, 1);
            $generateorderNumber = $stpad = '#' . str_pad($removed1char + 1, 8, "0", STR_PAD_LEFT);
        } else {
            $generateorderNumber = '#' . str_pad(1, 8, "0", STR_PAD_LEFT);
        }
        return $generateorderNumber;
    }
    // 後台退貨訂單資料
    public function showBack($back = false)
    {
        return $this->show(true);
    }
    // 後台顯示客戶訂單資料
    public function show($back = false)
    {
        if ($back) {
            $list = Order::having('cancelOrder', '=', 'N')
                ->having('status', '=', 'R')->orHaving('status', '=', 'T')->orHaving('status', '=', 'F')
                ->get()->all();
        } else {
            $list = Order::having('cancelOrder', '=', 'N')
                ->having('status', '!=', 'R')->having('status', '!=', 'T')->having('status', '!=', 'F')
                ->get()->all();
        }
    
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
    // 後台顯示客戶訂單退貨申請資料
    public function reBackShow()
    {
        $list = Order::having('cancelOrder', '=', 'N')->get()->all();
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

    // 客戶顯示訂單資料
    public function showGuest(Request $request)
    {
        $userName = UserInfoController::instance()->UserGet($request)->name;
        $list = Order::having('account', '=', $userName)->orderBy('created_at', 'desc')->get()->all();
        foreach ($list as $key => $item) {
            $goodsCountID = json_decode($item['goodsIndo']);
            $goodsIds = array();
            $goodsCounts = array();
            $goodsAmount = array();
            foreach ($goodsCountID as $itemKey => $itemInfo) {
                array_push($goodsIds, $itemInfo->id);
                array_push($goodsCounts, $itemInfo->count);
                array_push($goodsAmount, $itemInfo->amount);
            }
            $goods = Goods::whereIn('id', $goodsIds)->select('id', 'name', 'total')->get();
            foreach ($goods  as $goodsKey => $goodsItem) {
                $goodsItem['counts'] = $goodsCounts[$goodsKey];
                $goodsItem['amount'] = $goodsAmount[$goodsKey];
            }
            $item['goods'] = $goods;
            unset($item['goodsIndo']);
            unset($item['userId']);
        }

        $this->response['data'] = $list;
        
    
        return response()->json($this->response);
    }

    // 搜尋訂單
    function findOrder($id) {
        $order = Order::where('id', $id);
        return $order;
    }

    // 更新資料
    function update($DB, $updateArray) {
        return $DB->update($updateArray);
    }

    // 訂單處理狀態
    public function orderSatus(Request $request) {
        $data = $request->all();

        $validator = Validator::make($data, [
            'status' => ['required', 'string', 'max:1'],
        ],[
            'status.required' => '處理狀態有誤, 請重新操作',
            'status.max' => '處理狀態有誤, 請重新操作'
        ]);

        $order = $this->findOrder($data['id']);
        $orderStatus = $order->first()->status;

        try {
            $order->update(['status' => $data['status']]);
            $queryStatus = true;
        } catch(Exception $e) {
            $queryStatus = false;
        }

        // 計算總消費金額
        if ($queryStatus && $data['status'] === 'E') {
            // 完成送出, 但資料表已完成
            if ($orderStatus === 'E') {
                $this->response['code'] = 3211;
                $this->response['message'] = '已訂單已完成, 請重新整理';
                return response()->json($this->response);
            }
            $originMemo = $order->first()->memo;
            $newMemo = '<p><span>' . $this->timeNow . '</span>:[完成訂單]  </p>';
            $order->update(['memo' => $originMemo . $newMemo ]);
            $this->finshOrder($order);
        }

        if (!$queryStatus) {
            $this->response['code'] = 3210;
            $this->response['message'] = '更改失敗';
        }
        return response()->json($this->response);
    }

    // 完成訂單計算等級調整
    function finshOrder($order)
    {
        $orderData = $order->get()->first();
        $user = Users::where('name',$orderData['account']);
        $userCost = ($user->get()->first())['cost'] + $orderData['totalAmount'];

        try {
            $user->update([
                'cost' => $userCost 
            ]);
            $queryStatus = true;
        } catch(Exception $e) {
            $queryStatus = false;
        }

        if ($queryStatus) {
            $hasUpLevel = Level::having('upgradeAmount', '<', $userCost)->orderBy('upgradeAmount', 'desc');
            $lastUpLevel = '';
            if ($hasUpLevel) {
                $lastUpLevel = $hasUpLevel->get()->first();
            }
            if ((int) $lastUpLevel['id'] !== (int) $user->first()->level ) {
                $user->update([
                    'level' => $lastUpLevel['id']
                ]);
                $this->response['message'] = $orderData['account'] . '：用戶晉升至【'. $lastUpLevel['levelName'] . '】';
            }
        }


        if (!$queryStatus) {
            $this->response['code'] = 3212;
            $this->response['message'] = '用戶金額總計, 計算失敗(請聯繫客服)';
        }
        return response()->json($this->response);
    }

    // 取得api環境
    function evnPath()
    {
        $parsed = parse_url(url()->previous());
        $evn = str_replace('/', '', $parsed['path']);

        return $evn;
    }

    // 取消訂單
    public function cancelOrder(Request $request, $id)
    {
        $evn = $this->evnPath();
        $order = $this->findOrder($id);
        $mome = '訂單已取消';
        $originMemo = $order->first()->memo;

        if ($evn === 'admin') {
            $mome = '<p><span>'. $this->timeNow .'</span>:[客服取消訂單]已被取消, 詳細情況請聯繫客服</p>';
        } else {
            $mome = '<p><span>'. $this->timeNow .'</span>:[取消訂單] 您已將訂單取消</p>';
        }

        $preGoods = $order->get()->first();

        try {
            $order->update([
                'cancelOrder' => "Y",
                'memo' => $originMemo . $mome
            ]);
            $queryStatus = true;
        } catch(Exception $e) {
            $queryStatus = false;
        }
        // 補回貨品數量
        if ($preGoods && $queryStatus) {
            $this->goodsLoopArray(json_decode($preGoods['goodsIndo']), 'add');
        }

        if (!$queryStatus) {
            $this->response['code'] = 3210;
            $this->response['message'] = '訂單取消失敗';
        }

        return response()->json($this->response);
    }

    // 修改備註訂單
    public function memoFix(Request $request)
    {
        $data = $request->all();
        $validator = Validator::make($data, [
            'id' => ['required', 'numeric'],
        ],[
            'id.required' => '處理狀態有誤, 請重新操作',
        ]);
        $order = $this->findOrder($data['id']);
        $originMemo = $order->first()->memo;

        try {
            $order->update(['memo' => $originMemo . '<p>' . $data['memo'] . '</p>']);
            $queryStatus = true;
        } catch(Exception $e) {
            $queryStatus = false;
        }

        if (!$queryStatus) {
            $this->response['code'] = 3211;
            $this->response['message'] = '備註調整失敗';
        }

        return response()->json($this->response);
    }

    // 訂單貨品調整
    public function fixGoods(Request $request)
    {
        $data = $request->all();

        $validator = Validator::make($data, [
            'goods' => ['required', 'string'],
        ],[
            'goods.required' => '貨品描述有誤, 請重新操作',
        ]);

        $order = $this->findOrder($data['id']);
        
        $preGoods = $order->get()->first();
        
        // 刪除貨品數量
        $goodsArray = $this->goodsLoopArray(json_decode($data['goods']));
    
        $amountMap = array_column(json_decode($preGoods['goodsIndo']), 'amount', 'id');

        foreach ($goodsArray as $key => $value) {
            $goodsArray[$key]['amount'] = $amountMap[$value['id']];
        }

        if (empty($goodsArray)) {
            $this->response['code'] = 3233;
            $this->response['message'] = '貨品數量不足, 請重新整理頁面重新編輯訂單內容';
            return response()->json($this->response);
        }

        $goods = $this->goodsMehtod->shopCarData($request, true, $goodsArray);
        
        try {
            $order->update([
                'goodsIndo' => json_encode($goodsArray),
                'totalAmount' => $goods['totalAmount'],
            ]);
            $queryStatus = true;
        } catch(Exception $e) {
            $queryStatus = false;
        }

        // 補回貨品數量
        if ($preGoods && $queryStatus) {
            $this->goodsLoopArray(json_decode($preGoods['goodsIndo']), 'add');
        }

        if (!$queryStatus) {
            $this->response['code'] = 3212;
            $this->response['message'] = '更改失敗';
        }

        return response()->json($this->response);
    }
}
