<?php
declare(strict_types=1);

namespace app\controller;

use app\BaseController;
use app\model\SystemConfig;
use app\model\User;
use think\facade\View;

/**
 * 控制器公共基类：注入登录用户与站点配置到视图
 */
abstract class Base extends BaseController
{
    protected ?User $currentUser = null;

    protected function initialize(): void
    {
        $userId = $this->request->session('user_id');
        if ($userId) {
            $this->currentUser = User::find($userId);
        }

        $system = SystemConfig::groupValues('system');
        $siteName = $system['site_name'] ?? '逐梦记工';

        View::assign([
            'currentUser' => $this->currentUser,
            'siteName'    => $siteName,
        ]);
    }
}
