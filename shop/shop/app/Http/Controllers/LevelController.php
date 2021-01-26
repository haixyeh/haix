<?php

namespace App\Http\Controllers;

use App\Level;
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
    // 查詢所有層級資料(數據)
    public function getAllLevel()
    {
        $levelAll = Level::get()->all();

        return $levelAll;
    }

    // 層級優惠金額(數據)
    public function promsPrice($levelId, $totalAmount) {
        $levelList = $this->mapLevelLast($levelId);
        $price = $this->priceData($levelList, $totalAmount);
        $result = array(
            'price'=> $price,
            'levelName'=>$levelList['levelName']
        );
        return $result;
    }

    // 層級優惠金額(數據)
    public function priceData($levelList, $totalAmount) {
        $toFullPrice = 0; // 至滿額金額(尚差額度)
        $result = array(
            'isHasProms' => 'N',
            'promsPrice' => 0,
            'finalPrice' => 0,
            'toFullPrice' => 0
        );

        if (empty($levelList) || $levelList['offer'] !== 'Y' || $totalAmount <= $levelList['full']) {
            if ($levelList['offerType'] === 'A') {
                $result['toFullPrice'] = $levelList['full'] - $totalAmount;
                $result['finalPrice'] = $totalAmount;
            }
            return $result;
        }
        $promsPrice = 0;
        switch ($levelList['offerType']) {
            case 'A':
                $promsPrice =  $levelList['discount'];
                break;
            case 'B':
                $promsPrice =  $totalAmount * ($levelList['present']/100);
                break;
            default:
                # code...
                break;
        }
        $result['isHasProms'] = $promsPrice > 0 ? 'Y' : 'N';
        $result['promsPrice'] = $promsPrice;
        $result['finalPrice'] = $totalAmount - $promsPrice;

        return $result;
    }

    // 對應最低層級(數據)
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
            // 若層級找不到, 給最後符合層級
            if (!$alreadyFindId && $item['id'] <= (int)$levelId) {
                $levelList = $item;
                $temp = $item['id'];
            }
        }
        return $levelList;
    }

    // 查詢所有層級
    public function show()
    {
        $levelAll = $this->getAllLevel();
        $data = $this->success($levelAll);

        return $data;
    }

    // 新增層級
    public function addLevel(Request $request)
    {
        $data = $request->all();
        $validator = Validator::make($data, [
            'levelName' => ['required', 'string', 'min:1', 'max:30'],
            'offer' => ['required', 'string', 'max:1'],
            'offerType' => ['string', 'max:1'],
            'full' => ['numeric'],
            'discount' => ['numeric'],
            'present' => ['numeric', 'max:2']
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

            $this->response['message'] = $error;
            return response()->json($this->response);
        }

        $Create=Level::create([
            'levelName' =>$data['levelName'],
            'offer' =>$data['offer'],
            'offerType' =>array_key_exists('offerType' , $data) ? $data['offerType'] : null,
            'full' =>array_key_exists('full' , $data) ? $data['full'] : null,
            'discount' =>array_key_exists('discount' , $data) ? $data['discount'] : null,
            'present' =>array_key_exists('present' , $data) ? $data['present'] : null,
        ]);
        
        if ($Create) {
            $this->response['message'] = '建立成功';
        } else {
            $this->response['message'] = '建立失敗';
        }

        return response()->json($this->response);
            
    }
}
