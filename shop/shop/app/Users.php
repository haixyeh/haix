<?php

namespace App;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Users extends Authenticatable {
    use Notifiable;

    protected $fillable = [          // 使用批量分配（ Mass Assignment ）的填充白名單
        'name', 'email', 'password', 'api_token', 'id', 'cost', 'coupon', 'level', 'isDel'
    ];

    protected $hidden = [           // 隱藏 model 的陣列或 JSON 的屬性 
        'password',
        'api_token'
    ];
}
