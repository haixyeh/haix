<?php
    $servername = "haix.com";
    $username = 'haix';
    $password = 'qwe123';
    $db_name = 'haix';

    $conn  = new mysqli($servername, $username, $password, $db_name);

    $conn -> query('SET NAMES UTF8');
    $conn -> query('SET time_zone = "+8:00"');
?>