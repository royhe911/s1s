<?php
namespace app\admin\controller\sysconf;

use app\common\controller\Backend;
use app\common\model\feigo\ArticleType;
use app\common\model\feigo\Regions;
use app\common\model\feigo\Rotation as RotationM;

class Rotation extends Backend
{
    protected $relationSearch = true;
    protected $layout = 'default2';

    public function index()
    {
        $city_id = $this->city_id;
        $is_show = request()->get('is_show', -1);
        $device = request()->get('device', -1);
        $list_rows = $this->cur_limit;

        $w = [];
        $w2 = '';
        if ($is_show >= 0) $w[] = ['is_show', '=', $is_show];
        if ($device > 0) $w[] = ['device', 'like', '%,' . $device . ',%'];
        $is_super_admin = $this->checkRegionAllAuth();
        if ($is_super_admin) {
            $city_id = request()->param('city_id');
            if ($city_id != null && !empty($city_id)) {
                $w2 = "(is_visible = 1 and (region_id =',100000,' or region_id like '%," . $city_id . ",%')) or (is_visible = 0 and region_id not like  '%," . $city_id . ",%')";
            }
        } else {
            $w[] = ['region_id', 'like', "%,{$city_id},%"];
        }

        $list = RotationM::where($w)
            ->where($w2)
            ->order('sort', 'asc')
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
        $this->view->assign('is_show', $is_show);
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
            $device = request()->post('device');
            $region_id = request()->post('region_id');
            $is_visible = request()->post('is_visible', 1);
            $image_xcx = request()->post('image_xcx');
            $is_show = request()->post('is_show');
            $url = request()->post('url');
            $sort = request()->post('sort');
            if (!$isSuperAdmin) $region_id = ",{$this->city_id},";

            if ($title == null || $device == null || $region_id == null || $is_visible == null || $image_xcx == null || $is_show == null) {
                $this->error(__('请完善轮播图信息！'));
            }

            if (strstr($url, 'http://') === false) $url = 'http://' . $url;

            $time = date('Y-m-d H:i:s', time());
            $data = [
                'title' => $title,
                'device' => $device,
                'region_id' => $region_id,
                'is_visible' => $is_visible,
                'image_xcx' => $image_xcx,
                'is_show' => $is_show,
                'url' => $url,
                'sort' => $sort,
                'is_super' => $this->auth->isSuperAdmin()? 1 : 0,
                'create_time' => $time
            ];
            if (RotationM::create($data)) {
                $this->success(__('Operation completed'));
            }
            $this->error(__('Operation failed'));
        }

        $this->view->assign('is_super_admin', $isSuperAdmin ? 1 : 0);
        return $this->view->fetch('edit');
    }

    /**
     * 编辑
     * @param null $ids
     * @return string
     */
    public function edit($ids = null)
    {
        $isSuperAdmin = $this->checkRegionAllAuth();;

        $row = RotationM::get($ids);
        if (!$row) $this->error(__('No Results were found'));

        if ($row['is_super'] == 1 && $isSuperAdmin == false) $this->error(__('没有权限操作，如需要，请联系管理员'));
        if ($row['is_show'] == 1) $this->error(__('启用状态的数据不可编辑！'));

        if (request()->isAjax()) {
            $title = request()->post('title');
            $device = request()->post('device');
            $region_id = request()->post('region_id');
            $is_visible = request()->post('is_visible', 1);
            $image_xcx = request()->post('image_xcx');
            $is_show = request()->post('is_show');
            $url = request()->post('url');
            $sort = request()->post('sort');
            if (!$isSuperAdmin) $region_id = ",{$this->city_id},";

            if ($title == null || $device == null || $region_id == null || $is_visible == null || $image_xcx == null || $is_show == null) {
                $this->error(__('请完善轮播图信息！'));
            }

            if (strstr($url, 'http://') === false) $url = 'http://' . $url;

            $data = [
                'title' => $title,
                'device' => $device,
                'region_id' => $region_id,
                'is_visible' => $is_visible,
                'image_xcx' => $image_xcx,
                'is_show' => $is_show,
                'url' => $url,
                'sort' => $sort,
            ];

            if (RotationM::where('id', $ids)->update($data) !== false) {
                $this->success(__('Operation completed'));
            }
            $this->error(__('Operation failed'));
        }

        $region_arr = Regions::where('id', 'in', $row['region_id'])->field('id, name')->select()->toArray();

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
        $rotation_id = request()->get('rotation_id');
        $region_id_1 = request()->get('region_id_1');
        $name = request()->get('name');
        $list_rows = request()->get('list_rows');
        $list_rows = empty($list_rows) ? 10: $list_rows;

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
        $rotation = [];
        if ($rotation_id != null) {
            $rotation = Rotation::get($rotation_id);
        }

        $region_list_1 = Regions::where('level_type', 1)->column('name', 'id');
        $this->view->assign('rotation', $rotation);
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
        $row = RotationM::get($ids);
        if (!$row)
            $this->error(__('No Results were found'));
        if ($row['is_super'] == 1 && $this->auth->isSuperAdmin() == false) {
            $this->error(__('没有权限操作，如需要，请联系管理员'));
        }

        if (empty($ids) || $status == null)
            $this->error(__('Parameter exception'));

        if (RotationM::where('id', 'in', $ids)->where('is_show', $status)->find()) {
            $this->error(__('有' . ($status == 1 ? '启用' : '禁用') . '的轮播图，请重新选择！'));
        }

        if (RotationM::where('id', 'in', $ids)->setField('is_show', $status)) {
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
        $row = RotationM::get($ids);
        if (!$row)
            $this->error(__('No Results were found'));
        if ($row['is_super'] == 1 && $this->auth->isSuperAdmin() == false) {
            $this->error(__('没有权限操作，如需要，请联系管理员'));
        }

        if (RotationM::where('id', 'in', $ids)->where('is_show', 1)->find()) {
            $this->error(__('有轮播图启用状态，不能删除'));
        }

        if (RotationM::where('id', 'in', $ids)->delete()) {
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

        $row = RotationM::get($id);
        if (!$row)
            $this->error(__('No Results were found'));
        if ($row['is_super'] == 1 && $this->auth->isSuperAdmin() == false) {
            $this->error(__('没有权限操作，如需要，请联系管理员'));
        }

        if (RotationM::where('id', $id)->setField('sort', $sort)) {
            $this->success(__('Operation completed'));
        }
        $this->error(__('Operation failed'));
    }
}
