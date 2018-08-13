<?php
namespace app\admin\controller\sysconf;

use app\common\controller\Backend;
use app\common\model\feigo\IndexSpecial;
use app\common\model\feigo\Adverts as AdvertsM;

class Adverts extends Backend
{
    protected $relationSearch = true;
    protected $layout = 'default2';

    public function index()
    {
        $list_rows = $this->cur_limit;

        $title = request()->get('title');
        $position = request()->get('position');
        $end_time = request()->get('end_time');

        $w = [];
        if ($title != null) $w[] = ['title', 'like', '%' . $title . '%'];
        switch ($position) {
            case 1: // pc 首页
                $w[] = ['device', '=', 4];
                $w[] = ['position', '=', 1];
                break;
            case 2: // pc 门店详情
                $w[] = ['device', '=', 4];
                $w[] = ['position', '=', 2];
                break;
            default:
                break;
        }
        $cur_time = date('Y-m-d H:i:s', time());
        switch ($end_time) {
            case 1: // 一天内
                $w[] = ['end_time', '<', date('Y-m-d H:i:s', strtotime($cur_time . " + 1 day"))];
                $w[] = ['end_time', '>', date('Y-m-d H:i:s', time())];
                break;
            case 2: // 三天内
                $w[] = ['end_time', '<', date('Y-m-d H:i:s', strtotime($cur_time . " + 3 day"))];
                $w[] = ['end_time', '>', $cur_time];
                break;
            case 3: // 一周内
                $w[] = ['end_time', '<', date('Y-m-d H:i:s', strtotime($cur_time . " + 1 week"))];
                $w[] = ['end_time', '>', $cur_time];
                break;
            default:
                break;
        }

        $list = AdvertsM::where($w)
            ->where($w)
            ->paginate($list_rows, false, ['query' => $this->request->get()]);

        $special_list = IndexSpecial::column('name', 'id');
        $this->view->assign('list', $list);
        $this->view->assign('special_list', $special_list);
        return $this->view->fetch();
    }

    /**
     * 新增
     * @return string
     */
    public function add()
    {
        if (request()->isAjax()) {
            $title = request()->post('title');
            $device = request()->post('device');
            $position = request()->post('position');
            $sub_id = request()->post('sub_id');
            $image_url = request()->post('image_url');
            $to_url = request()->post('to_url');
            $from_time = request()->post('from_time');

            $time_arr = explode(' - ', $from_time);
            $start_time = $time_arr[0];
            $end_time = $time_arr[1];

            if ($title == null || $device == null || $position == null || $image_url == null) {
                $this->error(__('请完善广告信息！'));
            }

            if (strstr($to_url, 'http://') === false) $to_url = 'http://' . $to_url;

            $time = date('Y-m-d H:i:s', time());
            $data = [
                'title' => $title,
                'image_url' => $image_url,
                'device' => $device,
                'position' => $position,
                'sub_id' => ($device == 4 && $position == 1) ? $sub_id : '',
                'region_id' => ',100000,',
                'is_visible' => 1,
                'is_show' => 0,
                'to_url' => $to_url,
                'is_super' => $this->auth->isSuperAdmin() ? 1 : 0,
                'create_time' => $time,
                'update_time' => $time,
                'start_time' => $start_time,
                'end_time' => $end_time,
            ];
            if (AdvertsM::create($data)) {
                $this->success(__('Operation completed'));
            }
            $this->error(__('Operation failed'));
        }
        $special_list = IndexSpecial::where('is_show', 1)->where('is_show_ad', 1)->column('name', 'id');
        $this->view->assign('special_list', $special_list);
        return $this->view->fetch('edit');
    }

    /**
     * 编辑
     * @param null $ids
     * @return string
     */
    public function edit($ids = null)
    {
        $row = AdvertsM::get($ids);
        if (!$row) $this->error(__('No Results were found'));
        if ($row['is_show'] == 1) $this->error(__('启用状态的数据不可编辑！'));

        if (request()->isAjax()) {
            $title = request()->post('title');
            $device = request()->post('device');
            $position = request()->post('position');
            $sub_id = request()->post('sub_id');
            $image_url = request()->post('image_url');
            $to_url = request()->post('to_url');
            $from_time = request()->post('from_time');

            $time_arr = explode(' - ', $from_time);
            $start_time = $time_arr[0];
            $end_time = $time_arr[1];

            if ($title == null || $device == null || $position == null || $image_url == null) {
                $this->error(__('请完善广告信息！'));
            }

            if (strstr($to_url, 'http://') === false) $to_url = 'http://' . $to_url;

            $time = date('Y-m-d H:i:s', time());
            $data = [
                'title' => $title,
                'image_url' => $image_url,
                'device' => $device,
                'position' => $position,
                'sub_id' => ($device == 4 && $position == 1) ? $sub_id : '',
                'region_id' => ',100000,',
                'is_visible' => 1,
                'is_show' => 0,
                'to_url' => $to_url,
                'update_time' => $time,
                'start_time' => $start_time,
                'end_time' => $end_time,
            ];

            if (AdvertsM::where('id', $ids)->update($data) !== false) {
                $this->success(__('Operation completed'));
            }
            $this->error(__('Operation failed'));
        }
        $special_list = IndexSpecial::where('is_show', 1)->where('is_show_ad', 1)->column('name', 'id');
        $from_time = '';
        if (!empty($row['start_time']) && !empty($row['end_time'])) {
            $from_time = $row['start_time'] . ' - ' . $row['end_time'];
        }
        $this->view->assign('from_time', $from_time);
        $this->view->assign('special_list', $special_list);
        $this->view->assign('row', $row);
        return $this->view->fetch('edit');
    }

    /**
     * 编辑状态
     */
    public function edit_status()
    {
        $ids = request()->post('ids');
        $status = request()->post('status');
        $row = AdvertsM::get($ids);
        if (empty($ids) || $status == null)
            $this->error(__('Parameter exception'));
        if (!$row)
            $this->error(__('No Results were found'));

        $advert = AdvertsM::where('id', $ids)->find();
        if (empty($advert))
            $this->error(__('找不到该广告'));
        if ($advert['is_show'] == $status)
            $this->error(__('有' . ($status == 1 ? '显示' : '隐藏') . '的广告，请重新选择！'));

        if ($status == 1) {
            $special = IndexSpecial::where('id', $advert['sub_id'])->find();
            if (!empty($special)) {
                if ($special['is_show_ad'] != 1 || $special['is_show'] != 1)
                    $this->error(__('该位置没有开启，不能显示广告'));
            }
        }

        if (AdvertsM::where('id', 'in', $ids)->setField('is_show', $status)) {
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
        $row = AdvertsM::get($ids);
        if (!$row)
            $this->error(__('No Results were found'));

        if (AdvertsM::where('id', 'in', $ids)->where('is_show', 1)->find()) {
            $this->error(__('有专题是显示状态，不能删除'));
        }

        if (AdvertsM::where('id', 'in', $ids)->delete()) {
            $this->success(__('Operation completed'));
        }
        $this->error(__('Operation failed'));
    }
}
