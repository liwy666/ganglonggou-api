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
use think\facade\Cache;
use think\facade\Log;

class AppWeChatLogin extends BaseLogin
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
        $wx_user_info = self::getWeChatUserInfoForApp($this->code);

        $user_info = GlUser::where(['wx_openid' => $wx_user_info['openid']])->find();

        if (!$user_info) {
            //新用户
            $data = [
                'user_name' => $wx_user_info['nickname'],
                'user_password' => md5("ganglong8888"),
                'login_ip' => request()->ip(),
                'user_img' => $wx_user_info['head_img_file'],
                'add_time' => time(),
                'login_time' => time(),
                'integral' => 0,
                'is_del' => 0,
                'login_count' => 1,
                'wx_openid' => $wx_user_info['openid']
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