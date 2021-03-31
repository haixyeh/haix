<?php

namespace App\Http\Controllers;

use App\Order;
use App\Goods;
use App\Users;
use App\Level;
use App\RebackOrder;
use RedisServer;
use Illuminate\Routing\UrlGenerator;
use App\Http\Controllers\GoodsController;
use App\Http\Controllers\UsersController;
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
    private $goodsMethod;
    private $usersMethod;
    private $timeNow;
    /**
     * MenuController constructor.
     */
    public function __construct()
    {
        $this->response = $this->normalOutput();
        $this->goodsMethod = new GoodsController;
        $this->usersMethod = new UsersController;
        $this->timeNow = date("Y-m-d H:i:s" , mktime(date('H')+8, date('i'), date('s'), date('m'), date('d'), date('Y')));
    }
    
    // 結帳：建立訂單
    public function checkout(Request $request)
    {
        $data = $request->all();
        $isCheckCoupon = false;     // 確認購物金是否足夠
        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'min:2', 'max:30', 'regex:/^[^0-9,:;!@#$%^&*?<>()+=`|[\]{}\\".~\-\0]*$/'],
            'phone' => ['required', 'string', 'min:3', 'max:30', 'regex:/^[0-9+][0-9]+$/'],
            'address' => ['required', 'string', 'min:5', 'max:100', 'regex:/^[^0-9,:;!@#$%^&*?<>()+=`|[\]{}\\".~\-\0]*$/'],
            'coupon' => ['numeric']
        ],[
            'name.required' => '請填寫收件者',
            'name.min' => '收件者：字數至少兩個字',
            'name.regex' =>'收件者：請勿輸入特殊符號',
            'name.min' => '收件者：請輸入2～30字元',
            'name.max' => '收件者：請輸入2～30字元',
            'phone.required' => '請填寫聯絡電話',
            'phone.min' => '聯絡電話：請輸入3～30字元',
            'phone.max' => '聯絡電話：請輸入3～30字元',
            'phone.regex' => '聯絡電話：僅可輸入數字以及字首+號',
            'address.required' => '請填寫收件地址',
            'address.min' => '收件地址：請輸入5～100字元',
            'address.max' => '收件地址：請輸入5～100字元',
            'address.regex' => '收件地址：請勿輸入特殊符號',
            'coupon.numeric' => '購物金額格式錯誤',
        ]);

        if ($validator->fails()) {
            $error = $validator->errors()->first();
            $this->response['code'] = 3011;
            $this->response['message'] = $error;
            return response()->json($this->response);
        }

        $goodsCar = $this->goodsMethod->shopCarData($request, true);
        $user= Users::where(['name' => $goodsCar['userAccount']]);
        $userCoupon = $user->first()->coupon;   // 用戶購物金

        // 確認購物金是否足夠
        if (array_key_exists('coupon', $data)) {
            if ($userCoupon < $data['coupon']) {
                $this->response['code'] = 5566;
                $this->response['message'] = '購物金不夠使用, 請重整確認頁面' . '(' . $this->response['code'] . ')';
                return response()->json($this->response);
            }
            $isCheckCoupon = true;
        } else {
            $data['coupon'] = 0;
        }

        // 確認購物車是否存在貨物
        if (empty($goodsCar['goodsIndo'])) {
            $this->response['code'] = 5567;
            $this->response['message'] = '購物車空了, 請重整頁面（物品已下架或是物品數量不足）';
            return response()->json($this->response);
        }

        $goodsArray = $this->goodsLoopArray($goodsCar['goodsIndo']);
        $orderNumber = $this->generateOrderNR(); 

        // 確認商品數量
        if (empty($goodsArray)) {
            $this->response['code'] = 5568;
            $this->response['message'] = '貨品不足, 請查看商品實際剩餘貨量';
            return response()->json($this->response);
        }
        // 使用購物金超過貨品金額
        if ($goodsCar['totalAmount'] < $data['coupon']) {
            $this->response['code'] = 5569;
            $this->response['message'] = '使用購物金超過貨品金額';
            return response()->json($this->response);
        }

        $Create=Order::create([
            'account' => $goodsCar['userAccount'],
            'orderNumber' => $orderNumber,
            'name' =>$data['name'],
            'phone' =>$data['phone'],
            'address' => $data['address'],
            'totalAmount' => $goodsCar['totalAmount'],
            'promsPrice' => $goodsCar['promsPrice'],
            'currentProms' => $goodsCar['currentProms'],
            'goodsIndo' => json_encode($goodsArray),
            'coupon' => $data['coupon'],
            'payment' => $goodsCar['totalAmount'] - $data['coupon']
        ]);
        
        if ($Create) {
            // 先扣除購物金
            if ($userCoupon > 0) {
                $user->update(['coupon' => $userCoupon - $data['coupon']]);
                $this->usersMethod->setCoupon($user->first()->id, '<span>'. $this->timeNow .'</span> - ' . '購買【'. $orderNumber .'】, 扣除購物金' . $data['coupon']);
            }
            $this->response['message'] = '建立成功, 您的訂單編號:' . $orderNumber ;
            $this->usersMethod->setMessage($user->first()->id, '已於[<span>'. $this->timeNow .'</span>] 訂購貨品,' . '訂單編號【' . $orderNumber . '】【貨到付款】');
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
    public function showBack(Request $request, $back = false)
    {
        return $this->show($request, true);
    }
    // 後台顯示客戶訂單資料
    public function show(Request $request, $back = false)
    {
        $account = $request->input('account');
        $name = $request->input('userName');
        $orderNumber = $request->input('orderNumber');

        $data = array(
            'account'=> $account ? $account : '',
            'name'=> $name ? $name : '',
        );
        $validator = Validator::make($data, [
            'name' => ['string', 'max:30', 'regex:/^[^0-9,:;!@#$%^&*?<>()+=`|[\]{}\\".~\-\0]*$/'],
            'account' => ['string', 'max:30', 'regex:/^[A-Za-z][A-Za-z0-9_]+$/'],
        ],[
            'name.regex' =>'收件者：請勿輸入特殊符號',
            'name.max' => '收件者：請輸入1～30字元',
            'account.max' => '帳號：請輸入4～15字元',
            'account.regex' => '帳號：請輸入英文加數字, 字首需是英文',
        ]);

        if ($validator->fails()) {
            $error = $validator->errors()->first();

            switch (true) {
                case $validator->errors()->has('name'):
                    $this->response['code'] = 3501; // 帳號驗證問題
                    break;
                case $validator->errors()->has('account');
                    $this->response['code'] = 3502; // 收件者驗證問題
                    break;
                default:
                    $this->response['code'] = 3503;
                    $this->response['message'] =  '訂單管理問題, 請聯繫客服';
                    break;
            }

            $this->response['message'] = $error;
            return response()->json($this->response);
        }

        if ($back) {
            $list = Order::having('cancelOrder', 'N')
            ->having('status', 'R')
            ->orHaving('status', 'T')
            ->orHaving('status', 'F')
            ->orHaving('status', 'L')
            ->orderBy('id', 'desc')
            ->get()
            ->all();
        } else {
            $list = Order::when($account, function($query) use ($account) {
                return $query->where('account', $account);
            })
            ->when($name, function($query) use ($name) {
                return $query->where('name', 'like', '%' . $name . '%');
            })
            ->when($orderNumber, function($query) use ($orderNumber) {
                return $query->where('orderNumber', 'like', '%' . $orderNumber . '%');
            })
            ->having('cancelOrder', 'N')
            ->having('status', 'N')
            ->orHaving('status', 'Y')
            ->orHaving('status', 'S')
            ->orHaving('status', 'E')
            ->orHaving('status', 'L')
            ->orderBy('id', 'desc')
            ->get()
            ->all();
        }
        foreach ($list as $key => $item) {
            $goodsIndo = json_decode($item['goodsIndo']);
            $goodsIds = array();
            $goodscount = array();
            foreach ($goodsIndo as $itemKey => $itemInfo) {
                array_push($goodsIds, $itemInfo->id);
                array_push($goodscount, $itemInfo->count);
            }
            $goods = Goods::whereIn('id', $goodsIds)->select('id', 'name', 'total')->get();
            foreach ($goods as $goodsKey => $goodsItem) {
                $goodsItem['count'] = $goodscount[$goodsKey];
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
            $goodscount = array();
            foreach ($goodsCountID as $itemKey => $itemInfo) {
                array_push($goodsIds, $itemInfo->id);
                array_push($goodscount, $itemInfo->count);
            }
            $goods = Goods::whereIn('id', $goodsIds)->select('id', 'name', 'total')->get();
            foreach ($goods  as $goodsKey => $goodsItem) {
                $goodsItem['count'] = $goodscount[$goodsKey];
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
            $goodscount = array();
            $goodsAmount = array();
            foreach ($goodsCountID as $itemKey => $itemInfo) {
                array_push($goodsIds, $itemInfo->id);
                array_push($goodscount, $itemInfo->count);
                array_push($goodsAmount, $itemInfo->amount);
            }
            $goods = Goods::whereIn('id', $goodsIds)->select('id', 'name', 'total')->get();
            foreach ($goods  as $goodsKey => $goodsItem) {
                $goodsItem['count'] = $goodscount[$goodsKey];
                $goodsItem['amount'] = $goodsAmount[$goodsKey];
            }
            $item['goods'] = $goods;
            unset($item['goodsIndo']);
            unset($item['userId']);

            if ($item['status'] === 'R' || $item['status'] === 'T' || $item['status'] === 'F') {
                $orderNumber = $item['orderNumber'];
                $applyOrder = RebackOrder::where('orderNumber', $orderNumber)->orderBy('id', 'desc')->first();
                $item['back'] = isset($applyOrder->goodsIndo) ? json_decode($applyOrder->goodsIndo) : [];
            }
        }

        $this->response['data'] = $list;
        
    
        return response()->json($this->response);
    }
    // 顯示單一訂單
    function forOne(Request $request, $id) {
        $order = $this->findOrder((int) $id);
        $list = $order->first();
        $goodsIndo = json_decode($list['goodsIndo']);
        $goodsIds = array();
        $goodscount = array();
        foreach ($goodsIndo as $itemKey => $itemInfo) {
            array_push($goodsIds, $itemInfo->id);
            array_push($goodscount, $itemInfo->count);
        }
        $goods = Goods::whereIn('id', $goodsIds)->select('id', 'name', 'total')->get();
        foreach ($goods  as $goodsKey => $goodsItem) {
            $goodsItem['count'] = $goodscount[$goodsKey];
        }
        $list['goods'] = $goods;

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
           
            $this->usersMethod->setMessage($user->first()->id, '<span>'. $this->timeNow .'</span> - 完成交易, ' . '訂單編號【' . $orderData['orderNumber'] . '】');
            if ($hasUpLevel) {
                $lastUpLevel = $hasUpLevel->get()->first();
            }
            $lastLevelId = (int) $lastUpLevel['id'];
            $currenyLevelId = (int) $user->first()->level;
            
            if ($lastLevelId !== $currenyLevelId) {
                $user->update([
                    'level' => $lastLevelId
                ]);
                // 用於層級移除或是意外狀況, 否則完成訂單應只有晉升
                if ($lastLevelId > $currenyLevelId) {
                    $this->response['message'] = $orderData['account'] . '：用戶晉升至【'. $lastUpLevel['levelName'] . '】';
                    $this->usersMethod->setMessage($user->first()->id, '<span>'. $this->timeNow .'</span> - ' . '恭喜晉升至【'. $lastUpLevel['levelName'] . '】');
                } else {
                    $this->response['message'] = $orderData['account'] . '：用戶降級至【'. $lastUpLevel['levelName'] . '】';
                    $this->usersMethod->setMessage($user->first()->id, '<span>'. $this->timeNow .'</span> - ' . '降級至【'. $lastUpLevel['levelName'] . '】');
                }
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
        $user= Users::where(['name' => $order->first()->account]);

        if ($order->first()->status !== 'N') {
            $this->response['code'] = 3213;
            $this->response['message'] = '訂單不是「未確認」狀態, 無法取消';

            return response()->json($this->response);
        }

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
        if ($queryStatus) {
            $rebackCouponMsg = '';
            if ($order->first()->coupon) {
                // 將先扣除的購物金返回
                $userCoupon = $user->first()->coupon;   // 用戶購物金
                $coupon = $userCoupon + $order->first()->coupon;
                $user->update(['coupon' => $coupon]);
                $rebackCouponMsg = ', 購物金退還' . $order->first()->coupon;
            }
            $this->usersMethod->setMessage($user->first()->id, '<span>'. $this->timeNow .'</span> - 取消訂單, ' . '訂單編號【' . $order->first()->orderNumber . '】'. $rebackCouponMsg);
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

        if ($order->first()->status !== 'N') {
            $this->response['code'] = 3235;
            $this->response['message'] = '此筆訂單已確認，無法更改數量';
            return response()->json($this->response);
        }
        
        $preGoods = $order->get()->first();
        
        // 補回貨品數量
        if ($preGoods) {
            $this->goodsLoopArray(json_decode($preGoods['goodsIndo']), 'add');
        }
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

        $goods = $this->goodsMethod->shopCarData($request, true, $goodsArray);
        $coupon = $order->first()->coupon;  // 使用購物金
        try {
            $order->update([
                'goodsIndo' => json_encode($goodsArray),
                'payment' => $goods['totalAmount'] - $coupon,
                'totalAmount' => $goods['totalAmount'],
            ]);
            $queryStatus = true;
        } catch(Exception $e) {
            $queryStatus = false;
        }

        if (!$queryStatus) {
            $this->response['code'] = 3212;
            $this->response['message'] = '更改失敗';
        }

        return response()->json($this->response);
    }
}
