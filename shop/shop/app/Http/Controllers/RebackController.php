<?php

namespace App\Http\Controllers;

use App\Order;
use App\RebackOrder;
use App\Goods;
use App\Users;
use App\Level;
use App\Http\Controllers\UsersController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RebackController extends Controller
{
    /**
     * N: 未接收申請
     * Y: 已接收申請
     * F: 完成退貨程序
     */
    private $response;
    private $goodsMethod;
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
    /**
     * 搜尋訂單
     * @param  $id 訂單id
     * @return $order 訂單資料
     */
    function findOrder($id) {
        $order = Order::where('id', $id);
        return $order;
    }
    /**
     * 客端申請退貨項目
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function apply(Request $request)
    {
        $data = $request->all();
        $validator = Validator::make($data, [
            'orderId' => ['required', 'numeric'],
            'goodsIndo' => ['string'],
            'rebackAll' => ['string', 'max:1'],
            'reason' => ['required', 'string', 'max:256']
        ],[
            'orderId.required' => '訂單id有誤',
            'orderId.numeric' => '訂單id有誤',
            'goodsIndo.string' => '退貨清單有誤',
            'reason.required' => '請務必填寫退貨原因',
            'reason.string' => '退貨原因格式有誤'
        ]);
        if ($validator->fails()) {
            $error = $validator->errors()->first();
            $this->response['code'] = 6002;
            $this->response['message'] = $error;
            return response()->json($this->response);
        }
        $orderNumber = '';
        $order = $this->findOrder($data['orderId'])->first();

        if (empty($order)) {
            $this->response['code'] = 6001;
            $this->response['message'] = '訂單id不存在';
            return response()->json($this->response);
        }
        if ($order->status === 'L') {
            $this->response['code'] = 6012;
            $this->response['message'] = '訂單已完成, 不允許退貨';
            return response()->json($this->response);
        }
        if ($order->status !== 'E') {
            $this->response['code'] = 6005;
            $this->response['message'] = '訂單不是完成中訂單';
            return response()->json($this->response);
        }

        $orderNumber = $order->orderNumber;
        $hasApply = RebackOrder::where('orderNumber', $orderNumber)->having('rebackStatus', '=', 'N')->first();

        if ($hasApply) {
            $this->response['code'] = 6010;
            $this->response['message'] = '已申請退貨, 請勿重複送出（請重新整理頁面！！！）';
            return response()->json($this->response);
        }
        $createArray = [
            'orderId' => $data['orderId'],
            'orderNumber' => $orderNumber,
            'rebackStatus' => 'N',
            'reason' => $data['reason'],
        ];

        if (array_key_exists('rebackAll', $data)) {
            $createArray['rebackAll'] = $data['rebackAll'];
            $orderGoodsIndo = json_decode($order->goodsIndo);
            $goodsArray = array();

            foreach ($orderGoodsIndo as $key => $list) {
                $goods = Goods::where('id', $list->id)->select('name')->get()->first();

                array_push($goodsArray,
                    array(
                        'count'=>$list->count,
                        'id'=>$list->id,
                        'amount'=> $list->amount,
                        'name'=>$goods->name,
                    )
                );
            }

            $createArray['goodsIndo'] = json_encode($goodsArray);
        }
        if (array_key_exists('goodsIndo', $data)) {
            // 訂單內容比對
            $orderGoodsIndo = json_decode($order->goodsIndo);
            $mapOrderAmount = array_pluck($orderGoodsIndo, 'amount', 'id');
            $mapOrderCount = array_pluck($orderGoodsIndo, 'count', 'id');

            
            $goodsIndo = json_decode($data['goodsIndo']);
            $isAllBack = true;  // 確認是否全部退貨 (預設先為 ture)

            // 如果訂單以及貨品商品種類數量不相同
            if (count($goodsIndo) !== count($orderGoodsIndo)) {
                $isAllBack = false;
            }
            $goodsArray = array();
            
            foreach ($goodsIndo as $key => $list) {
                $amount = $mapOrderAmount[$list->id];
                $count = $mapOrderCount[$list->id];
                $goods = Goods::where('id', $list->id)->select('name')->get()->first();

                // 如果還是 ture 才繼續比對
                if ($isAllBack) {
                    // 如果寫的數量不同則 false
                    if ($count !== (int)$list->count) {
                        $isAllBack = false;
                    }
                }
                if ($count < (int)$list->count) {
                    $this->response['code'] = 6011;
                    $this->response['message'] = '申請失敗, 退貨內容多於訂貨內容資料';

                    return response()->json($this->response);
                }

                array_push($goodsArray,
                    array(
                        'count'=>$list->count,
                        'id'=>$list->id,
                        'amount'=> $amount,
                        'name'=>$goods->name,
                    )
                );
            }
            if ($isAllBack) {
                $createArray['rebackAll'] = 'Y';
            }
            $createArray['goodsIndo'] = json_encode($goodsArray);
        }

        $Create=RebackOrder::create($createArray);

        if ($Create) {
            $this->response['message'] = '申請完成:' . $orderNumber ;
            $originMemo = $order->memo;
            $newMemo = '<p><span>' . $this->timeNow . '</span>:[申請退貨] '. $data['reason'] .'</p>';
            
            try {
                $order->update(['status' => 'R', 'memo' => $originMemo . $newMemo]);
                $queryStatus = true;
            } catch(Exception $e) {
                $queryStatus = false;
            }
        } else {
            $this->response['code'] = 6002;
            $this->response['message'] = '建立失敗';
        }

        return response()->json($this->response);
    }

    /**
     * 回傳全部申請資料
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');
        $orderNumber = $request->input('orderNumber');
        $rebackStatus = $request->input('rebackStatus');

        $data = array(
            'orderNumber' => $orderNumber ? $orderNumber : ''
        );

        $data = $request->all();
        $validator = Validator::make($data, [
            'orderNumber' => ['max:10']
        ],[
            'orderNumber.max' => '退貨訂單號：請勿輸入超過10字元',
        ]);

        if ($validator->fails()) {
            $error = $validator->errors()->first();
            $this->response['code'] = 60021;
            $this->response['message'] = $error;
            return response()->json($this->response);
        }

        $list = RebackOrder::orderBy('id', 'desc')
        ->when($startDate, function ($query) use ($startDate) {
            return $query->whereDate('created_at', '>=', $startDate);
        })
        ->when($endDate, function ($query) use ($endDate) {
            return $query->whereDate('created_at', '<=', $endDate);
        })
        ->when($orderNumber, function ($query) use ($orderNumber) {
            return $query->where('orderNumber', 'like', '%' . $orderNumber. '%');
        })
        ->when($rebackStatus, function ($query) use ($rebackStatus) {
            return $query->where('rebackStatus', $rebackStatus);
        })->get()->all();

        $this->response['data'] = $list;
        return response()->json($this->response);
    }
    /**
     * 回傳全部申請資料
     * @return \Illuminate\Http\Response
     */
    public function showSingle(Request $request)
    {
        $data = $request->all();
        $list = RebackOrder::where('orderNumber', $data['orderNumber'])->get()->first();
        $this->response['data'] = $list;
        return response()->json($this->response);
    }
    /**
     * 後台取消申請
     */
    public function cancel(Request $request)
    {
        $data = $request->all();
        $validator = Validator::make($data, [
            'id' => ['required', 'numeric'],
            'memo' => ['required', 'string', 'max:256'],
            'lock' => ['string', 'max:1'],
        ],[
            'id.required' => '申請訂單id有誤, 請聯繫客服',
            'id.numeric' => '申請訂單id有誤, 請聯繫客服',
            'memo.required' => '務必填寫取消申請原因',
            'memo.string' => '備註資訊有誤',
            'memo.max' => '取消原因請填寫不超過256字',
            'lock' => '強制取消, 狀態有誤'
        ]);

        if ($validator->fails()) {
            $error = $validator->errors()->first();
            $this->response['code'] = 6003;
            $this->response['message'] = $error;
            return response()->json($this->response);
        }
        $applyOrder = RebackOrder::where('id', $data['id'])->first();   // 退貨申請表單
        $editStatus = $this->edit($applyOrder, 
            [
                'rebackStatus' => 'C',
                'memo' => $data['memo']
            ]
        );

        if ($editStatus) {
            $order = $this->findOrder($applyOrder->orderId)->first();   // 退貨資料原訂單
            $originMemo = $order->memo;
            $newMemo = '<p><span>'. $this->timeNow . '</span>:[客服取消申請] '. $data['memo'] .'</p>';
            if (array_key_exists('lock',$data) && $data['lock'] === 'Y') {
                $order->update(['status'=> 'L']);
            } else {
                $order->update(['status'=> 'E']);
            }
            
            $order->update(['memo'=>$originMemo . $newMemo ]);
        }

        return response()->json($this->response);
    }
    /**
     * 申請成功
     */
    public function cross(Request $request)
    {
        $data = $request->all();
        $validator = Validator::make($data, [
            'id' => ['required', 'numeric'],
            'memo' => ['required', 'string', 'max:256']
        ],[
            'id.required' => '申請訂單id有誤, 請聯繫客服',
            'id.numeric' => '申請訂單id有誤, 請聯繫客服',
            'memo.required' => '務必填寫取消申請原因',
            'memo.string' => '備註資訊有誤',
            'memo.max' => '取消原因請填寫不超過256字'
        ]);
        if ($validator->fails()) {
            $error = $validator->errors()->first();
            $this->response['code'] = 6003;
            $this->response['message'] = $error;
            return response()->json($this->response);
        }
        $applyOrder = RebackOrder::where('id', $data['id'])->first();
        $editStatus = $this->edit($applyOrder, 
            [
                'rebackStatus' => 'Y',
                'memo' => $data['memo']
            ]
        );

        if ($editStatus) {
            $order = $this->findOrder($applyOrder->orderId)->first();
            $originMemo = $order->memo;
            $newMemo = '<p><span>'. $this->timeNow . '</span>:[申請通過] '. $data['memo'] .'</p>';
            $order->update(['status'=> 'T']);
            $order->update(['memo'=>$originMemo . $newMemo ]);
        }

        return response()->json($this->response);
    }
    /**
     * 編輯資料方法
     *
     * @param Object $applyOrder
     * @param Array $setArray
     * @return \Illuminate\Http\Response
     */
    public function edit($applyOrder, $setArray = [])
    {
        try {
            $applyOrder->update($setArray);
            $queryStatus = true;
        } catch(Exception $e) {
            $queryStatus = false;
            $this->response['code'] = '6004';
            $this->response['message'] = '更新失敗';
        }
        
        return $queryStatus;
    }

    /**
     * 退貨完成
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function finish(Request $request)
    {
        $data = $request->all();
        $validator = Validator::make($data, [
            'id' => ['required', 'numeric'],
            'memo' => ['string', 'max:256']
        ],[
            'id.required' => '申請訂單id有誤, 請聯繫客服',
            'id.numeric' => '申請訂單id有誤, 請聯繫客服',
            'memo.string' => '備註資訊有誤',
            'memo.max' => '取消原因請填寫不超過256字'
        ]);

        if ($validator->fails()) {
            $error = $validator->errors()->first();
            $this->response['code'] = 6003;
            $this->response['message'] = $error;
            return response()->json($this->response);
        }
        $applyOrder = RebackOrder::where('id', $data['id'])->first();   // 申請退貨單
        $order = $this->findOrder($applyOrder->orderId)->first();   // 原訂購單
        $user = Users::where('name', $order['account'])->first();   // 訂購用戶資訊
        $orderTotalAmount = $order['totalAmount'];  // 總退貨金額
        $nowCoupon = 0;     // 產生購物金
        $cost = 0;          // 使用者總花費
        $queryStatus = false;   // db是否成功
        $userCost = $user->cost; // 用戶消費總額
        $userCoupon = $user->coupon; // 用戶購物金

        // 全部退貨
        if ($applyOrder->rebackAll === 'Y') {
            $nowCoupon  = $orderTotalAmount;

            if (empty($user)) {
                $this->response['code'] = 6015;
                $this->response['msg'] = '此用戶不存在';
                return response()->json($this->response);
            }

            $cost = (int) $userCost - (int) $orderTotalAmount;
            $coupon = (int) $userCoupon + (int) $orderTotalAmount;
        }
        // 部份退貨
        if ($applyOrder->rebackAll === 'N') {
            $orderGoodsIndo= json_decode($order->goodsIndo);
            $rebackGoodsIndo = json_decode($applyOrder->goodsIndo);

            if (empty($rebackGoodsIndo)) {
                $this->response['code'] = 6016;
                $this->response['msg'] = '無退貨商品';
                return response()->json($this->response);
            }

            foreach ($orderGoodsIndo as $Okey => $orderItem) {
                foreach ($rebackGoodsIndo as $key => $rebackItem) {
                    if ($orderItem->id !== $rebackItem->id) {
                        break;
                    }
                    if ((int) $rebackItem->count > (int) $orderItem->count) {
                        $this->response['code'] = 6017;
                        $this->response['msg'] = '退貨數量異常';
                        return response()->json($this->response);
                    }
                    if ((int) $rebackItem->count === (int) $orderItem->count) {
                        array_splice($orderGoodsIndo, $Okey, 1);
                    } else {
                        $orderGoodsIndo[$Okey]->count = (int) $orderItem->count - (int) $rebackItem->count;
                    }
                }
            }
            // 產生購物資訊
            $goods = $this->goodsMethod->shopCarData($request, true, $orderGoodsIndo, $user, $order);
            $nowCoupon = $orderTotalAmount - $goods['totalAmount'];
            $cost = (int) $userCost - (int) $nowCoupon;
            $coupon = (int) $userCoupon + (int) $nowCoupon;

        }

        // 更新用戶-消費以及購物金
        try {
            $user->update([
                'cost'=> $cost,
                'coupon'=> $coupon
            ]);
            $queryStatus = true;
        } catch(Exception $e) {
            $queryStatus = false;
        }

        if ($queryStatus) {
            $editStatus = $this->edit($applyOrder, [
                'rebackStatus' => 'F',
            ]);

            if ($editStatus) {
                $order = $this->findOrder($applyOrder->orderId)->first();
                if (array_key_exists('mome', $data)) {
                    $originMemo = $order->memo;
                    $newMemo = '<p><span>'. $this->timeNow . '</span>:[退貨完成備註] '. $data['memo'] .'</p>';
                    $order->update(['memo'=>$originMemo . $newMemo ]);
                }

                $order->update(['status'=> 'F']);
            }
            // 退貨成功訊息
            $this->usersMethod->setMessage($user->id, '<span>'. $this->timeNow .'</span> - ' . '退貨成功, 購物金' . $nowCoupon . '已入帳', $nowCoupon);
            $this->usersMethod->setCoupon($user->id, '<span>'. $this->timeNow .'</span> - ' . '退貨【'. $order->orderNumber .'】, 購物金' . $nowCoupon . '已入帳');
        
            // 層級調整
            $this->levelChange($cost, $order, $user);
        }

        return response()->json($this->response);
    }
    // 層級更換
    public function levelChange($userCost, $orderData, $user) {
        $hasUpLevel = Level::having('upgradeAmount', '<', $userCost)->orderBy('upgradeAmount', 'desc');
        $lastUpLevel = '';

        if ($hasUpLevel) {
            $lastUpLevel = $hasUpLevel->get()->first();
        }
        $lastLevelId = (int) $lastUpLevel['id'];
        $currenyLevelId = (int) $user->level;

        if ($lastLevelId !== $currenyLevelId) {
            $user->update([
                'level' => $lastUpLevel['id']
            ]);
            // 用於層級移除或是意外狀況, 否則退貨應只有降級
            if ($lastLevelId > (int) $currenyLevelId) {
                $this->response['message'] = $orderData['account'] . '：用戶晉升至【'. $lastUpLevel['levelName'] . '】';
                $this->usersMethod->setMessage($user->id, '<span>'. $this->timeNow .'</span> - ' . '恭喜晉升至【'. $lastUpLevel['levelName'] . '】');
            } else {
                $this->response['message'] = $orderData['account'] . '：用戶降級至【'. $lastUpLevel['levelName'] . '】';
                $this->usersMethod->setMessage($user->id, '<span>'. $this->timeNow .'</span> - ' . '降級至【'. $lastUpLevel['levelName'] . '】');
            }
        }
    }
}
