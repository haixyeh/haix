<?php

namespace App;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;

class adminUser extends Model
{
    use Notifiable;
     // 資料表
    protected $table = 'adminUsers';
    protected $fillable = [          // 使用批量分配（ Mass Assignment ）的填充白名單
        'name', 'email', 'password', 'api_token'
    ];
    protected $hidden = [           // 隱藏 model 的陣列或 JSON 的屬性 
        'password',
    ];
}
