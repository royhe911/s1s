<?php
namespace app\common\model\feigo;

use think\Model;
use app\common\model\feigo\Shops;

class BusinessShop extends Model
{
    protected $connection = [
        'database'    => 'feigo',
    ];

    protected $table = 'feigo_business_shop';

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'timestamp';

    /**
     * 区级
     * @return \think\model\relation\HasMany
     */
    public function region_3()
    {
        return $this->hasOne('Regions', 'id', 'region_id_3')->field("id,name");
    }

    /**
     * 商圈级
     * @return \think\model\relation\HasMany
     */
    public function region_4()
    {
        return $this->hasOne('Regions', 'id', 'region_id_4')->field("id,name");
    }

    /**
     * 根据商户id获取门店
     * @param $member_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getShopsByBusinessId($member_id)
    {
        $shop = self::where('business_id', $member_id)->field('shop_id, status, message, shop_name, address')->select()->toArray();
        foreach ($shop as $k=>$v){
            if($v['status'] == '1'){
                $shop[$k]['shop_name'] = Shops::where("id","=",$v['shop_id'])->value('name');
            }
        }
        return $shop;
    }

    /**
     * 根据商户id和门店id获取单个门店
     * @param $member_id
     * @param $shop_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getShopByBusinessIdAndShopId($member_id, $shop_id)
    {
        return self::where('business_id', $member_id)->where('shop_id', $shop_id)->find();
    }

    /**
     * 获取当前自己认领的门店
     * @param $member_id
     * @return mixed
     */
    public static function getMyShopId($member_id)
    {
        return self::alias('bs')
            ->join('shops s', 's.id=bs.shop_id')
            ->where('bs.business_id', $member_id)
            ->where('s.is_delete', 0)
            ->where('s.is_user_status', 1)
            ->value('bs.shop_id');
    }

    /**
     * 获取首页状态
     * @param $member_info
     * @return mixed
     */
    public static function getIndexStatus($member_info)
    {
        $refuse_status = '';
        $reason = '';
        $business_id = '';
        $shop_id = '';
        $shop_name = '';

        if(empty($member_info)){
            $status = '1'; //未登陆
        }else{
            $business_shop = self::where('business_id',$member_info['id'])->order('id desc')->find();
            if(empty($business_shop)){
                $status = '2'; //登陆了，未认领
                $shop_id = $member_info['shop_id'];
            }else{
                $shop_name = $business_shop['shop_name'];
                $business_id = $business_shop['id'];
                $shop_id = $business_shop['shop_id'];
                $status = '3'; //提交资质
                if($business_shop['status'] == '1'){
                    $status = '4'; //认领审核成功，即已开通
                    $shop_name = Shops::where("id","=",$shop_id)->value('name');

                }else{
                    if($business_shop['status'] == '3'){
                        $refuse_status = '1';
                        $reason = $business_shop['message'];
                    }elseif($business_shop['status'] == '2'){
                        $refuse_status = '0';
                        $reason = "资质提交成功，3个工作日内会出审核结果！";
                    }else{
                        $refuse_status = '2';
                        $reason = $business_shop['message'];
                    }
                }

            }
        }

        $data = [
            'status' => $status,
            'refuse_status' => $refuse_status,
            'reason' => $reason,
            'business_id' => $business_id,
            'shop_id' => $shop_id,
            'shop_name' => $shop_name
        ];
        return $data;
    }
}
