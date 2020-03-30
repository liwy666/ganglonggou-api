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
            //新用户
            $insert_info_array = ['abc_wx_openid' => $this->abcWxOpenid];
            $user_id = self::addUser($insert_info_array,'abc_wx');
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