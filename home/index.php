<?php
    $file = './view/index.html';

    if (is_file($file)) {
        $content = @file_get_contents($file);
    }
    
    $content = preg_replace('/\.css/', ".css?v=" .time(), $content);
    $content = preg_replace('/\.js/', ".js?v=" .time(), $content);

    die($content);
?>