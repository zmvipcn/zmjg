<?php
declare(strict_types=1);

namespace app\model;

use think\Model;
use think\model\relation\HasMany;

/**
 * 用户模型
 * 用户ID由业务层分配（1000起，删除后可复用），关闭主键自增
 */
class User extends Model
{
    protected $name = 'users';

    protected $pk = 'id';

    protected $autoIncrement = false;

    // 角色常量
    public const ROLE_SUPER = 'super'; // 超级管理员
    public const ROLE_ADMIN = 'admin'; // 普通管理员
    public const ROLE_USER  = 'user';  // 普通用户

    /**
     * 分配下一个可复用的用户ID（从1001开始寻找最小空缺）
     */
    public static function nextId(): int
    {
        $ids = self::where('id', '>=', 1001)->order('id', 'asc')->column('id');
        $expect = 1001;
        foreach ($ids as $id) {
            if ($id > $expect) {
                break;
            }
            $expect = $id + 1;
        }
        return $expect;
    }

    /**
     * 生成指定用户的初始密码：zmjg + 用户ID
     */
    public static function defaultPassword(int $id): string
    {
        return 'zmjg' . $id;
    }

    public function isSuper(): bool
    {
        return $this->role === self::ROLE_SUPER;
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_SUPER], true);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'user_id', 'id');
    }
}
