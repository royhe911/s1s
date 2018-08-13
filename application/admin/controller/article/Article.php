<?php

namespace app\admin\controller\article;

use app\common\controller\Backend;
use app\common\model\feigo\ArticleType;
use app\common\model\feigo\Config;
use app\common\model\feigo\Regions;
use app\common\model\feigo\Article as ArticleM;

class Article extends Backend
{
    protected $relationSearch = true;
    protected $layout = 'default2';

    /**
     * 列表
     * @return string|\think\response\Json
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $city_id = $this->city_id;

        $list_rows = $this->cur_limit;
        $type_id = request()->get('type_id');
        $from_time = request()->get('from_time');

        $w = [];
        $w2 = '';

        $is_super_admin = $this->checkRegionAllAuth();;
        if ($is_super_admin) {
            $city_id = request()->param('city_id');
            if ($city_id != null && !empty($city_id)) {
                $w2 = "(a.is_visible = 1 and (a.region_id =',100000,' or a.region_id like '%," . $city_id . ",%')) or (a.is_visible = 0 and a.region_id not like  '%," . $city_id . ",%')";
            }

        } else {
            $w[] = ['a.region_id', 'like', "%$city_id%"];
        }

        if ($type_id != null) $w[] = ['a.type_id', '=', $type_id];
        $str = betweenTime($from_time, 'a.create_time');
        if (!empty($str)) $w[] = $str;

        $list = (new ArticleM)->alias('a')
            ->join('article_type t', 'a.type_id=t.id')
            ->field('a.*, t.name')
            ->where($w)
            ->where($w2)
            ->order('a.create_time', 'desc')
            ->paginate($list_rows, false, ['query' => $this->request->get()]);

        $city_list = Regions::where('level_type', 2)->column('name', 'id');
        foreach ($list as $k => $v) {
            $city_str = '';
            if (!empty($v['region_id'])) {
                if (strpos($v['region_id'], ',100000,') !== false) {
                    $city_str = '全国';
                } else {
                    $region_arr = explode(',', trim($v['region_id'], ','));
                    if (is_array($region_arr) && !empty($region_arr)) {
                        foreach ($region_arr as $v2) {
                            if (isset($city_list[$v2])) {
                                $city_str .= $city_list[$v2] . ',';
                            }
                        }
                    }
                }
            }
            $list[$k]['city_str'] = trim($city_str, ',');
        }

        $type_list = ArticleType::getAllType();
        $region_list_1 = Regions::where('level_type', 1)->column('name', 'id');
        $this->view->assign('list', $list);
        $this->view->assign('type_list', $type_list);
        $this->view->assign('is_super_admin', $is_super_admin);
        $this->view->assign('region_list_1', $region_list_1);
        return $this->view->fetch();
    }

    /**
     * 新增
     * @return string
     */
    public function add()
    {
        $isSuperAdmin = $this->checkRegionAllAuth();;

        if (request()->isAjax()) {
            $title = request()->post('title');
            $type_id = request()->post('type_id');
            $author = request()->post('author');
            $region_id = request()->post('region_id');
            $is_visible = request()->post('is_visible');
            $image_url = request()->post('image_url');
            $is_show = request()->post('is_show');
            $desc = request()->post('desc');
            $content = request()->post('content', '', 'htmlspecialchars');
            $position = request()->post('position');
            if (strstr($title, '%') !== false) $this->error(__('名称中有非法字符！'));
            if (strstr($author, '%') !== false) $this->error(__('来源中有非法字符！'));
            if ($isSuperAdmin) {
                if (strstr(',100000,', $region_id) !== false){
                    if ($is_visible != 1) $this->error(__('包含全国必须选择包含！'));
                    $region_id = ',100000,';
                }
            } else {
                $region_id = ",{$this->city_id},";
                $is_visible = 1;
            }

            $is_empty = (empty($title) || empty($type_id) || empty($region_id) || !isset($is_visible) || empty($image_url) || !isset($is_show) || empty($desc) || empty($content) || empty(trim($region_id, ',')));

            $is_empty && $this->error(__('请完善资讯信息！'));

            $time = date('Y-m-d H:i:s', time());
            $data = [
                'title' => $title,
                'type_id' => $type_id,
                'author' => $author,
                'region_id' => $region_id,
                'is_visible' => $is_visible,
                'image_url' => $image_url,
                'is_show' => $is_show,
                'desc' => $desc,
                'description' => $desc,
                'content' => $content,
                'is_super' => $isSuperAdmin ? 1 : 0,
                'position' => $position,
                'create_time' => $time
            ];

            if ($region_id == ',100000,') {
                $tag = ['news_100000_b'];
            } else {
                $region_id_arr = explode(',', trim($region_id, ','));
                if ($is_visible != 1) {
                    $support_city = Config::getValue('support_city');
                    $region_id_arr = Regions::where('id', 'not in', $region_id_arr)->where('id', 'in', $support_city)->column('id');
                }
                $tag = array_map(function ($ab) {
                    return "news_{$ab}_b";
                }, $region_id_arr);
            }
            $id = (new ArticleM())->insertGetId($data);
            if ($id) {
                // 极光推送
                jPush('《' . $title . '》点击查看....', [], $tag, [
                    'sound' => 'sound',
                    'badge' => '+1',
                ], [], '', [
                    'title' => '《' . $title . '》点击查看....',
                    'extras' => ['category' => '2', 'sign_id' => '2', 'id' => "{$id}"]
                ]);
                $this->success(__('Operation completed'));
            }
            $this->error(__('Operation failed'));
        }

        $type_list = ArticleType::getAllType();
        $this->view->assign('is_super_admin', $isSuperAdmin ? 1 : 0);
        $this->view->assign('type_list', $type_list);
        return $this->view->fetch('edit');
    }

