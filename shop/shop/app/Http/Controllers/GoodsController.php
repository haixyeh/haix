<?php

namespace App\Http\Controllers;

use App\Goods;
use App\Http\Controllers\UserInfoController;
use App\Http\Controllers\LevelController;
use App\Http\Controllers\CategoriesController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use RedisServer;

class GoodsController extends Controller
{
    private $response;
    /**
     * MenuController constructor.
     */
    public function __construct()
    {
        $this->response = $this->normalOutput();
    }
    // /**
    //  * 初始化
    // */
    // public static function instance()
    // {
    //     if (!isset(self::$self)) {
    //         self::$self = new self();
    //     }
    //     return self::$self;
    // }
    // 全部商品
    public function showAllList() {
        $data = $this->show(true);

        return $this->success($data);
    }
    // 上架商品
    public function showStartList() {
        $data = $this->show(false);

        return $this->success($data['start']);
    }
    // 搜尋Object
    public function filterByDow($object, $id)
    {
        $index = 0;
        $hasID = false;
        foreach($object as $key => $value) {
            if ($value->id == $id) {
                $hasID = true;
                break;
            }
            $index ++;
        }

        if ($hasID) {
            return $index;
        }
        return null;
    }
    // 前台 - 查看購物車
    public function showShopCar(Request $request){ 
        return $this->success($this->shopCarData($request));
    }
    // 數據 - 購物車資料
    public function shopCarData(Request $request, $isForData = false, $goodsListData = [], $enterUser = array(), $order = null){
        if (empty($enterUser)) {
            $user = UserInfoController::instance()->UserGet($request);
        } else {
            $user = $enterUser;
        }
        $userAccount = $user->name;
        $userId = $user->id;
        $userLevel = LevelController::instance()-> mapLevelLast($user->level);

        if (empty($goodsListData)) {
            $carListData = json_decode(RedisServer::get('user'. $userId . 'car'));
        } else {
            $carListData = $goodsListData;
        }

        $levelProms = 0;
        $data = array(
            'shopList' => array(),
            'userInfo' => UserInfoController::instance()->showData($request, $enterUser),
            'totalCount' => 0,
            'totalAmount' => 99999,
            'levelProms' => $levelProms,
            'userLevel' => $userLevel
        );

        $goodsInfo = $this->goodsMoeny($carListData);
        $data['totalCount'] = $goodsInfo['totalCount'];
        $data['totalAmount'] = $goodsInfo['totalAmount'];
        $data['shopList'] = $goodsInfo['shopList'];
        if (empty($order)) {
            $levelProms = LevelController::instance()->promsPrice($user->level, $data['totalAmount']);
        } else {
            $levelProms = LevelController::instance()->promsPrice($user->level, $data['totalAmount'], $order);
        }
        $data['levelProms'] = $levelProms;

        if ($isForData) {
            $currentProms = array(
                'full'=> $userLevel['full'],
                'offerType'=> $userLevel['offerType'],
                'offer'=> $userLevel['offer'],
                'discount'=> $userLevel['discount'],
                'present'=> $userLevel['present']
            );

            $data = array(
                'goodsIndo' => $data['shopList'],
                'totalAmount' => $levelProms['price']['finalPrice'],
                'promsPrice' => $levelProms['price']['promsPrice'],
                'currentProms' => json_encode($currentProms),
                'price' => $levelProms,
                'userAccount' => $userAccount,
                'userId' => $userId
            );
        }

        return $data;
    }
    public function goodsMoeny($carListData) {
        $goodsInfo = array(
            'shopList'=> array(),
            'totalCount'=> 0,
            'totalAmount'=> 0
        );
        if (empty($carListData)) {
            return $goodsInfo;
        }

        $carListData = json_decode(json_encode($carListData));

        foreach($carListData as $key => $carItem) {
            $carItemID = $carItem->id;
            $carItemCount = $carItem->count;
            $goodsItem = $this->singleData($carItemID);
            $goodsItem['count'] = (int)$carItemCount;

            // 總計
            $goodsInfo['totalAmount'] += ($carItemCount * $goodsItem['amount']);
            $goodsInfo['totalCount'] += $carItemCount;
            array_push($goodsInfo['shopList'], $goodsItem);
        }

        return $goodsInfo;
    }
    // 前台 - 加入購物車
    public function addShopCar(Request $request, $id) {
        $data = $request->all();

        if (empty($data) || empty($data['count'])) {
            $this->response['message'] = '請輸入數量';
            $this->response['code'] = '3001';
            return response()->json($this->response);
        }

        $userId = UserInfoController::instance()->UserGet($request)->id;
        $carListData = json_decode(RedisServer::get('user'. $userId . 'car'));

        if (empty($carListData)) {
            $carListData = array();
        }

        $carItem = array(
            'id'=> $id,
            'count'=> $data['count']
        );

        $isDataIndex = $this->filterByDow($carListData, $id);

        if (is_numeric($isDataIndex)) {
            $carListData[$isDataIndex] = $carItem;
        } else {
            array_push($carListData, $carItem);
        }

        RedisServer::set('user'. $userId . 'car', json_encode($carListData));

        return $this->success(array());
    }
    // 刪除購物車項目
    public function delSingleShopCar(Request $request, $id) {
        $userId = UserInfoController::instance()->UserGet($request)->id;
        $carListData = json_decode(RedisServer::get('user'. $userId . 'car'));

        if (empty($carListData)) {
            $this->response['message'] = '無購物車項目';
            $this->response['code'] = '3010';
            return response()->json($this->response);
        }

        $newCarListData = array();

        foreach($carListData as $key => $carItem) {
            $carItemID = $carItem->id;
            if ((int) $carItemID != (int) $id ) {
                array_push($newCarListData, $carItem);
            }
            
        }

        RedisServer::set('user'. $userId . 'car', json_encode($newCarListData));
        return $this->success(array());
    }
    // 前台, 依分類排版
    function mapCategories()
    {
        $data = $this->show(false);
        $categories = new CategoriesController();
        $categoriesAll = $categories-> showAllData();
        $newCategoriesList = array();
        foreach ($data['start'] as $key => $value) {
            $type = $value['goodsType'];
            if (!array_key_exists($type, $newCategoriesList)) {
                $newCategoriesList[$type] = array();
            }
            $value['typeName'] = $categoriesAll['simple'][$type];
            array_push($newCategoriesList[$type], $value['id']);
        }

        $sentData = array(
            'goodsTypeMap'=> $newCategoriesList,
            'goodsList'=> $data['start'],
            'categoriesSimple'=> $categoriesAll['simple'],
            'categoriesInfo'=>$categoriesAll['allinfo']
        );
        return $this->success($sentData);
    }
    // 共用 - 查詢單一商品 （Data）
    public function singleData($id)
    {
        $goods = Goods::where('id',$id)->first();
        
        return $goods;
    }

