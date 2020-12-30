<?php

namespace App\Http\Controllers;
use App\Goods;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
    // 查詢所有商品
    public function show() {
        $list = Goods::get()->all();

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
            unset($list[$key]['forcedRemoval']);
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
        $data = $this->success($newData);

        return $data;
    }
    // 新增
    public function create(Request $request)
    {
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
            'upload0' => ['required', 'mimes:jpeg,jpg,png,gif', 'max:100000'],
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

            // switch (true) {
            //     case $validator->errors()->has('name'):
            //         $this->response['code'] = 1003; // 請填寫帳號
            //         break;
            //     case $validator->errors()->has('email');
            //         $this->response['code'] = 1005; // 請填寫郵件信箱
            //         break;
            //     default:
            //         # code...
            //         break;
            // }

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
            // var_dump($req\[$k]);
            if ($request->has($k)) {
                $path =  $this->handleImage($data[$k], $k);
                array_push($imagesPath, $path);
            }
            $index++;
        }

        $Create=Goods::create([
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
        
        if ($Create) {
            $this->response['message'] = '建立成功';
        } else {
            $this->response['message'] = '建立失敗';
        }

        return response()->json($this->response);
            
    }
    // 下架
    public function down(Request $request, $id) {
        Goods::where('id',$id)->update([
            'forcedRemoval'=> 'Y'
        ]);
        return response()->json($this->response);
    }
    // 刪除
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

    public function handleImage($file, $name) {
        $imageName = time().'.'.request()->$name->getClientOriginalExtension();
        $file->move(public_path('images'), $imageName);

        return '/images/' . $imageName;
    }
}
