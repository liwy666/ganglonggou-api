<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/6/11
 * Time: 12:42
 */

namespace app\api\service\Login;


use app\api\model\GlUser;
use app\lib\exception\CommonException;

class AbcNewAppLogin extends BaseLogin
{
    private $abcAppId;
    private $id;
    private $intoType;
    private $sonIntoType;
    private $abcAppOpenid;
    private $userInfo;

    public function __construct()
    {
        $this->id = request()->param('id');
        $this->intoType = 'abc';
        $this->sonIntoType = 'abc_app';
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

        return $this->getTokenByOpenId();

    }


    /**
     * 获取openId
     */
    private function getOpenId()
    {


        $this->abcAppOpenid = $this->id;

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

        $this->getOpenId();

        $this->userInfo = GlUser::where(['abc_app_openid' => $this->abcAppOpenid])->find();

        if (!$this->userInfo) {
            //表示新用户
            $insert_info_array = ['abc_app_openid' => $this->abcAppOpenid];
            $user_id = self::addUser($insert_info_array, 'abc_app');
        } else {
            //老用户
            $user_id = $this->userInfo['user_id'];
            self::recordUserLogin($user_id);
        }

        $result['user_id'] = $user_id;
        $result['into_type'] = $this->intoType;
        $result['son_into_type'] = $this->sonIntoType;
        $token = self::saveToCache($result);

        return $token;
    }
}