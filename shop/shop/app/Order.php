<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    // 資料表
    protected $table = 'orderList';

    protected $fillable = [          // 使用批量分配（ Mass Assignment ）的填充白名單
        'id',
        'userAccount',
        'account',
        'orderNumber',
        'name',
        'phone',
        'address',
        'totalAmount',
        'promsPrice',
        'currentProms', 
        'goodsIndo',
        'status',
        'cancelOrder',
        'coupon',
        'payment',
        'memo'
    ];

    // protected $hidden = [           // 隱藏 model 的陣列或 JSON 的屬性 
    //     'id',
    // ];
}
