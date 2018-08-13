<?php
namespace app\common\model\feigo;

use think\Model;

class MemberOpen extends Model
{
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'timestamp';

    /**
     * 用户第三方open关联用户
     * @return \think\model\relation\HasOne
     */
    public function memberInfo()
    {
        return $this->hasOne('Members', 'id', 'member_id');
    }
}
