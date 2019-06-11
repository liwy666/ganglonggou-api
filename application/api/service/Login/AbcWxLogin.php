<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/6/11
 * Time: 12:58
 */

namespace app\api\service\Login;


use app\api\model\GlUser;
use app\lib\exception\CommonException;

class AbcWxLogin extends BaseLogin
{

    private $intoType;
    private $sonIntoType;
    private $abcWxOpenid;
    private $userInfo;

    public function __construct()
    {
        $this->abcWxOpenid = request()->param('abc_wx_openid');
        $this->intoType = 'abc';
        $this->sonIntoType = 'abc_wx';
    }

    /**
     * @return string
     * @throws CommonException
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * 返回token
     */
    public function giveToken()
    {

        $token = $this->getTokenByOpenId();

        return $token;
    }


    /**
     * @return string
     * @throws CommonException
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * 保存用户信息，返回token
     */
    private function getTokenByOpenId()
    {
        $this->userInfo = GlUser::where(['abc_wx_openid' => $this->abcWxOpenid])->find();

        if (!$this->userInfo) {
            //表示新用户
            $data = [
                'user_name' => "abc_app" . time(),
                'user_password' => md5("ganglong8888"),
                'login_ip' => request()->ip(),
                'user_img' => "head_portrait.png",
                'add_time' => time(),
                'login_time' => time(),
                'integral' => 0,
                'is_del' => 0,
                'login_count' => 1,
                'abc_wx_openid' => $this->abcWxOpenid
            ];
            $user_id = GlUser::create($data)->id;
        } else {
            $user_id = $this->userInfo['user_id'];
            //更新用户登录时间
            $data = [
                'login_ip' => request()->ip(),
                'login_time' => time()
            ];
            GlUser::where(['user_id' => $user_id])
                ->update($data);
            GlUser::where(['user_id' => $user_id])
                ->setInc('login_count');
        }

        $result['user_id'] = $user_id;
        $result['into_type'] = $this->intoType;
        $result['son_into_type'] = $this->sonIntoType;
        $token = self::saveToCache($result);

        return $token;
    }
}