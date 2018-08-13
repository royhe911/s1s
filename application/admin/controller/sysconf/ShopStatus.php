<?php
namespace app\admin\controller\sysconf;

use app\common\controller\Backend;
use app\common\model\feigo\Config;
use think\facade\Cache;

class ShopStatus extends Backend
{
    protected $relationSearch = true;
    protected $layout = 'default2';

    private $key = 'business_status';

    public function index()
    {
        $list = Config::getValue($this->key);

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
            $list = Config::getValue($this->key);
            $name = request()->post('name');
            if ($name == null) $this->error(__('请完善信息！'));
            array_push($list, $name);

            $data = [
                'c_key' => $this->key,
                'c_value' => json_encode($list),
                'update_time' => date('Y-m-d H:i:s', time())
            ];
            if (Config::where('c_key', $this->key)->update($data) !== false) {
                Cache::rm('config_value_' . $this->key);
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
        $list = Config::getValue($this->key);
        if (!isset($list[$ids])) $this->error(__('No Results were found'));

        if (request()->isAjax()) {
            $name = request()->post('name');
            if ($name == null) $this->error(__('请完善信息！'));

            $list[$ids] = $name;
            $data = [
                'c_key' => $this->key,
                'c_value' => json_encode($list),
                'update_time' => date('Y-m-d H:i:s', time())
            ];
            if (Config::where('c_key', $this->key)->update($data) !== false) {
                Cache::rm('config_value_' . $this->key);
                $this->success(__('Operation completed'));
            }
            $this->error(__('Operation failed'));
        }
        $row = $list[$ids];
        $this->view->assign('row', $row);
        return $this->view->fetch('edit');
    }

    /**
     * 删除
     */
    public function delete()
    {
        $ids = request()->post('ids');
        if (empty($ids))
            $this->error(__('Parameter exception'));
        $list = Config::getValue($this->key);
        if (!isset($list[$ids])) $this->error(__('No Results were found'));
        unset($list[$ids]);

        $data = [
            'c_key' => $this->key,
            'c_value' => json_encode($list),
            'update_time' => date('Y-m-d H:i:s', time())
        ];

        if (Config::where('c_key', $this->key)->update($data) !== false) {
            Cache::rm('config_value_' . $this->key);
            $this->success(__('Operation completed'));
        }
        $this->error(__('Operation failed'));
    }
}
