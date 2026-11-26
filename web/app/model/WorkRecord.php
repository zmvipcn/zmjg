<?php
declare(strict_types=1);

namespace app\model;

use think\Model;
use think\model\relation\BelongsTo;

/**
 * 工天记录模型（每个项目每天一条）
 */
class WorkRecord extends Model
{
    protected $name = 'work_records';

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }
}
