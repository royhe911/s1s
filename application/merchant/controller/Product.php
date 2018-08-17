<?php

namespace app\merchant\controller;

use app\common\model\Merchant;
use app\common\model\Product;
use app\common\model\ProductAttr;
use app\common\model\Task;

class Product extends Pub
{

    protected $not_login = [''];

    public function index()
    {
    }

    /**
     * 添加产品和任务
     */
    public function add_product()
    {
        if (request()->isPost()) {
            $mid           = $this->merchant_info['id']; // 商家ID
            $balance       = $this->merchant_info['balance_amount']; // 商家余额
            $overdraft     = $this->merchant_info['overdraft_amount']; // 商家透支额度
            $shop_id       = request()->post('shop_id'); // 店铺ID
            $type          = request()->post('type'); // 商品类型
            $label         = request()->post('label'); // 标签词
            $title         = request()->post('title'); // 商品标题
            $url           = request()->post('url'); // 商品链接
            $pic           = request()->post('pic'); // 商品主图
            $amount        = request()->post('amount'); // 任务金额
            $addtime       = time();
            $status        = 3;
            $reward_method = request()->post('reward_method'); // 赠品方式
            $reward        = request()->post('reward'); // 赠品内容
            $keyword       = request()->post('keyword'); // 关键词数组

            if (empty($shop_id) || empty($type) || empty($title) || empty($url) || empty($pic) || empty($keyword)) {
                $this->result['code'] = '10';
                $this->result['msg']  = lang($this->result['code']);
            }

            if ($balance_amount + $overdraft < $amount) {
                return ['code' => '30007', 'msg' => lang('30007')];
            }
            Db::startTrans();
            try {
                $data = [
                    'mid'           => $mid,
                    'shop_id'       => $shop_id,
                    'type'          => $type,
                    'label'         => $label ? $label : '',
                    'title'         => $title,
                    'url'           => $url,
                    'pic'           => $pic,
                    'amount'        => $amount,
                    'addtime'       => $addtime,
                    'status'        => $status,
                    'reward_method' => $reward_method,
                    'reward'        => $reward,
                ];
                $product = Product::create($data);

                $keyword_arr = json_decode($keyword, true);
                $data_arr    = [];
                $data_task   = [];
                foreach ($keyword_arr as $i => $k) {
                    $data_arr[$i]['pid'] = $product->id;
                    if (!empty($k['keyword'])) {
                        $data_arr[$i]['keyword'] = $k['keyword'];
                    } else {
                        Db::rollback();
                        $this->result['code'] = "30000";
                        $this->result['msg']  = lang($this->result['code']);
                        break;
                    }
                    if (!empty($k['price']) && floatval($k['price'])) {
                        $data_arr[$i]['price'] = $k['price'];
                    } else {
                        Db::rollback();
                        $this->result['code'] = "30001";
                        $this->result['msg']  = lang($this->result['code']);
                        break;
                    }
                    // 随机分配任务执行时间
                    $each_hour = $this->release_algorithm($k);
                    if (!empty($k['num']) && intval($k['num'])) {
                        $num                 = intval($k['num']);
                        $data_arr[$i]['num'] = $num;
                        for ($j = 0; $j < intval($k['num']); $j++) {
                            $data_task['mid']            = $mid;
                            $data_task[$j]['pid']        = $product->id;
                            $data_task[$j]['price']      = $k['price'];
                            $data_task[$j]['salesman']   = 0; // 业务员ID
                            $data_task[$J]['cost']       = $k['cost'];
                            $data_task[$j]['status']     = 2;
                            $data_task[$j]['addtime']    = time();
                            $data_task[$j]['start_time'] = $each_hour[$j];
                        }
                    } else {
                        Db::rollback();
                        $this->result['code'] = '30002';
                        $this->result['msg']  = lang($this->result['code']);
                        break;
                    }
                    if (!empty($k['service'])) {
                        $data_arr[$i]['service'] = $k['service'];
                    }
                    if (!empty($k['remark'])) {
                        $data_arr[$i]['remark'] = $k['remark'];
                    }
                }
                (new ProductAttr)->saveAll($data_arr);
                (new Task)->saveAll($data_task);
                Db::commit();
                $this->result['code'] = "1";
                $this->result['msg']  = lang($this->result['code']);
            } catch (\Exception $e) {
                Db::rollback();
                $this->result['code'] = '30404';
                $this->result['msg']  = lang($this->result['code']);
            }
        }
        return $this->result;
    }

    /**
     * 根据商家选择放单规则计算每小时发开放的任务
     * @param  array $keyword 参数数组
     * @return array          返回每个任务执行的开始时间
     */
    private function release_algorithm($keyword)
    {
        if (empty($keyword['start_time'])) {
            return "30004";
        }
        if (empty($keyword['end_time'])) {
            return "30005";
        }
        $data       = [];
        $start_time = $keyword['start_time'];
        $end_time   = $keyword['end_time'];
        if ($keyword['average'] === 1) {
            $h   = date('H', $end_time) - date('H', $start_time);
            $num = intval($keyword['num']);
            if ($num % $h == 0) {
                $average = $num / $h;
                $last    = 0;
            } else {
                $average = ceil($num / $h);
                $last    = $num - ($h - 1) * $average;
            }
            for ($i = 0; $i < $h; $i++) {
                $start = strtotime(date('Y-m-d H'), $start_time) + $i * 3600;
                if ($last != 0 && $i == ($h - 1)) {
                    $average = $last;
                }
                for ($j = 0; $j < $average; $j++) {
                    $data[] = $start + rand(0, 1800);
                }
            }
        } elseif ($keyword['average'] === 2) {
            if (empty($keyword['each_hour'])) {
                return "10";
            }
            $each_hour = json_decode($keyword['each_hour'], true);
            if (count($each_hour) !== $h) {
                return "30006";
            }
            foreach ($each_hour as $hour => $count) {
                $start = strtotime(date("Y-m-d {$hour}"), $start_time);
                for ($n = 0; $n < $count; $n++) {
                    $data[] = $start + rand(0, 1800);
                }
            }
        } else {
            return "30404";
        }
        return $data;
    }
}
