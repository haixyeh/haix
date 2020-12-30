<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\AdminUser;
use Illuminate\Support\Facades\Cookie;

class AdminAuth
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
        $session = request()->cookie('SESSION');
        // if (!$session) {
        //     return $next($request);
        // }

        if ($session) {
            $users = AdminUser::where([
                'api_token' => $session,
            ])->first();
            if ($users && $users->name) {
                return $next($request);
            }
        }
        $cookie = Cookie::forget('SESSION');
        $result = array(
            'code'=> 1001, 'message'=>'已被登出'
        );

        return response()->json($result)->withCookie($cookie);
    }
}
