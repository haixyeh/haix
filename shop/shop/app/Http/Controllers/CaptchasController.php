<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Mews\Captcha\Captcha;
use Cache;

class CaptchasController extends Controller
{
    public function store(Request $request, Captcha $captcha)
    {
        $captchaInfo = $captcha->create('flat', true);
        $expiredAt = now()->addMinute(2);
        // 暫存key
        Cache::put('key', 
            // 添加验证码生成的key到缓存中
            $captchaInfo['key']
        , $expiredAt);

        $reslut = array(
            'img' => $captchaInfo['img'],
        );
        return response()->json($reslut)->withCookie(Cookie(
            'ss', $captchaInfo['key']
        ));
    }
}
