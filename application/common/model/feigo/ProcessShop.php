<?php
namespace app\common\model\feigo;

use think\Model;

class ProcessShop extends Model
{

    protected $connection = [
        'database' => 'feigo'
    ];
    
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'timestamp';

    /**
     * 省级
     *
     * @return \think\model\relation\HasMany
     */
    public function region1()
    {
        return $this->hasMany('Regions', 'id', 'region_id_1');
    }

    /**
     * 市级
     *
     * @return \think\model\relation\HasMany
     */
    public function region2()
    {
        return $this->hasMany('Regions', 'id', 'region_id_2');
    }

    /**
     * 区级
     *
     * @return \think\model\relation\HasMany
     */
    public function region3()
    {
        return $this->hasMany('Regions', 'id', 'region_id_3');
    }

    /**
     * 商圈级
     *
     * @return \think\model\relation\HasMany
     */
    public function region4()
    {
        return $this->hasMany('Regions', 'id', 'region_id_4');
    }

    /**
     *
     * @param 门店信息 $shop_info            
     * @param 用户id $member_id            
     * @param 操作类型 $type
     *            1:校验 2:修改 3:新增
     */
    public static function addProcessLog($shop_info, $member_id, $type)
    {
        if (! empty($shop_info)) {
            $shop_types = ShopTypes::alias('st')->join('shop_type_shop sts', 'sts.type_id=st.id')
                ->field("st.id,st.name")
                ->where("sts.shop_id", $shop_info['id'])
                ->select();
            $types = "";
            foreach ($shop_types as $value) {
                $types = $types . $value['name'] . ",";
            }
            
            $data['shop_name'] = $shop_info['name'];
            $data['region_id_1'] = $shop_info['region_id_1'];
            $data['region_id_2'] = $shop_info['region_id_2'];
            $data['region_id_3'] = $shop_info['region_id_3'];
            $data['region_id_4'] = $shop_info['region_id_4'];
            $data['address'] = $shop_info['address'];
            $data['shop_type'] = trim($types, ",");
            $data['shop_id'] = $shop_info['id'];
            $data['member_id'] = $member_id;
            $data['type'] = $type;
            self::create($data);
        }
    }
}
