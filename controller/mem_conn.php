<?php
    $memcache = new Memcache();
    $memcache->connect('haix.com',11211) or die ("could not connect");
?>