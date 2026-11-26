<?php
declare(strict_types=1);

namespace app\middleware;

use app\model\User;
use think\Request;
use think\Response;

/**
 * 后台权限校验：仅超级管理员/普通管理员可进入
 */
class AdminAuth
{
    public function handle(Request $request, \Closure $next): Response
    {
        $userId = $request->session('user_id');
        if (!$userId) {
            return redirect((string) url('/login'));
        }
        $user = User::find($userId);
        if (!$user || $user->status != 1 || !$user->isAdmin()) {
            return Response::create('无权访问管理后台，仅管理员可进入。', 'html', 403);
        }
        return $next($request);
    }
}
