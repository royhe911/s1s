<?php
namespace app\admin\command;

use app\common\model\feigo\Regions;
use app\common\model\feigo\ShopImport;
use app\common\model\feigo\ShopImportErr;
use app\common\model\feigo\Shops;
use app\common\model\feigo\ShopTypes;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\console\input\Argument;
use think\Db;
use think\Exception;
use think\exception\ErrorException;
use think\facade\Log;

class shopimportdata extends Command
{

    protected function configure()
    {
        $this->setName('shopimportdata')
            ->addArgument('name', Argument::OPTIONAL, "your name")
            ->setDescription('Here is the remark ');
    }


    /**
     * 导入门店数据
     * @param Input $input
     * @param Output $output
     */
    protected function execute(Input $input, Output $output)
    {
        $redis = new \RedisM;
        $distance = 5000;
        $type_list = ShopTypes::getAllTypeToS();
        $import = ShopImport::where('status', 2)->where('count', '<', 3)->lock(true)->order('id', 'asc')->order('count', 'asc')->find();
        if (empty($import)) {
            Log::info('没有要处理的店铺数据');
            Db::rollback();die;
        }
        $import = $import->toArray();
        $branch_no = $import['branch_no'];
        try {
            $shop_list = $redis->lpop('import_shop_list_queue_' . $branch_no);
            //$shop_list = $redis->lrange('import_shop_list_queue_' . $branch_no);
            if (empty($shop_list) || $shop_list == false) {
                Log::info('没有要处理的店铺数据');
                $output->error('没有要处理的店铺数据');die;
            }

            $shop_list = json_decode($shop_list, true);
            //$shop_list = json_decode($shop_list[0], true);
            foreach ($shop_list as $k => $v) {
                $code = 0;
                $v['err'] = [];
                $region_id_2 = $v['city_id'];
                $region_id_2 = Regions::where('id', $region_id_2)->find();
                if (empty($region_id_2)) {
                    $v['err'][] = '市信息有误';
                    Log::info($v);
                    $code = 1;
                }
                $region_id_1 = Regions::where('id', $region_id_2['parent_id'])->find();
                if (empty($region_id_1)) {
                    $v['err'][] = '省信息有误';
                    Log::info($v);
                    $code = 1;
                }
                $region_3 = removeStr($v['region_id_3']);
                $region_id_3 = Regions::where('parent_id', $region_id_2['id'])->where('short_name', 'like', $region_3)->find();
                if (empty($region_id_3)) {
                    $v['err'][] = '区信息有误';
                    Log::info($v);
                    $code = 1;
                }

                $address = $region_id_1['name'] . $region_id_2['name'] . $region_id_3['name'] . $v['address'];
                $json_data = getLngLatByAddress($address);
                if (!isset($json_data->status) || $json_data->status != 0 || empty($json_data->result->location->lng)) {
                    $v['err'][] = '没有获取到经纬度';
                    Log::info($v);
                    $code = 1;
                }
                $lng = $json_data->result->location->lng;
                $lat = $json_data->result->location->lat;
                if (empty($lng) || empty($lat)) {
                    $v['err'][] = '没有获取到经纬度';
                    Log::info($v);
                    $code = 1;
                }
                $v['longitude'] = $lng;
                $v['latitude'] = $lat;
                // 商圈
                $distance_field = ",(st_distance (point (lng,lat),point({$lng},{$lat}) ) * 111195) as distance";
                $region_id_4 = Regions::where('parent_id', $region_id_3['id'])
                    ->where('level_type', 4)
                    ->field('*' . $distance_field)
                    ->order('distance', 'asc')
                    ->having('distance<' . $distance)
                    ->group('id')
                    ->find();
                // 分类
                $type_id = array_search($v['type_id'], $type_list);
                if ($type_id == false) {
                    $v['err'][] = '分类信息有误';
                    Log::info($v);
                    $code = 1;
                }
                $v['type_id_new'] = $type_id;
                $v['region_id_11'] = $region_id_1;
                $v['region_id_22'] = $region_id_2;
                $v['region_id_33'] = $region_id_3;
                $v['region_id_44'] = $region_id_4;
                $err_data = [
                    'shop_name' => $v['name'],
                    'type_name' => $v['type_id'],
                    'region_id_3' => isset($region_id_3['name']) ? $region_id_3['name'] : '',
                    'region_id_4' => isset($region_id_4['name']) ? $region_id_4['name'] : '',
                    'address' => $v['address'],
                    'err_msg' => isset($v['err']) ? json_encode($v['err']) : '',
                    'err_count' => isset($v['err']) ? count($v['err']) : 0,
                    'shop_info' => json_encode($v),
                    'branch_no' => $v['branch_no'],
                ];
                if ($code == 1) { // 有错误信息 加入到错误临时表
                    ShopImportErr::create($err_data);
                    unset($shop_list[$k]);
                    continue;
                }

                // 先匹配相似度
                $distance_field = ",(st_distance (point (s.longitude,s.latitude),point({$lng},{$lat}) ) * 111195) as distance";
                $like_list = (new Shops())->alias('s')
                    ->with('region3')
                    ->with('region4')
                    ->join('shop_type_shop st', 'st.shop_id=s.id')
                    ->join('shop_types t', 't.id=st.type_id')
                    ->field("s.*, t.name as type_name" . $distance_field)
                    ->where('st.type_id', $type_id)
                    ->where('s.name', $v['name'])
                    ->where('s.address', $v['address'])
                    ->group('s.id')
                    ->having('distance < 150')
                    ->select()->toArray();

                $like_data = [];
                if (!empty($like_list)) {
                    foreach ($like_list as $k8 => $v8) {
                        $like_data[] = [
                            'shop_name' => $v8['name'],
                            'type_name' => $v8['type_name'],
                            'region_id_3' => isset($v8['region_3'][0]['name']) ? $v8['region_3'][0]['name'] : '',
                            'region_id_4' => isset($v8['region_4'][0]['name']) ? $v8['region_4'][0]['name'] : '',
                            'address' => $v8['address'],
                            'data_source' => $v8['data_source'] == 1 ? '抓取' : ($v8['data_source'] == 2 ? '导入' : '后台添加'),
                            'shop_id' => $v8['id'],
                            'shop_info' => '',
                        ];
                    }
                }
                if (!empty($like_data)) {
                    unset($err_data['err_msg'], $err_data['err_count']);
                    $err_data['data_source'] = '导入';
                    Db::startTrans();
                    if ($like_id = Db::name('shop_import_like')->insertGetId($err_data)) {
                        foreach ($like_data as &$like_list) $like_list['pid'] = $like_id;
                    }
                    Db::name('shop_import_like')->insertAll($like_data);
                    Db::commit();
                    unset($shop_list[$k]);
                    continue;
                }

                $time = date('Y-m-d H:i:s', time());
                $res_data = [
                    'name' => $v['name'],
                    'address' => $v['address'],
                    'brand' => $v['brand'],
                    'longitude' => $lng,
                    'latitude' => $lat,
                    'region_id_1' => $region_id_1['id'],
                    'region_id_2' => $region_id_2['id'],
                    'region_id_3' => $region_id_3['id'],
                    'region_id_4' => isset($region_id_4['id']) ? $region_id_4['id'] : '',
                    'data_source' => 2,
                    'status' => 0,
                    'contacts' => $v['contacts'],
                    'phone_number' => $v['phone_number'],
                    'phone_number1' => $v['phone_number1'],
                    'create_time' => $time,
                    'preview_image' => isset($v['images'][0]) ? $v['images'][0] : 'shops/default/1.jpg',
                    'business_hours_new' => '[{"week":{"1":1,"2":2,"3":3,"4":4,"5":5,"6":6,"7":7},"time":"09:00-18:00"}]',
                    'acreage' => isset($v['acreage']) ? $v['acreage'] : '',
                    'scale' => isset($v['scale']) ? $v['scale'] : '',
                ];
                Db::startTrans();
                $shop_id = Db::name('shops')->insertGetId($res_data);
                // 法人信息表
                Db::name('shop_paperwork')->insert(['name' => isset($v['legal_person']) ? $v['legal_person'] : '', 'shop_id' => $shop_id]);
                // 分类
                $res_data = ['type_id' => $type_id, 'shop_id' => $shop_id];
                Db::name('shop_type_shop')->insert($res_data);
                // 图片
                $res_data = [];
                foreach ($v['images'] as $v5) {
                    $res_data[] = ['image_url' => $v5, 'shop_id' => $shop_id];
                }
                Db::name('shop_album')->insertAll($res_data);
                // 成功导入绑定批次号
                $res_data = [
                    'shop_id' => $shop_id,
                    'branch_no' => $branch_no,
                    'shop_name' => $v['name'],
                    'type_name' => $v['type_id'],
                    'region_id_3' => isset($region_id_3['name']) ? $region_id_3['name'] : '',
                    'region_id_4' => isset($region_id_4['name']) ? $region_id_4['name'] : '',
                    'address' => $v['address'],
                ];
                Db::name('shop_import_suc')->insert($res_data);
                Db::commit();
            }
            // 循环结束 查看该批次号队列是否还有数据
            $shop_list = $redis->lrange('import_shop_list_queue_' . $branch_no);
            if (empty($shop_list) || $shop_list == false) { // 为空，表示该批次号已经全部导入完毕
                ShopImport::where('branch_no', $branch_no)->setField('status', 3);
            }
        } catch (Exception $e) {
            Db::rollback();
            $output->error($e->getMessage());
        } catch (ErrorException $e2) {
            Db::rollback();
            $output->error($e2->getMessage());
        }
    }
}