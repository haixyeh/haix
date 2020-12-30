<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
    public function normalOutput()
    {
        return array(
            'status' => 'Y',
            'code' => 200,
            'message' => '',
            'data' => array()
        );
    }

    /**
     * 成功輸出
     * @param array $data
     * @return array
     */
    public function success($data = [])
    {
        $result = [
            'status' => 'Y',
            'code' => 200,
            'message' => '',
            'data' => $data
        ];

        return response()->json($result);
    }
}
