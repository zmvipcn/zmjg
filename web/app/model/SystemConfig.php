<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 系统配置模型（按 group/name 键值存储）
 */
class SystemConfig extends Model
{
    protected $name = 'system_configs';

    /**
     * 获取某个分组的全部配置 [name => value]
     */
    public static function groupValues(string $group): array
    {
        $rows = self::where('group', $group)->select();
        $data = [];
        foreach ($rows as $row) {
            $data[$row->name] = $row->value;
        }
        return $data;
    }
}
