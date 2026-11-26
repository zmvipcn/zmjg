<?php
declare(strict_types=1);

namespace app\controller;

use app\model\Project;
use app\model\WorkRecord as WorkRecordModel;
use think\response\Redirect;

/**
 * 工天记录（挂在项目下，每个项目每天一条）
 */
class WorkRecord extends Base
{
    /**
     * 新增/编辑某天的上班记录
     */
    public function save($projectId): Redirect
    {
        $userId = (int) $this->currentUser->id;
        $project = Project::find((int) $projectId);
        if (!$project || (int) $project->user_id !== $userId) {
            return redirect((string) url('/project/current'))->with('error', '项目不存在或无权操作');
        }

        $recordDate = trim((string) $this->request->post('record_date', ''));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $recordDate)) {
            return redirect((string) url('/project/view', ['id' => $project->id]))->with('error', '记录日期不合法');
        }

        $workDays = round((float) $this->request->post('work_days', 1), 1);
        $overtime = round((float) $this->request->post('overtime_hours', 0), 1);
        if ($workDays < 0 || $workDays > 1 || $overtime < 0) {
            return redirect((string) url('/project/view', ['id' => $project->id]))->with('error', '工天或加班时长不合法（工天 0~1）');
        }

        $data = [
            'work_days'       => $workDays,
            'overtime_hours'  => $overtime,
            'note'            => mb_substr(trim((string) $this->request->post('note', '')), 0, 255),
            'update_time'     => date('Y-m-d H:i:s'),
        ];

        $record = WorkRecordModel::where('project_id', $project->id)
            ->where('record_date', $recordDate)->find();
        if ($record) {
            $record->save($data);
        } else {
            WorkRecordModel::create(array_merge($data, [
                'project_id'  => $project->id,
                'user_id'     => $userId,
                'record_date' => $recordDate,
                'create_time' => date('Y-m-d H:i:s'),
            ]));
        }

        return redirect((string) url('/project/view', ['id' => $project->id, 'y' => substr($recordDate, 0, 4), 'm' => (int) substr($recordDate, 5, 2)]))
            ->with('success', '上班记录已保存');
    }

    /**
     * 删除某天的记录
     */
    public function delete($id): Redirect
    {
        $userId = (int) $this->currentUser->id;
        $record = WorkRecordModel::find((int) $id);
        if (!$record || (int) $record->user_id !== $userId) {
            return redirect((string) url('/project/current'))->with('error', '记录不存在或无权操作');
        }
        $projectId = $record->project_id;
        $recordDate = $record->record_date;
        $record->delete();

        return redirect((string) url('/project/view', ['id' => $projectId, 'y' => substr($recordDate, 0, 4), 'm' => (int) substr($recordDate, 5, 2)]))
            ->with('success', '记录已删除');
    }
}
