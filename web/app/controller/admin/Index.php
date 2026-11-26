<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\controller\Base;
use app\model\AppFile;
use app\model\Project;
use app\model\User;
use app\model\WorkRecord;
use think\facade\View;

/**
 * 管理后台首页
 */
class Index extends Base
{
    public function index()
    {
        View::assign([
            'activeMenu'    => 'admin_home',
            'userCount'     => User::count(),
            'projectCount'  => Project::count(),
            'activeCount'   => Project::where('status', Project::STATUS_ACTIVE)->count(),
            'recordCount'   => WorkRecord::count(),
            'fileCount'     => AppFile::count(),
        ]);
        return View::fetch('admin/index/index');
    }
}
