<?php
    require_once('../../controller/mem_conn.php');

    $data = json_decode(file_get_contents('php://input'), true);
    
    // 設定的隨機數值
    $real_number = $memcache->get('randomNumber');;
    // 陣列化字串
    $real_number_sp = str_split($real_number);
    $user_number_sp = str_split($data["value"]);
    // 1A2B 參數
    $a = 0;
    $b = 0;
    // message
    $msg = '';
    
    // 處理 A
    for($i=0 ; $i<4 ; $i++ ) {
        if ($user_number_sp[$i] == $real_number_sp[$i]) {
            $a += 1;
            unset($user_number_sp[$i]);
            continue;
        } 
    }

    // 處理 B
    if ($a <= 3) {
        for($i=0 ; $i<4 ; $i++ ) {
            if (in_array($user_number_sp[$i], $real_number_sp)) {
                $b += 1;
            }
        }
    } else {
        $msg = '猜對啦！';
    }


    $result = array(
        'code'  => 200,
        'data'  => array(
            'value' => $data["value"],
            'text'  => $a . 'A' . $b . 'B',
            'a' => $a,
            'b' => $b
        ),
        'msg'   => $msg
    );

    echo json_encode($result);
?>