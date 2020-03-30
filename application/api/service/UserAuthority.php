<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/5/15
 * Time: 15:09
 */

namespace app\api\service;


use app\api\service\Login\BaseLogin;
use app\lib\exception\CommonException;
use think\facade\Request;

class UserAuthority extends BaseLogin
{
    /**
     * @param $code
     * @return mixed
     * @throws CommonException
     * 检查用户权限
     */
    public static function checkAuthority($code){
        $request =  Request::instance();
        $admin_token = $request->param("admin_token");
        $admin_desc = self::getCurrentIdentity(['admin_id',"admin_action"],$admin_token);

        if($admin_desc["admin_action"] < $code){
            throw new CommonException(["msg" => "权限不足",'code' => 500]);
        }else{
            return $admin_desc["admin_id"];
        }
    }
}