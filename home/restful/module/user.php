<?php
    class User {
        public static function getlist() {
            require_once('./module/conn.php');

            $sql = "SELECT * FROM user";

            $result = $conn->query($sql);
            $data = Array();

            while ($row = $result->fetch_assoc()) {
                if (!$row['name']) {
                    continue;
                }
                $user = Array(
                    'name'=>$row['name'],
                    'id'=>$row['id']
                );
                array_push($data, $user);
            }

            return $data;
        }
        static function delUser($data) {
            require_once('./module/conn.php');

            $id = $data['id'];
            $sql = sprintf(
                "DELETE FROM user where id='%s'",
                $id
            );

            $result = $conn->query($sql);

            $infoResult = Array(
                'code'=> 200,
                'data'=> [],
                'msg'=> '刪除成功'
            );

            if (!$result) {
                $infoResult['msg'] = '刪除失敗('. $conn->error . ')';
            }

            return $infoResult;
        }
        static function addUser($data) {
            require_once('./module/conn.php');

            $account = $data['account'];
            $password = $data['password'];

            $sql = sprintf(
                "INSERT INTO user(name, password) VALUES ('%s', '%s')",
                $account,
                $password
            );
            $infoResult = Array(
                'code'=> 200,
                'data'=> [],
                'msg'=> ''
            );
            $result = $conn->query($sql);
            $infoResult['msg'] = '新增成功';

            if (!$result) {
                $infoResult['msg'] = '新增失敗('. $conn->error . ')';
            }

            return $infoResult;
        }
    }
?>