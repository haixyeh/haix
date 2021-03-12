<?php

namespace App\Http\Controllers;

use App\Level;
use App\Users;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LevelController extends Controller
{
    /**
     * self
     *
     * @var Lang
     */
    private static $self;
    private $response;
    /**
     * MenuController constructor.
     */
    public function __construct()
    {
        $this->response = $this->normalOutput();
    }
    /**
     * 初始化
    */
    public static function instance()
    {
        if (!isset(self::$self)) {
            self::$self = new self();
        }
        return self::$self;
    }
    // 查詢所有等級資料(數據)
    public function getAllLevel()
    {
        $levelAll = Level::get()->all();

        return $levelAll;
    }

    // 等級優惠金額(數據)
    public function promsPrice($levelId, $totalAmount, $order = null) {
        if (empty($order)) {
            $levelList = $this->mapLevelLast($levelId);
        } else {
            $levelList = json_decode($order->currentProms);
        }

        $price = $this->priceData($levelList, $totalAmount);

        $result = array(
            'price'=> $price,
            'levelName'=>$order ?  null : $levelList['levelName']
        );
        return $result;
    }

    // 等級優惠金額(數據)
    public function priceData($levelList, $totalAmount) {
        $toFullPrice = 0; // 至滿額金額(尚差額度)
        $result = array(
            'isHasProms' => 'N',
            'promsPrice' => 0,
            'finalPrice' => 99999,
            'toFullPrice' => 0
        );

        // 等級尚未設定優惠
        if (empty($levelList) || $levelList->offer !== 'Y' || $totalAmount < $levelList->full) {
            if ($levelList->offerType === 'A') {
                $result['toFullPrice'] = $levelList->full - $totalAmount;
            }
            $result['finalPrice'] = $totalAmount;

            return $result;
        }

        // 總結計算優惠金額
        $promsPrice = 0;
        switch ($levelList->offerType) {
            case 'A':
                if ($totalAmount >= $levelList->full) {
                    $countsDiscount = floor($totalAmount / $levelList->full);
                } else {
                    $countsDiscount = 0;
                }
                
                $promsPrice =  $levelList->discount * $countsDiscount;
                break;
            case 'B':
                $promsPrice = (float) floor($totalAmount * ($levelList->present)/100);
                break;
            default:
                $promsPrice = 0;
                break;
        }

        $result['isHasProms'] = $promsPrice > 0 ? 'Y' : 'N';
        $result['promsPrice'] = $promsPrice;
        $result['finalPrice'] = $totalAmount - $promsPrice;

        return $result;
    }

    // 對應最低等級(數據)
    public function mapLevelLast($levelId)
    {
        $allLevel = $this->getAllLevel();
        $levelList = array();
        $alreadyFindId = false;
        $temp = 0;
        foreach ($allLevel as $key => $item) {
            if ($item['id'] === (int)$levelId) {
                $levelList = $item;
                $alreadyFindId = true;
            }
            // 若等級找不到, 給最後符合等級
            if (!$alreadyFindId && $item['id'] <= (int)$levelId) {
                $levelList = $item;
                $temp = $item['id'];
            }
        }
        return $levelList;
    }

    // 查詢所有等級
    public function show()
    {
        $levelAll = $this->getAllLevel();
        $data = $this->success($levelAll);

        return $data;
    }

    // 新增等級
    public function addLevel(Request $request)
    {
        $data = $request->all();
        $validator = Validator::make($data, [
            'levelName' => ['required', 'string', 'min:1', 'max:30'],
            'upgradeAmount' => ['required', 'numeric'],
            'offer' => ['required', 'string', 'max:1'],
            'offerType' => ['string', 'max:1'],
            'full' => ['numeric'],
            'discount' => ['numeric'],
            'present' => ['numeric', 'max:99'],
        ],[
            'levelName.required' => '請填寫等級名稱',
            'levelName.max' => '等級名稱請勿設定超過30字',
            'upgradeAmount.required' => '請輸入晉級金額',
            'offer.required' => '請選擇是否設定優惠',
            'offer.max' => '是否設定優惠, 欄位有誤',
            'full.numeric' => '單比滿額： 請輸入正整數',
            'discount.numeric' => '折扣金額： 請輸入正整數',
            'present.numeric' => '請輸入正整數',
            'present.max' => '折扣％數, 設定請勿超過100%',
        ]);

        if ($validator->fails()) {
            $error = $validator->errors()->first();
            $this->response['code'] = 399012;
            $this->response['message'] = $error;
            return response()->json($this->response);
        }

        $levelLast = Level::orderBy('id', 'desc')->first();

        if ($data['upgradeAmount'] <= $levelLast['upgradeAmount']) {
            $this->response['code'] = 399011;
            $this->response['message'] = '等級晉級金額: 晉級金額請高於〔'. $levelLast['levelName'] .'〕';
            return response()->json($this->response);
        }

        $Create=Level::create([
            'levelName' =>$data['levelName'],
            'offer' =>$data['offer'],
            'offerType' =>array_key_exists('offerType' , $data) ? $data['offerType'] : null,
            'full' =>array_key_exists('full' , $data) ? $data['full'] : null,
            'discount' =>array_key_exists('discount' , $data) ? $data['discount'] : null,
            'present' =>array_key_exists('present' , $data) ? $data['present'] : null,
            'upgradeAmount'=>$data['upgradeAmount'],
        ]);
        
        if ($Create) {
            $this->response['message'] = '建立成功';
        } else {
            $this->response['code'] = 5003;
            $this->response['message'] = '建立失敗';
        }

        return response()->json($this->response);
            
    }
    public function previousLevel($id) {
        $level = Level::orderBy('id', 'desc')
                    ->having('id', '<', $id)
                    ->take(1)
                    ->get();
        return $level;
    }
    // 編輯等級(不可修改等級晉級金額)
    public function edit(Request $request) {
        $data = $request->all();
        $validator = Validator::make($data, [
            'levelName' => ['required', 'string', 'min:1', 'max:30'],
            'offer' => ['required', 'string', 'max:1'],
            'offerType' => ['string', 'max:1'],
            'full' => ['numeric'],
            'discount' => ['numeric'],
            'present' => ['numeric', 'max:99'],
        ],[
            'levelName.required' => '請填寫等級名稱',
            'levelName.max' => '等級名稱請勿設定超過30字',
            'offer.required' => '請選擇是否設定優惠',
            'offer.max' => '是否設定優惠, 欄位有誤',
            'full.numeric' => '單比滿額： 請輸入正整數',
            'discount.numeric' => '折扣金額： 請輸入正整數',
            'present.numeric' => '請輸入正整數',
            'present.max' => '折扣％數, 設定請勿超過100%',
        ]);

        if ($validator->fails()) {
            $error = $validator->errors()->first();
            $this->response['code'] = 399012;
            $this->response['message'] = $error;
            return response()->json($this->response);
        }
        if (array_key_exists('full' , $data) && array_key_exists('discount' , $data)) {
            if (floor($data['discount']/$data['full']) >= 1) {
                $this->response['code'] = 5005;
                $this->response['message'] = '[折扣金額]請低於「單筆滿額金額」';
                return response()->json($this->response);
            }
        }

        $LevelUpdate=Level::where('id', $data['id'])->update([
            'levelName' =>$data['levelName'],
            'offer' =>$data['offer'],
            'offerType' =>array_key_exists('offerType' , $data) ? $data['offerType'] : null,
            'full' =>array_key_exists('full' , $data) ? $data['full'] : null,
            'discount' =>array_key_exists('discount' , $data) ? $data['discount'] : null,
            'present' =>array_key_exists('present' , $data) ? $data['present'] : null
        ]);
        
        if ($LevelUpdate) {
            $this->response['message'] = '更新成功';
        } else {
            $this->response['code'] = 5004;
            $this->response['message'] = '更新失敗';
        }

        return response()->json($this->response);
    }

    // 刪除
    public function destroy(Request $request,int $id) {
        $levelAll = Level::orderBy('id', 'desc')->get();
        $levelLast = $levelAll[0];
        $levelLast2 = $levelAll[1];

        if ($id === 0) {
            $this->response['code'] = 5003;
            $this->response['message'] = '不能刪除剩於等級';
            return response()->json($this->response);
        }

        if ($levelLast['id'] !== $id) {
            $this->response['code'] = 5002;
            $this->response['message'] = '等級只能刪除最後一層';
            return response()->json($this->response);
        }
        $level = Level::where('id',$id);

        try {
            $level && $level -> delete();
            $queryStatus = true;
        } catch(Exception $e) {
            $queryStatus = false;
        }

        if (!$queryStatus) {
            $this->response['code'] = 5001;
            $this->response['message'] = '未成功刪除, 請洽客服';
        }

        $user = Users::where('level', $id)->update([
            'level' => $levelLast2['id']
        ]);
        

        return response()->json($this->response);
    }
}
