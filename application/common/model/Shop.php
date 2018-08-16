<?php

namespace app\common\model;

use think\Model;

class Shop extends Model
{
    protected $connection = [
        'database'    => 's1s_new',
    ];

    protected $table = 's1s_shop';
}
