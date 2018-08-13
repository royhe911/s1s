<?php
namespace app\common\model\feigo;

use think\Model;

class Article extends Model
{
    protected $connection = [
        'database'    => 'feigo',
    ];

    protected $table = 'feigo_article';

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'timestamp';

    public function articleType()
    {
        return $this->hasOne('ArticleType', 'id', 'type_id')->bind(['type_name'	=> 'name']);
    }
}