    // 前台 - 查詢單一商品
    public function single(Request $request, $id)
    {
        return $this->success($this->singleData($id));
    }
    // 共用 - 查詢所有商品
    public function show($isOrderDate)
    {
        $list = null;
        if ($isOrderDate) {
            $list = Goods::orderBy('startDate')->get()->all();
        }
        if (!$isOrderDate) {
            $list = Goods::orderBy('isRecommon', 'desc')->get()->all();
        }

        $start = array();
        $unStart = array();
        $over = array();
        // 設定上架/下架資訊
        foreach($list as $key=>$value){
            $now = date("Y-m-d");
            $startDate = $value['startDate'];
            $endDate = $value['endDate'];
            // isOpen N 尚未開始、P 進行中、E 過期/下架
            switch (true) {
                case $list[$key]['forcedRemoval'] === 'Y':
                    $list[$key]['isOpen'] = 'E';
                    break;
                case strtotime($now) > strtotime($endDate):
                    $list[$key]['isOpen'] = 'E';
                    break;
                case strtotime($now) >= strtotime($startDate):
                    $list[$key]['isOpen'] = 'P';
                    break;
                case strtotime($now) < strtotime($startDate):
                    $list[$key]['isOpen'] = 'N';
                    break;
                default:
                    $list[$key]['isOpen'] = 'N';
                    break;
            }
            // 不顯示強至下架資訊
            // unset($list[$key]['forcedRemoval']);
            if ($list[$key]['isOpen'] === 'P') {
                array_push($start, $list[$key]);
            }
            if ($list[$key]['isOpen'] === 'N') {
                array_push($unStart, $list[$key]);
            }
            if ($list[$key]['isOpen'] === 'E') {
                array_push($over, $list[$key]);
            }
        }
        $newData = array(
            'start' => $start,
            'unStart' => $unStart,
            'over' => $over
        );
        // $data = $this->success($newData);

        return $newData;
    }
    // 後台, 新增or編輯 商品
    public function create(Request $request, $id = null)
    {
        $uploadIsReq = 'required';
        $isEdit = false;
        if ($id) {
            $uploadIsReq = null;
            $isEdit = true;
        }
        $data = $request->all();
        $validator = Validator::make($data, [
            'startDate' => ['required', 'date_format:Y-m-d'],
            'endDate' => ['required', 'date_format:Y-m-d'],
            'name' => ['required', 'string'],
            'info' => ['required', 'string'],
            'amount' => ['required', 'numeric'],
            'total' => ['required', 'numeric'],
            'goodsType' => ['required', 'string'],
            'isRecommon' => ['required', 'string'],
            'upload0' => [$uploadIsReq , 'mimes:jpeg,jpg,png,gif', 'max:100000'],
            'upload1' => ['sometimes', 'mimes:jpeg,jpg,png,gif', 'max:100000'],
            'upload2' => ['sometimes', 'mimes:jpeg,jpg,png,gif', 'max:100000'],
            'upload3' => ['sometimes', 'mimes:jpeg,jpg,png,gif', 'max:100000'],
            'upload4' => ['sometimes', 'mimes:jpeg,jpg,png,gif', 'max:100000'],
        ],[
            'startDate.required' => '請填寫上架時間',
            'endDate.required' => '請填寫下架時間',
            'name.required' => '請填寫密碼',
        ]);

        if ($validator->fails()) {
            $error = $validator->errors()->first();

            $this->response['message'] = $error;
            return response()->json($this->response);
        }
    
        $imagesPath = array();
        $images = array(
            'upload0', 'upload1', 'upload2', 'upload3', 'upload4'
        );
        // 圖片
        $index = 0;
        foreach($images as $k) {            
            if ($request->has($k)) {
                $path =  $this->handleImage($data[$k], $k, $index);
                array_push($imagesPath, $path);
            }
            $index++;
        }

        $updataAarray = ([
            'startDate'=>$data['startDate'],
            'endDate'=>$data['endDate'],
            'name'=>$data['name'],
            'info'=>$data['info'],
            'amount'=>$data['amount'],
            'total'=>$data['total'],
            'goodsType'=>$data['goodsType'],
            'isRecommon'=>$data['isRecommon'],
            'images'=>json_encode($imagesPath)
        ]);

        if(array_key_exists('forcedRemoval', $data)) {
            $updataAarray['forcedRemoval'] = $data['forcedRemoval'];
        }

        if ($isEdit) {
            if (!$imagesPath) {
                unset($updataAarray['images']);
            }
            $Create=Goods::where('id',$id)->update($updataAarray);
        } else {
            $Create=Goods::create($updataAarray);
        };

        if ($Create) {
            $this->response['message'] = $isEdit ? '編輯成功' : '建立成功';
        } else {
            $this->response['message'] = '建立失敗';
        }

        return response()->json($this->response);
            
    }
    // 下架商品
    public function down(Request $request, $id) {
        Goods::where('id',$id)->update([
            'forcedRemoval'=> 'Y'
        ]);
        return response()->json($this->response);
    }
    // 刪除該項商品
    public function destroy(Request $request, $id) {
        $goods = Goods::where('id',$id);
        if ($goods && $goods -> delete()) {
            $this->response['message'] = '刪除成功';
            return response()->json($this->response);
        }
        $this->response['code'] = 2001;
        $this->response['message'] = '未成功刪除';
        return response()->json($this->response);
    }
    // 處理圖片
    public function handleImage($file, $name, $index) {
        $imageName = time(). $index. '.'.request()->$name->getClientOriginalExtension();
        $file->move(public_path('images'), $imageName);

        return '/images/' . $imageName;
    }
}