    /**
     * 编辑
     * @param null $ids
     * @return string
     */
    public function edit($ids = null)
    {
        $isSuperAdmin = $this->checkRegionAllAuth();

        $row = ArticleM::get($ids);
        if (!$row) $this->error(__('No Results were found'));
        if ($row['is_super'] == 1 && $isSuperAdmin == false)  $this->error(__('没有权限操作，如需要，请联系管理员'));

        if ($row['is_show'] == 1) $this->error(__('启用状态的数据不可编辑！'));

        if (request()->isAjax()) {
            $title = request()->post('title');
            $type_id = request()->post('type_id');
            $author = request()->post('author');
            $region_id = request()->post('region_id');
            $is_visible = request()->post('is_visible');
            $image_url = request()->post('image_url');
            $is_show = request()->post('is_show');
            $desc = request()->post('desc');
            $content = request()->post('content', '', 'htmlspecialchars');
            $position = request()->post('position');
            if (strstr($title, '%') !== false) $this->error(__('名称中有非法字符！'));
            if (strstr($author, '%') !== false) $this->error(__('来源中有非法字符！'));

            if ($isSuperAdmin){
                if (strstr(',100000,', $region_id) !== false){
                    if ($is_visible != 1) $this->error(__('包含全国必须选择包含！'));
                    $region_id = ',100000,';
                }
            }else{
                $region_id = ",{$this->city_id},";
                $is_visible = 1;
            }

            $is_empty = (empty($title) || empty($type_id) || empty($region_id) || !isset($is_visible) || empty($image_url) || !isset($is_show) || empty($desc) || empty($content) || empty(trim($region_id, ',')));

            $is_empty && $this->error(__('请完善资讯信息！'));

            $time = date('Y-m-d H:i:s', time());
            $data = [
                'title' => $title,
                'type_id' => $type_id,
                'author' => $author,
                'region_id' => $region_id,
                'is_visible' => $is_visible,
                'image_url' => $image_url,
                'is_show' => $is_show,
                'description' => $desc,
                'desc' => $desc,
                'content' => $content,
                'position' => $position,
                'update_time' => $time
            ];

            if (ArticleM::where('id', $ids)->update($data)) $this->success(__('Operation completed'));

            $this->error(__('Operation failed'));
        }

        $row['content'] = $row['content'] ? htmlspecialchars_decode($row['content']) : '';

        $region_arr = Regions::where('id', 'in', $row['region_id'])->field('id, name')->select()->toArray();
        $type_list = ArticleType::getAllType();

        $this->view->assign('type_list', $type_list);
        $this->view->assign('is_super_admin', $isSuperAdmin ? 1 : 0);
        $this->view->assign('row', $row);
        $this->view->assign('regions_data', $region_arr);

        return $this->view->fetch('edit');
    }

