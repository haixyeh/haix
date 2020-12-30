<?php

namespace App;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;

class Categories extends Model
{
    use Notifiable;
    // 商品分類資料表 - 不由migerate 建立
    protected $table = 'categories';

    protected $fillable = [          // 使用批量分配（ Mass Assignment ）的填充白名單
        'id', 'name', 'info', 'preid'
    ];

    protected $hidden = [           // 隱藏 model 的陣列或 JSON 的屬性 
        'created_at'
    ];
}
