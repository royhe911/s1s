<?php
namespace app\admin\controller\sysconf;

use app\common\controller\Backend;
use app\common\model\feigo\Config;
use think\facade\Cache;

class ForcedLogin extends Backend
{
    protected $relationSearch = true;
    protected $layout = 'default2';

    private $key = 'forced_login';

    protected $device = [
        1 => 'Android',  2 => 'IOS', 3 => '小程序', 4 => 'PC'
    ];

    public function index()
    {
        $list = Config::getValue($this->key);

        $this->view->assign('list', $list);
        $this->view->assign('device', $this->device);
        return $this->view->fetch();
    }

    /**
     * 编辑状态
     */
    public function edit_status()
    {
        $ids = request()->post('ids');
        $status = request()->post('status', 0);

        if (empty($ids) || $status == null)
            $this->error(__('Parameter exception'));

        $list = Config::getValue($this->key);
        if (!isset($list[$ids])) $this->error(__('No Results were found'));


        $list[$ids] = $status;
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
