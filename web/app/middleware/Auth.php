<?php
declare(strict_types=1);

namespace app\middleware;

use think\Request;
use think\Response;

/**
 * 前台登录校验：未登录一律跳转登录页
 */
class Auth
{
    public function handle(Request $request, \Closure $next): Response
    {
        if (!$request->session('user_id')) {
            return redirect((string) url('/login'));
        }
        return $next($request);
    }
}
