<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/5/15
 * Time: 15:30
 */

namespace app\api\controller\v1\cms;


use app\api\service\Login\AdminLogin;
use app\api\validate\CurrencyValidate;

class CmsAdmin
{
    /**
     * @return string
     * @throws \app\lib\exception\CommonException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 管理员登录
     */
    public function adminLogin(){
        //验证必要
        (new CurrencyValidate()) ->myGoCheck(['name','password'],"require");

        return (new AdminLogin())->giveToken();
    }
}