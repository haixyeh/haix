<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Level extends Model
{
    // 資料表
    protected $table = 'level';

    protected $fillable = [          // 使用批量分配（ Mass Assignment ）的填充白名單
        'id', 'levelName', 'offer', 'offerType', 'full', 'discount', 'present', 'upgradeAmount'
    ];

    // protected $hidden = [           // 隱藏 model 的陣列或 JSON 的屬性 
    //     'id',
    // ];
}
