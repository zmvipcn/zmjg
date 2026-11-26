<?php
declare(strict_types=1);

namespace app\controller;

use app\model\Project;
use app\model\WorkRecord;
use think\facade\View;

/**
 * 前台首页
 */
class Index extends Base
{
    public function index()
    {
        $userId = (int) $this->currentUser->id;

        $activeProjects = Project::where('user_id', $userId)
            ->where('status', Project::STATUS_ACTIVE)
            ->order('is_default', 'desc')
            ->order('id', 'asc')
            ->select();

        $projectIds = Project::where('user_id', $userId)->column('id');
        $totalWorkDays = $projectIds ? (float) WorkRecord::whereIn('project_id', $projectIds)->sum('work_days') : 0;
        $totalOvertime = $projectIds ? (float) WorkRecord::whereIn('project_id', $projectIds)->sum('overtime_hours') : 0;
        $historyCount = Project::where('user_id', $userId)->where('status', Project::STATUS_HISTORY)->count();

        $recentRecords = WorkRecord::where('user_id', $userId)
            ->order('record_date', 'desc')
            ->order('id', 'desc')
            ->limit(8)
            ->select();

        $projectMap = Project::whereIn('id', $projectIds)->column('name', 'id');

        View::assign([
            'activeMenu'     => 'home',
            'activeProjects' => $activeProjects,
            'historyCount'   => $historyCount,
            'totalWorkDays'  => $totalWorkDays,
            'totalOvertime'  => $totalOvertime,
            'recentRecords'  => $recentRecords,
            'projectMap'     => $projectMap,
        ]);
        return View::fetch('index/index');
    }
}
