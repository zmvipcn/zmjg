<?php
declare(strict_types=1);

namespace app\controller;

use app\model\User;
use think\facade\Session;
use think\facade\View;
use think\response\Redirect;

/**
 * 登录 / 退出
 */
class Auth extends Base
{
    /**
     * 登录页面（已登录则直接进入前台）
     */
    public function login()
    {
        if ($this->currentUser) {
            return redirect((string) url('/'));
        }
        return View::fetch('auth/login');
    }

    /**
     * 登录提交
     */
    public function doLogin(): Redirect
    {
        $account = trim((string) $this->request->post('account', ''));
        $password = (string) $this->request->post('password', '');

        if ($account === '' || $password === '') {
            return redirect((string) url('/login'))->with('error', '请输入用户ID和密码');
        }

        // 登录账号即用户ID（纯数字）
        if (!ctype_digit($account)) {
            return redirect((string) url('/login'))->with('error', '用户ID应为数字');
        }

        $user = User::find((int) $account);
        if (!$user || !password_verify($password, (string) $user->password)) {
            return redirect((string) url('/login'))->with('error', '用户ID或密码错误');
        }
        if ($user->status != 1) {
            return redirect((string) url('/login'))->with('error', '账号已被停用，请联系管理员');
        }

        Session::set('user_id', $user->id);
        $user->last_login_time = date('Y-m-d H:i:s');
        $user->last_login_ip = $this->request->ip();
        $user->save();

        // 会话固定攻击防护：登录后重新生成 session id
        Session::regenerate();

        return redirect((string) url('/'));
    }

    /**
     * 退出登录
     */
    public function logout(): Redirect
    {
        Session::clear();
        return redirect((string) url('/login'));
    }
}
