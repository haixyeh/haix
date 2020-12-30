<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserInfo extends Model
{
    // 資料表
    protected $table = 'userInfo';

    protected $fillable = [          // 使用批量分配（ Mass Assignment ）的填充白名單
        'account', 'firstName', 'lastName', 'address', 'address', 'email'
    ];
}
