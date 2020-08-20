<?php
    $file = './view/index.html';

    if (is_file($file)) {
        $content = @file_get_contents($file);
    }
    
    die($content);
?>