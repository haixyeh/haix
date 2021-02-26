<?php

namespace App\Http\Controllers;

use App\Order;
use App\RebackOrder;
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
    // 搜尋訂單
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
        if ($order->status !== 'E') {
            $this->response['code'] = 6005;
            $this->response['message'] = '訂單不是完成中訂單';
            return response()->json($this->response);
        }
        if (!array_key_exists('goodsIndo', $data)) {
            $data['goodsIndo'] = '';
        }

        $orderNumber = $order->orderNumber;
        $hasApply = RebackOrder::where('orderNumber', $orderNumber)->having('rebackStatus', '=', 'N')->first();

        if ($hasApply) {
            $this->response['code'] = 6010;
            $this->response['message'] = '已申請退貨, 請勿重複送出（請重新整理頁面！！！）';
            return response()->json($this->response);
        }
        $Create=RebackOrder::create([
            'orderId' => $data['orderId'],
            'rebackAll' => $data['rebackAll'],
            'orderNumber' => $orderNumber,
            'rebackStatus' => 'N',
            'reason' => $data['reason'],
            'goodsIndo' => $data['goodsIndo'] ? json_encode($data['goodsIndo']) : ''
        ]);

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
    public function show()
    {
        $list = RebackOrder::orderBy('id', 'desc')->get()->all();
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
                'rebackStatus' => 'C',
                'memo' => $data['memo']
            ]
        );

        if ($editStatus) {
            $order = $this->findOrder($applyOrder->orderId)->first();
            $originMemo = $order->memo;
            $newMemo = '<p><span>'. $this->timeNow . '</span>:[客服取消申請] '. $data['memo'] .'</p>';
            $order->update(['status'=> 'E']);
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
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
