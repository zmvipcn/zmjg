<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 帮助文章模型
 */
class HelpArticle extends Model
{
    protected $name = 'help_articles';

    public const CATEGORIES = [
        'web'     => '网站',
        'android' => '安卓APP',
        'ios'     => 'iOS APP',
        'harmony' => '鸿蒙APP',
    ];
}