    /**
     * 地区列表
     * @return string
     */
    public function region_list()
    {
        $list_rows = request()->get('list_rows');
        $list_rows = empty($list_rows) ? 10: $list_rows;
        $article_id = request()->get('article_id');
        $region_id_1 = request()->get('region_id_1');
        $name = request()->get('name');

        $w[] = ['r.level_type', '=', 2];
        if (!empty($name) && $name != null) $w[] = ['r.name', 'like', '%' . $name . '%'];
        if ($region_id_1 != null) {
            $city_id = Regions::where('parent_id', $region_id_1)->column('id');
            $w[] = ['r.id', 'in', $city_id];
        }
        $city_list = (new Regions)->alias('r')
            ->join('regions re', 're.id=r.parent_id')
            ->field('r.*, re.name as region_name')
            ->where($w)
            ->paginate($list_rows, false, ['query' => $this->request->get()]);
        $acticle = [];
        if ($article_id != null) {
            $acticle = Activity::get($article_id);
        }

        $region_list_1 = Regions::where('level_type', 1)->column('name', 'id');
        $this->view->assign('acticle', $acticle);
        $this->view->assign('city_list', $city_list);
        $this->view->assign('region_list_1', $region_list_1);
        return $this->view->fetch();
    }

    /**
     * 编辑状态
     */
    public function edit_status()
    {
        $ids = request()->post('ids');
        $status = request()->post('status');
        $row = ArticleM::get($ids);
        if (!$row)
            $this->error(__('No Results were found'));
        if ($row['is_super'] == 1 && $this->auth->isSuperAdmin() == false) {
            $this->error(__('没有权限操作，如需要，请联系管理员'));
        }

        if (empty($ids) || $status == null)
            $this->error(__('Parameter exception'));

        if (ArticleM::where('id', 'in', $ids)->where('is_show', $status)->find()) {
            $this->error(__('有' . ($status == 1 ? '显示' : '隐藏') . '的资讯，请重新选择！'));
        }

        if (ArticleM::where('id', 'in', $ids)->setField('is_show', $status)) {
            $this->success(__('Operation completed'));
        }
        $this->error(__('Operation failed'));
    }

    /**
     * 删除
     */
    public function delete()
    {
        $ids = request()->post('ids');
        if (empty($ids))
            $this->error(__('Parameter exception'));
        $row = ArticleM::get($ids);
        if (!$row)
            $this->error(__('No Results were found'));
        if ($row['is_super'] == 1 && $this->auth->isSuperAdmin() == false) {
            $this->error(__('没有权限操作，如需要，请联系管理员'));
        }
        if (ArticleM::where('id', 'in', $ids)->where('is_show', 1)->find()) {
            $this->error(__('有资讯显示状态，不能删除'));
        }

        if (ArticleM::where('id', 'in', $ids)->delete()) {
            $this->success(__('Operation completed'));
        }
        $this->error(__('Operation failed'));
    }

    /**
     * 获取下级列表
     */
    public function get_city()
    {
        $pid = request()->param('pid');

        $city_list = Regions::where('parent_id', $pid)->field('id, name')->select()->toArray();

        $this->success(__('Operation completed'), '', $city_list);
    }

}
