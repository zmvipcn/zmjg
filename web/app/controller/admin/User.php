<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\controller\Base;
use app\model\Project;
use app\model\User as UserModel;
use app\model\WorkRecord;
use think\facade\Db;
use think\facade\View;
use think\response\Redirect;

/**
 * 用户管理（仅管理员；权限设置仅超级管理员）
 */
class User extends Base
{
    public function index()
    {
        $users = UserModel::order('id', 'asc')->paginate(20);
        View::assign([
            'activeMenu' => 'admin_user',
            'users'      => $users,
        ]);
        return View::fetch('admin/user/index');
    }

    /**
     * 创建用户（需验证管理员密码）
     */
    public function create(): Redirect
    {
        $verify = $this->verifyAdminPassword();
        if ($verify !== true) {
            return $verify;
        }

        $realName = trim((string) $this->request->post('real_name', ''));
        if ($realName === '') {
            return $this->back('请填写用户姓名/昵称');
        }

        $role = (string) $this->request->post('role', UserModel::ROLE_USER);
        // 只有超级管理员可以设置管理员角色，普通管理员创建的一律为普通用户
        if (!$this->currentUser->isSuper()) {
            $role = UserModel::ROLE_USER;
        }
        if (!in_array($role, [UserModel::ROLE_ADMIN, UserModel::ROLE_USER], true)) {
            $role = UserModel::ROLE_USER;
        }

        $newId = UserModel::nextId();
        $defaultPassword = UserModel::defaultPassword($newId);

        UserModel::create([
            'id'          => $newId,
            'real_name'   => mb_substr($realName, 0, 50),
            'role'        => $role,
            'password'    => password_hash($defaultPassword, PASSWORD_DEFAULT),
            'phone'       => mb_substr(trim((string) $this->request->post('phone', '')), 0, 30),
            'email'       => mb_substr(trim((string) $this->request->post('email', '')), 0, 100),
            'bio'         => '',
            'signature'   => '',
            'avatar'      => '',
            'status'      => 1,
            'create_time' => date('Y-m-d H:i:s'),
            'update_time' => date('Y-m-d H:i:s'),
        ]);

        return $this->back("用户创建成功：ID {$newId}，初始密码 {$defaultPassword}（请通知用户首次登录后修改）", 'success');
    }

    /**
     * 删除用户（需验证管理员密码；用户全部数据一并删除，ID释放可复用）
     */
    public function delete($id): Redirect
    {
        $verify = $this->verifyAdminPassword();
        if ($verify !== true) {
            return $verify;
        }

        $targetId = (int) $id;
        if ($targetId === 1000) {
            return $this->back('超级管理员（ID 1000）不可删除');
        }
        if ($targetId === (int) $this->currentUser->id) {
            return $this->back('不能删除当前登录账号');
        }

        $target = UserModel::find($targetId);
        if (!$target) {
            return $this->back('用户不存在');
        }

        Db::transaction(function () use ($targetId) {
            $projectIds = Project::where('user_id', $targetId)->column('id');
            if ($projectIds) {
                WorkRecord::whereIn('project_id', $projectIds)->delete();
                Project::where('user_id', $targetId)->delete();
            }
            UserModel::destroy($targetId);
        });

        return $this->back("用户 {$targetId} 及其全部项目、工天数据已删除，ID 已释放可复用", 'success');
    }

    /**
     * 设置用户角色/权限（仅超级管理员）
     */
    public function setRole($id): Redirect
    {
        if (!$this->currentUser->isSuper()) {
            return $this->back('只有超级管理员可以设置用户权限');
        }
        $target = UserModel::find((int) $id);
        if (!$target) {
            return $this->back('用户不存在');
        }
        if ((int) $target->id === 1000) {
            return $this->back('超级管理员（ID 1000）的角色不可修改');
        }

        $role = (string) $this->request->post('role', UserModel::ROLE_USER);
        if (!in_array($role, [UserModel::ROLE_ADMIN, UserModel::ROLE_USER], true)) {
            return $this->back('角色不合法');
        }
        $target->save(['role' => $role, 'update_time' => date('Y-m-d H:i:s')]);
        return $this->back('用户权限已更新', 'success');
    }

    /**
     * 校验提交的管理员密码
     * @return true|Redirect
     */
    private function verifyAdminPassword()
    {
        $adminPassword = (string) $this->request->post('admin_password', '');
        if ($adminPassword === '' || !password_verify($adminPassword, (string) $this->currentUser->password)) {
            return $this->back('管理员密码验证失败，操作已取消');
        }
        return true;
    }

    private function back(string $message, string $type = 'error'): Redirect
    {
        return redirect((string) url('/admin/user'))->with($type, $message);
    }
}
