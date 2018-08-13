<?php
namespace app\common\model\feigo;

use think\Model;

class Help extends Model
{
    protected $connection = [
        'database'    => 'feigo',
    ];

    protected $table = 'feigo_help';

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'timestamp';

    /**
     * 门店分类中间表
     * @return $this
     */
    public function helpTypeHelp()
    {
        return $this->hasMany('helpTypeHelp', 'help_id', 'id')->field('type_id, help_id');
    }
}
