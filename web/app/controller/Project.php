<?php
declare(strict_types=1);

namespace app\controller;

use app\model\Project as ProjectModel;
use app\model\WorkRecord;
use think\facade\Db;
use think\facade\View;
use think\response\Redirect;

/**
 * 前台项目管理
 */
class Project extends Base
{
    /**
     * 当前项目（进行中，最多3个）
     */
    public function current()
    {
        $userId = (int) $this->currentUser->id;
        $projects = ProjectModel::where('user_id', $userId)
            ->where('status', ProjectModel::STATUS_ACTIVE)
            ->order('is_default', 'desc')
            ->order('id', 'asc')
            ->select();

        foreach ($projects as $project) {
            $project->total_days = $project->totalWorkDays();
            $project->total_overtime = $project->totalOvertimeHours();
        }

        View::assign([
            'activeMenu'  => 'current',
            'projects'    => $projects,
            'maxReached'  => count($projects) >= ProjectModel::MAX_ACTIVE,
            'maxActive'   => ProjectModel::MAX_ACTIVE,
        ]);
        return View::fetch('project/current');
    }

    /**
     * 创建项目
     */
    public function create(): Redirect
    {
        $userId = (int) $this->currentUser->id;
        $activeCount = ProjectModel::where('user_id', $userId)
            ->where('status', ProjectModel::STATUS_ACTIVE)->count();

        if ($activeCount >= ProjectModel::MAX_ACTIVE) {
            return redirect((string) url('/project/current'))->with('error', '进行中的项目最多 ' . ProjectModel::MAX_ACTIVE . ' 个，请先结束已有项目');
        }

        $data = $this->buildProjectData();
        if ($data === null) {
            return redirect((string) url('/project/current'))->with('error', '请填写项目名称和开始日期');
        }

        Db::transaction(function () use ($data, $userId) {
            $wantDefault = (bool) $this->request->post('is_default', 0);
            $hasDefault = ProjectModel::where('user_id', $userId)
                ->where('status', ProjectModel::STATUS_ACTIVE)->where('is_default', 1)->count();

            $project = new ProjectModel();
            $project->save(array_merge($data, [
                'user_id'    => $userId,
                'status'     => ProjectModel::STATUS_ACTIVE,
                'is_default' => 0,
                'end_date'   => null,
            ]));

            // 勾选默认：取消其他默认；未勾选且当前无默认：自动成为默认（保证始终有1个默认项目）
            if ($wantDefault || !$hasDefault) {
                $this->makeDefault($project->id, $userId);
            }
        });

        return redirect((string) url('/project/current'))->with('success', '项目创建成功');
    }

    /**
     * 编辑项目
     */
    public function update($id): Redirect
    {
        $project = $this->findOwnedProject((int) $id);
        if (!$project) {
            return redirect((string) url('/project/current'))->with('error', '项目不存在或无权操作');
        }

        $data = $this->buildProjectData();
        if ($data === null) {
            return redirect((string) url('/project/current'))->with('error', '请填写项目名称和开始日期');
        }
        $project->save($data);
        return redirect((string) url('/project/current'))->with('success', '项目信息已更新');
    }

    /**
     * 设为默认项目
     */
    public function setDefault($id): Redirect
    {
        $project = $this->findOwnedProject((int) $id);
        if (!$project || $project->status != ProjectModel::STATUS_ACTIVE) {
            return redirect((string) url('/project/current'))->with('error', '项目不存在或已结束');
        }
        $this->makeDefault($project->id, (int) $this->currentUser->id);
        return redirect((string) url('/project/current'))->with('success', '已设为默认项目');
    }

    /**
     * 结束项目：转入历史项目
     */
    public function end($id): Redirect
    {
        $userId = (int) $this->currentUser->id;
        $project = $this->findOwnedProject((int) $id);
        if (!$project) {
            return redirect((string) url('/project/current'))->with('error', '项目不存在或无权操作');
        }

        Db::transaction(function () use ($project, $userId) {
            $wasDefault = $project->is_default == 1;
            $project->save(['status' => ProjectModel::STATUS_HISTORY, 'end_date' => date('Y-m-d'), 'is_default' => 0]);

            // 若结束的是默认项目，把最新的进行中项目补为默认
            if ($wasDefault) {
                $next = ProjectModel::where('user_id', $userId)
                    ->where('status', ProjectModel::STATUS_ACTIVE)
                    ->order('id', 'desc')->find();
                if ($next) {
                    $this->makeDefault($next->id, $userId);
                }
            }
        });

        return redirect((string) url('/project/current'))->with('success', '项目已结束并转入历史项目');
    }

