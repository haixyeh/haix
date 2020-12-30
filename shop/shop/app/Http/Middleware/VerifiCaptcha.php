<?php

namespace App\Http\Middleware;

use Closure;
use Cache;

class VerifiCaptcha
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $data = $request->all();
        $captchaKey = Cache::get('key');
        $key = request()->cookie('ss');

        if(!captcha_api_check($data['captcha'], $key)) {
            $result = array(
                'code'=> 1005, 'message'=>'验证码错误'
            );
    
            return response()->json($result);
        }
        return $next($request);
    }
}
