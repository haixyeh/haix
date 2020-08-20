<?php
    require_once('../../controller/mem_conn.php');

    $radomNumber = '';
    for ($i=0; $i < 4 ; $i++) { 
        $radomNumber = $radomNumber . rand(1,9);
    }
    $memcache->set('randomNumber',$radomNumber,false,100000) or die("Failed to save data at the server");   //1000为过期时间
    $get_result = $memcache->get('randomNumber');

    $result = array(
        'code'  => 200,
        'haha'  => $get_result
    );

    echo json_encode($result);
?>