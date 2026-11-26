<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * APP安装包文件模型（安卓 / iOS / 鸿蒙）
 */
class AppFile extends Model
{
    protected $name = 'app_files';

    public const PLATFORMS = [
        'android' => '安卓',
        'ios'     => 'iOS',
        'harmony' => '鸿蒙',
    ];
}
