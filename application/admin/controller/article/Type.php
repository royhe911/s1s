<?php

namespace app\admin\controller\article;

use think\facade\Cache;

use app\common\controller\Backend;
use app\common\model\feigo\ArticleType;
use app\common\model\feigo\Article;

/**
 * 门店分类管理
 *
 * @icon fa fa-user
 */
class Type extends Backend
{
    protected $relationSearch = true;
    protected $layout = 'default2';

    /**
     * 查看
     */
    public function index()
    {
        $list = (new ArticleType)->getAllType();
        $this->view->assign('list', $list);
        return $this->view->fetch();
    }

    /**
     * 新增
     * @return string
     */
    public function add()
    {
        if (request()->isAjax()) {
            $name = request()->post('name');
            if (strstr($name, '%') !== false) $this->error(__('您输入了非法字符！'));
            if (ArticleType::where('name', $name)->find()) {
                $this->error(__('分类已存在'));
            }

            if (ArticleType::create(['name' => $name])) {
                Cache::rm('article_type_all');
                $this->success(__('Operation completed'));
            }
            $this->error(__('Operation failed'));
        }
        return $this->view->fetch('edit');
    }

    /**
     * 编辑
     * @param null $ids
     * @return string
     */
    public function edit($ids = null)
    {

        $row = ArticleType::where(['id' => $ids])->find();
        if (!$row)
            $this->error(__('No Results were found'));
        if (request()->isAjax()) {
            $name = request()->post('name');
            if (strstr($name, '%') !== false) $this->error(__('您输入了非法字符！'));
            if (ArticleType::where('name', $name)->where('id', 'not in', $ids)->find()) {
                $this->error(__('分类已存在'));
            }

            if (ArticleType::where(['id' => $ids])->update(['name' => $name])) {
                Cache::rm('article_type_all');
                $this->success(__('Operation completed'));
            }
            $this->error(__('Operation failed'));
        }

        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 删除
     */
    public function delete()
    {
        $ids = request()->post('ids');
        if (empty($ids))
            $this->error(__('Parameter exception'));

        if (Article::where('type_id', 'in', $ids)->find()) {
            $this->error(__('有关联的资讯，请重新选择！'));
        }

        if (ArticleType::where('id', 'in', $ids)->delete()) {
            Cache::rm('article_type_all');
            $this->success(__('Operation completed'));
        }
        $this->error(__('Operation failed'));
    }

    /**
     * 修改排序
     */
    public function edit_sort()
    {
        $id = request()->post('id');
        $sort = request()->post('sort');

        if ($id == null || $sort == null) {
            $this->error(__('Parameter exception'));
        }
        if (ArticleType::where('id', $id)->setField('sort', $sort) !== false) {
            Cache::rm('article_type_all');
            $this->success(__('Operation completed'));
        }
        $this->error(__('Operation failed'));
    }
}
