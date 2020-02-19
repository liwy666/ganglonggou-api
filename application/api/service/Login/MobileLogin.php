<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/7/1
 * Time: 11:17
 */

namespace app\api\service\Login;


use app\api\model\GlUser;
use app\lib\exception\CommonException;

class MobileLogin extends BaseLogin
{
    private $intoType;
    private $sonIntoType;

    public function __construct()
    {
        $this->intoType = 'wx';
        $this->sonIntoType = 'mobile';
    }

    /**
     * @param $phone
     * @param $password
     * @return string
     * @throws \app\lib\exception\CommonException
     * 用户注册
     */
    public function mobileRegister($phone, $password)
    {
        $data = [
            'user_name' => 'mobile' . time(),
            'user_password' => md5($password),
            'login_ip' => request()->ip(),
            'user_img' => "head_portrait.png",
            'add_time' => time(),
            'login_time' => time(),
            'integral' => 0,
            'is_del' => 0,
            'login_count' => 1,
            'phone' => $phone
        ];
        $user_id = GlUser::create($data)->id;

        $result['user_id'] = $user_id;
        $result['into_type'] = $this->intoType;
        $result['son_into_type'] = $this->sonIntoType;
        $token = self::saveToCache($result);

        return $token;
    }

    /**
     * @param $phone
     * @param $password
     * @return string
     * @throws CommonException
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * 用户登录
     */
    public function mobilLogin($phone, $password)
    {
        $user_info = GlUser::where([
            ['phone', '=', $phone],
            ['is_del', '=', 0]
        ])->find();
        if (!$user_info) {
            throw new CommonException(['msg' => '无此用户']);
        }
        if ($user_info['user_password'] !== $password) {
            throw new CommonException(['msg' => '密码错误']);
        }

        self::recordUserLogin($user_info['user_id']);

        $result['user_id'] = $user_info['user_id'];
        $result['into_type'] = $this->intoType;
        $result['son_into_type'] = $this->sonIntoType;
        $token = self::saveToCache7Day($result);

        return $token;
    }
}