    /**
     * 历史项目
     */
    public function history()
    {
        $userId = (int) $this->currentUser->id;
        $projects = ProjectModel::where('user_id', $userId)
            ->where('status', ProjectModel::STATUS_HISTORY)
            ->order('end_date', 'desc')
            ->order('id', 'desc')
            ->select();

        foreach ($projects as $project) {
            $project->total_days = $project->totalWorkDays();
            $project->total_overtime = $project->totalOvertimeHours();
        }

        View::assign([
            'activeMenu' => 'history',
            'projects'   => $projects,
        ]);
        return View::fetch('project/history');
    }

    /**
     * 项目内页：日历式工天记录
     */
    public function view($id)
    {
        $project = $this->findOwnedProject((int) $id);
        if (!$project) {
            return redirect((string) url('/project/current'))->with('error', '项目不存在或无权操作');
        }

        $year = (int) $this->request->param('y', date('Y'));
        $month = (int) $this->request->param('m', date('n'));
        if ($month < 1 || $month > 12) {
            $month = (int) date('n');
        }

        $firstDay = 1;
        $daysInMonth = (int) date('t', mktime(0, 0, 0, $month, 1, $year));
        // 周一为一周起点：周日(0) 转为 7
        $startOffset = (int) date('N', mktime(0, 0, 0, $month, 1, $year)) - 1;

        $records = WorkRecord::where('project_id', $project->id)
            ->whereBetweenTime('record_date', "{$year}-{$month}-01", "{$year}-{$month}-{$daysInMonth}")
            ->select()
            ->column(null, 'record_date');

        // 月份切换
        $prevMonth = $month == 1 ? 12 : $month - 1;
        $prevYear = $month == 1 ? $year - 1 : $year;
        $nextMonth = $month == 12 ? 1 : $month + 1;
        $nextYear = $month == 12 ? $year + 1 : $year;

        View::assign([
            'activeMenu'    => $project->status == ProjectModel::STATUS_ACTIVE ? 'current' : 'history',
            'project'       => $project,
            'year'          => $year,
            'month'         => $month,
            'daysInMonth'   => $daysInMonth,
            'startOffset'   => $startOffset,
            'records'       => $records,
            'prevYear'      => $prevYear,
            'prevMonth'     => $prevMonth,
            'nextYear'      => $nextYear,
            'nextMonth'     => $nextMonth,
            'totalDays'     => $project->totalWorkDays(),
            'totalOvertime' => $project->totalOvertimeHours(),
            'today'         => date('Y-m-d'),
        ]);
        return View::fetch('project/view');
    }

    /**
     * 修改日工资与加班工资系数
     */
    public function updateWage($id): Redirect
    {
        $project = $this->findOwnedProject((int) $id);
        if (!$project) {
            return redirect((string) url('/project/current'))->with('error', '项目不存在或无权操作');
        }
        $dailyWage = max(0, (float) $this->request->post('daily_wage', 0));
        $coefficient = max(0, (float) $this->request->post('overtime_coefficient', 1.5));

        $project->save([
            'daily_wage'           => $dailyWage,
            'overtime_coefficient' => $coefficient,
        ]);
        return redirect((string) url('/project/view', ['id' => $project->id]))->with('success', '工资参数已更新');
    }

    /**
     * 收集表单中的项目字段
     */
    private function buildProjectData(): ?array
    {
        $name = trim((string) $this->request->post('name', ''));
        $startDate = trim((string) $this->request->post('start_date', ''));
        if ($name === '' || $startDate === '') {
            return null;
        }
        return [
            'name'                 => mb_substr($name, 0, 100),
            'description'          => mb_substr(trim((string) $this->request->post('description', '')), 0, 500),
            'start_date'           => $startDate,
            'daily_wage'           => max(0, (float) $this->request->post('daily_wage', 0)),
            'overtime_coefficient' => max(0, (float) $this->request->post('overtime_coefficient', 1.5)),
        ];
    }

    private function findOwnedProject(int $id): ?ProjectModel
    {
        $project = ProjectModel::find($id);
        if (!$project || (int) $project->user_id !== (int) $this->currentUser->id) {
            return null;
        }
        return $project;
    }

    private function makeDefault(int $projectId, int $userId): void
    {
        ProjectModel::where('user_id', $userId)
            ->where('status', ProjectModel::STATUS_ACTIVE)
            ->update(['is_default' => 0]);
        ProjectModel::where('id', $projectId)->update(['is_default' => 1]);
    }
}
