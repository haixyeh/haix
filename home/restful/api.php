<?php
    ini_set('display_errors', 1); //顯示錯誤訊息
    ini_set('log_errors', 1); //錯誤log 檔開啟
    error_reporting(E_ALL); //錯誤回報

    require_once("./control/rest.php");
    require_once("./module/user.php");

    $data = RestUtils::processRequest();
    
    switch($data->getMethod()){  
        // this is a request for all users
        case 'get':  
            $user_list = User::getlist(); // assume this returns an array  
      
            if($data->getHttpAccept() == 'json'){  
                RestUtils::sendResponse(200, json_encode($user_list), 'application/json');  
            }else if ($data->getHttpAccept() == 'xml') {  
                $options = array  
                (  
                    'indent' => '     ',  
                    'addDecl' => false,  
                    'rootName' => $fc->getAction(),  
                    XML_SERIALIZER_OPTION_RETURN_RESULT => true  
                );  
                $serializer = new XML_Serializer($options);  
      
                RestUtils::sendResponse(200, $serializer->serialize($user_list), 'application/xml');  
            }  
      
            break;  
        case 'post':  
            $data = $data->getRequestVars();
            $user = new User();
            $reslut = $user->addUser($data);

            RestUtils::sendResponse(200, json_encode($reslut), 'application/json');
            break;
        case 'delete':
            $data = $data->getRequestVars();
            $user = new User();
            $reslut = $user->delUser($data);

            RestUtils::sendResponse(200, json_encode($reslut), 'application/json');
            break;
    }  
?>