<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\controller\Base;
use app\model\AppFile;
use think\facade\Filesystem;
use think\facade\View;
use think\response\Redirect;

/**
 * 文件管理：安卓 / iOS / 鸿蒙 APP 安装包
 */
class File extends Base
{
    public function index()
    {
        $files = AppFile::order('platform', 'asc')->order('sort', 'asc')->order('id', 'desc')->paginate(20);
        View::assign([
            'activeMenu' => 'admin_file',
            'files'      => $files,
            'platforms'  => AppFile::PLATFORMS,
        ]);
        return View::fetch('admin/file/index');
    }

    /**
     * 新增/更新安装包（支持本地上传或填写外部下载地址）
     */
    public function save(): Redirect
    {
        $platform = (string) $this->request->post('platform', 'android');
        if (!isset(AppFile::PLATFORMS[$platform])) {
            return $this->back('平台类型不合法');
        }
        $version = trim((string) $this->request->post('version', ''));
        if ($version === '') {
            return $this->back('请填写版本号');
        }

        $fileUrl = trim((string) $this->request->post('file_url', ''));
        $fileName = trim((string) $this->request->post('file_name', ''));
        $fileSize = trim((string) $this->request->post('file_size', ''));

        $upload = $this->request->file('package');
        if ($upload) {
            try {
                $path = Filesystem::disk('public')->putFile('app_package', $upload);
                $fileUrl = '/storage/' . str_replace('\\', '/', $path);
                $fileName = $upload->getOriginalName();
                $fileSize = $fileSize ?: round($upload->getSize() / 1024 / 1024, 2) . ' MB';
            } catch (\Throwable $e) {
                return $this->back('安装包上传失败：' . $e->getMessage());
            }
        }
        if ($fileUrl === '') {
            return $this->back('请上传安装包或填写下载地址');
        }

        $id = (int) $this->request->post('id', 0);
        $data = [
            'platform'    => $platform,
            'version'     => mb_substr($version, 0, 50),
            'file_name'   => mb_substr($fileName, 0, 150),
            'file_url'    => mb_substr($fileUrl, 0, 255),
            'file_size'   => mb_substr($fileSize, 0, 30),
            'update_note' => trim((string) $this->request->post('update_note', '')),
            'status'      => (int) $this->request->post('status', 1),
            'sort'        => (int) $this->request->post('sort', 0),
            'update_time' => date('Y-m-d H:i:s'),
        ];

        if ($id) {
            $model = AppFile::find($id);
            if ($model) {
                $model->save($data);
            }
        } else {
            AppFile::create(array_merge($data, ['create_time' => date('Y-m-d H:i:s')]));
        }
        return $this->back('安装包信息已保存', 'success');
    }

    public function delete($id): Redirect
    {
        $model = AppFile::find((int) $id);
        if ($model) {
            // 本地存储的文件一并清理
            if (str_starts_with((string) $model->file_url, '/storage/')) {
                $absolute = public_path() . ltrim($model->file_url, '/');
                if (is_file($absolute)) {
                    @unlink($absolute);
                }
            }
            $model->delete();
        }
        return $this->back('安装包记录已删除', 'success');
    }

    private function back(string $message, string $type = 'error'): Redirect
    {
        return redirect((string) url('/admin/file'))->with($type, $message);
    }
}
