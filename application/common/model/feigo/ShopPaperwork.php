<?php
namespace app\common\model\feigo;

use think\Model;

class ShopPaperwork extends Model
{
    protected $connection = [
        'database'    => 'feigo',
    ];

    protected $table = 'feigo_shop_paperwork';

      /**
     *门店资质参数审核 
     *@param 用户上传资质信息
     *@return bool
     *  
     */
    public static function checkParam($paperwork_info)
    {
        foreach ($paperwork_info as $key=>$value){
            if(($paperwork_info['license_status'] =='1' && $key!='license_type' && $key!='license_name' && empty($value)) || ($paperwork_info['license_status'] =='2' && !in_array($key, ['license_type','license_name','license_number','license_img']) && empty($value))){
               return false;         
            }
            
        }
        return true;
    }
}
