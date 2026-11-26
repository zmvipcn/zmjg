<?php
declare(strict_types=1);

namespace app\controller;

use think\facade\Filesystem;
use think\facade\View;
use think\response\Redirect;

/**
 * 个人设置：资料、密码、头像
 */
class Setting extends Base
{
    public function index()
    {
        View::assign(['activeMenu' => 'setting']);
        return View::fetch('setting/index');
    }

    /**
     * 更新基本信息
     */
    public function updateProfile(): Redirect
    {
        $user = $this->currentUser;
        $user->save([
            'real_name' => mb_substr(trim((string) $this->request->post('real_name', '')), 0, 50),
            'phone'     => mb_substr(trim((string) $this->request->post('phone', '')), 0, 30),
            'email'     => mb_substr(trim((string) $this->request->post('email', '')), 0, 100),
            'bio'       => mb_substr(trim((string) $this->request->post('bio', '')), 0, 500),
            'signature' => mb_substr(trim((string) $this->request->post('signature', '')), 0, 255),
        ]);
        return redirect((string) url('/setting'))->with('success', '个人信息已保存');
    }

    /**
     * 修改密码（需验证原密码）
     */
    public function updatePassword(): Redirect
    {
        $old = (string) $this->request->post('old_password', '');
        $new = (string) $this->request->post('new_password', '');
        $confirm = (string) $this->request->post('confirm_password', '');

        if (!password_verify($old, (string) $this->currentUser->password)) {
            return redirect((string) url('/setting'))->with('error', '原密码不正确');
        }
        if (strlen($new) < 6) {
            return redirect((string) url('/setting'))->with('error', '新密码长度不能少于6位');
        }
        if ($new !== $confirm) {
            return redirect((string) url('/setting'))->with('error', '两次输入的新密码不一致');
        }

        $this->currentUser->save(['password' => password_hash($new, PASSWORD_DEFAULT)]);
        return redirect((string) url('/setting'))->with('success', '密码修改成功，下次登录请使用新密码');
    }

    /**
     * 上传头像
     */
    public function avatar(): Redirect
    {
        $file = $this->request->file('avatar');
        if (!$file) {
            return redirect((string) url('/setting'))->with('error', '请选择要上传的头像图片');
        }
        try {
            $ext = strtolower($file->getOriginalExtension());
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
                return redirect((string) url('/setting'))->with('error', '仅支持 jpg/png/gif/webp 格式图片');
            }
            $path = Filesystem::disk('public')->putFile('avatar', $file);
            $this->currentUser->save(['avatar' => '/storage/' . str_replace('\\', '/', $path)]);
        } catch (\Throwable $e) {
            return redirect((string) url('/setting'))->with('error', '头像上传失败：' . $e->getMessage());
        }
        return redirect((string) url('/setting'))->with('success', '头像已更新');
    }
}
