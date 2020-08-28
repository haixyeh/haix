# haix
    1. 1A2B 作業
    https://github.com/haixyeh/haix/tree/master/work

    路徑位置: {Domain}/work/
    
    2. Rest API
    https://github.com/haixyeh/haix/tree/master/home

    路徑位置: {Domain}/home/
    
    文件位置：
    https://docs.google.com/document/d/1c1GjX2FvM1nkgz0EsM-EUX_9kRSbqHfvxaTQlYinnsY/edit#

    nginx.conf 需新增以下設定
    ```
        rewrite ^/user/?$ /home/restful/api.php last;
		rewrite ^/user/(\d+)/?$ /home/restful/api.php?id=$1 last;

        location / {
			try_files $uri $uri/ =404;
		}
    ```


    3. 研究 MemCache Redis MySql
        文件網址： https://is.gd/Zjbqhm
    4. 研究Laravel框架 (尚未執行)