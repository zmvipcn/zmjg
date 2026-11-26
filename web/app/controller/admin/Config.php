<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\controller\Base;
use app\model\SystemConfig;
use think\facade\View;
use think\response\Redirect;

/**
 * 系统配置：系统信息 / 邮箱配置 / 手机（短信）配置
 */
class Config extends Base
{
    private const GROUPS = [
        'system' => '系统信息',
        'email'  => '邮箱配置',
        'sms'    => '手机配置',
    ];

    public function index()
    {
        $group = (string) $this->request->param('group', 'system');
        if (!isset(self::GROUPS[$group])) {
            $group = 'system';
        }
        $items = SystemConfig::where('group', $group)->order('id', 'asc')->select();

        View::assign([
            'activeMenu' => 'admin_config',
            'groups'     => self::GROUPS,
            'group'      => $group,
            'items'      => $items,
        ]);
        return View::fetch('admin/config/index');
    }

    /**
     * 保存某个分组的配置
     */
    public function save(): Redirect
    {
        $group = (string) $this->request->post('group', 'system');
        if (!isset(self::GROUPS[$group])) {
            return redirect((string) url('/admin/config'))->with('error', '配置分组不合法');
        }

        $values = (array) $this->request->post('config', []);
        $now = date('Y-m-d H:i:s');
        foreach (SystemConfig::where('group', $group)->select() as $item) {
            if (array_key_exists($item->name, $values)) {
                $item->save(['value' => (string) $values[$item->name], 'update_time' => $now]);
            }
        }
        return redirect((string) url('/admin/config', ['group' => $group]))->with('success', '配置已保存');
    }
}
