
<?php
ini_set('display_errors', 1); //顯示錯誤訊息
ini_set('log_errors', 1); //錯誤log 檔開啟
error_reporting(E_ALL); //錯誤回報
    //连接本地的 Redis 服务
   $redis = new Redis();
   $redis->connect('127.0.0.1', 6379);
   echo "Connection to server successfully";
         //查看服务是否运行
   echo "Server is running: ". $redis->ping()."<br/>\n";
   echo "<br/>\n";
   echo "<br/>\n";
   echo "<br/>\n";
?>

<?php
ini_set('display_errors', 1); //顯示錯誤訊息
ini_set('log_errors', 1); //錯誤log 檔開啟
error_reporting(E_ALL); //錯誤回報
$memcache = new Memcache();

$memcache->connect('haix.com',11211) or die ("could not connect");
$version = $memcache->getVersion();
echo "Server's version: ".$version."<br/>\n";
$memcache->set('key','hello everybody',false,1000) or die("Failed to save data at the server");   //1000为过期时间
echo "Store data in the cache(data will expire in 1000 seconds)<br/>\n";
$get_result = $memcache->get('key');
echo "Date from the cache:<br/>\n";
var_dump($get_result);
?>