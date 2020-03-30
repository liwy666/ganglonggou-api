<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/6/20
 * Time: 14:12
 */

namespace app\api\service\Login;


use app\api\model\GlUser;
use app\lib\exception\CommonException;
use think\facade\Log;

class AppAliPayLogin extends BaseLogin
{
    private $intoType;
    private $sonIntoType;
    private $code;


    /**
     * AppWeChatLogin constructor.
     * @param $code
     * @param $son_into_type
     */
    public function __construct($code, $son_into_type)
    {
        $this->intoType = 'wx';
        $this->sonIntoType = $son_into_type;
        $this->code = $code;
    }

    /**
     * @return string
     * @throws CommonException
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function getToken()
    {
        $ali_pay_user_info = self::getAliPayUserInfoForApp($this->code);

        if (!$ali_pay_user_info['ali_pay_user_id']) {
            throw new CommonException(['msg' => '获取用户信息失败']);
        }

        $user_info = GlUser::where(['ali_pay_user_id' => $ali_pay_user_info['ali_pay_user_id']])->find();

        if (!$user_info) {
            //新用户
            $data = [
                'user_name' => $ali_pay_user_info['nick_name'],
                'user_password' => md5("ganglong8888"),
                'login_ip' => request()->ip(),
                'user_img' => $ali_pay_user_info['head_img_file'],
                'add_time' => time(),
                'login_time' => time(),
                'integral' => 0,
                'is_del' => 0,
                'login_count' => 1,
                'ali_pay_user_id' => $ali_pay_user_info['ali_pay_user_id']
            ];

            $user_id = GlUser::create($data)->id;
        } else {
            //老用户
            $user_id = $user_info['user_id'];
            self::recordUserLogin($user_id);
        }

        $result['user_id'] = $user_id;
        $result['into_type'] = $this->intoType;
        $result['son_into_type'] = $this->sonIntoType;
        $token = self::saveToCache7Day($result);

        return $token;
    }
}