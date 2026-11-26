<?php
declare(strict_types=1);

namespace app\controller;

use app\model\Project;
use app\model\WorkRecord;
use think\facade\View;

/**
 * 个人中心（只读展示）
 */
class Profile extends Base
{
    public function index()
    {
        $userId = (int) $this->currentUser->id;
        $projectIds = Project::where('user_id', $userId)->column('id');

        View::assign([
            'activeMenu'    => 'profile',
            'activeCount'   => Project::where('user_id', $userId)->where('status', Project::STATUS_ACTIVE)->count(),
            'historyCount'  => Project::where('user_id', $userId)->where('status', Project::STATUS_HISTORY)->count(),
            'totalWorkDays' => $projectIds ? (float) WorkRecord::whereIn('project_id', $projectIds)->sum('work_days') : 0,
            'totalOvertime' => $projectIds ? (float) WorkRecord::whereIn('project_id', $projectIds)->sum('overtime_hours') : 0,
        ]);
        return View::fetch('profile/index');
    }
}
