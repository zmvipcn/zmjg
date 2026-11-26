<?php
declare(strict_types=1);

namespace app\model;

use think\Model;
use think\model\relation\BelongsTo;
use think\model\relation\HasMany;

/**
 * 项目模型
 */
class Project extends Model
{
    protected $name = 'projects';

    // 状态：1进行中 0已结束（历史）
    public const STATUS_ACTIVE  = 1;
    public const STATUS_HISTORY = 0;

    // 每个用户最多同时存在的进行中项目数
    public const MAX_ACTIVE = 3;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function records(): HasMany
    {
        return $this->hasMany(WorkRecord::class, 'project_id', 'id');
    }

    /**
     * 累计上班天数
     */
    public function totalWorkDays(): float
    {
        return (float) WorkRecord::where('project_id', $this->id)->sum('work_days');
    }

    /**
     * 累计加班小时
     */
    public function totalOvertimeHours(): float
    {
        return (float) WorkRecord::where('project_id', $this->id)->sum('overtime_hours');
    }
}
