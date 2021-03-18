<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Goods extends Model
{
    // 資料表
    protected $table = 'goods';

    protected $fillable = [          // 使用批量分配（ Mass Assignment ）的填充白名單
        'startDate', 'endDate', 'name', 'info', 'amount', 'total', 'goodsType', 'isRecommon', 'images', 'forcedRemoval', 'isDestroy'
    ];

    // protected $hidden = [           // 隱藏 model 的陣列或 JSON 的屬性 
    //     'id',
    // ];
}
