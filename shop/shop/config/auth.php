<?php

return [
    'defaults' => [
        'guard' => 'web',
        'passwords' => 'users',
    ],
    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
        'api' => [
            'driver' => 'token',
            'provider' => 'users',
            'hash' => false,
        ],
        'haix' => [
            'driver' => 'token',        // 使用 token 當作入門票
            'provider' => 'haix',
            'hash' => false,
        ],
    ],
    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Users::class,
        ],
        'haixs' => [
            'driver' => 'eloquent',          // 參照 eloquent 建立的 model
            'model' => App\Users::class,    // 對應 app/Flower.php 檔案內的 class 
        ],
    ],

];