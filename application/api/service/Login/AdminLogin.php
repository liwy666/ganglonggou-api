<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/5/15
 * Time: 15:42
 */

namespace app\api\service\Login;


use app\api\model\GlAdmin;
use app\api\service\Token\Token;
use app\lib\exception\CommonException;

class AdminLogin extends BaseLogin
{
    private $adminName;
    private $adminPassword;

    //private $adminAction;

    public function __construct()
    {
        $this->adminName = request()->param("name");
        $this->adminPassword = request()->param("password");
    }

    /**
     * @return string
     * @throws CommonException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 保存登录，返回token
     */
    public function giveToken()
    {

        $admin_model = GlAdmin::where(['admin_name' => $this->adminName, 'admin_password' => $this->adminPassword])->find();
        if (!$admin_model) {
            throw new CommonException(['msg' => '账户或密码错误', 'code' => 500]);
        }
        $admin_info['admin_id'] = $admin_model->admin_id;
        $admin_info['admin_action'] = $admin_model->admin_action;

        //永久保存token
        $token = self::saveToCache7Day($admin_info);

        return $token;

    }


}