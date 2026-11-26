<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\controller\Base;
use app\model\HelpArticle;
use think\facade\View;
use think\response\Redirect;

/**
 * 帮助内容管理
 */
class Help extends Base
{
    public function index()
    {
        $articles = HelpArticle::order('sort', 'asc')->order('id', 'desc')->paginate(20);
        View::assign([
            'activeMenu' => 'admin_help',
            'articles'   => $articles,
            'categories' => HelpArticle::CATEGORIES,
        ]);
        return View::fetch('admin/help/index');
    }

    public function edit($id = 0)
    {
        $article = $id ? HelpArticle::find((int) $id) : null;
        View::assign([
            'activeMenu' => 'admin_help',
            'article'    => $article,
            'categories' => HelpArticle::CATEGORIES,
        ]);
        return View::fetch('admin/help/edit');
    }

    public function save(): Redirect
    {
        $title = trim((string) $this->request->post('title', ''));
        if ($title === '') {
            return redirect((string) url('/admin/help'))->with('error', '请填写帮助标题');
        }
        $category = (string) $this->request->post('category', 'web');
        if (!isset(HelpArticle::CATEGORIES[$category])) {
            $category = 'web';
        }

        $data = [
            'title'       => mb_substr($title, 0, 150),
            'category'    => $category,
            'content'     => (string) $this->request->post('content', ''),
            'sort'        => (int) $this->request->post('sort', 0),
            'status'      => (int) $this->request->post('status', 1),
            'update_time' => date('Y-m-d H:i:s'),
        ];

        $id = (int) $this->request->post('id', 0);
        if ($id) {
            $article = HelpArticle::find($id);
            if ($article) {
                $article->save($data);
            }
        } else {
            HelpArticle::create(array_merge($data, ['create_time' => date('Y-m-d H:i:s')]));
        }
        return redirect((string) url('/admin/help'))->with('success', '帮助内容已保存');
    }

    public function delete($id): Redirect
    {
        $article = HelpArticle::find((int) $id);
        if ($article) {
            $article->delete();
        }
        return redirect((string) url('/admin/help'))->with('success', '帮助文章已删除');
    }
}
