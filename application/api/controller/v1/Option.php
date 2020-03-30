<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/5/16
 * Time: 14:13
 */

namespace app\api\controller\v1;


use think\facade\Response;

class Option
{
    public function returnTrue()
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: Authorization, Content-Type, If-Match, If-Modified-Since, If-None-Match, If-Unmodified-Since,X-Requested-With, X_Requested_With');
        header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE');

        return Response::create()->send();
    }

}