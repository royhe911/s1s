<?php
namespace app\common\model\feigo;

use think\Model;

class GoodAttuributeKeys extends Model
{
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'timestamp';

    public function goodAttuributeValues()
    {
        return $this->hasMany("GoodAttuributeValues","good_atturibute_key_id","id");
    }
}
