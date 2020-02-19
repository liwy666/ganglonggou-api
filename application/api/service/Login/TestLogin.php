<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/5/23
 * Time: 9:34
 */

namespace app\api\service\Login;


use app\api\model\GlUser;
use app\lib\exception\CommonException;

class TestLogin extends BaseLogin
{
    private $testAppId;
    private $id;
    private $intoType;
    private $sonIntoType;
    private $testOpenid;
    private $userInfo;

    public function __construct()
    {
        $this->testAppId = request()->param('test_app_appid');
        $this->id = request()->param('id');
        $this->intoType = 'wx';
        $this->sonIntoType = 'wx';
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

        $token = $this->getTokenByTestAppIdAndId();

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
    private function getTokenByTestAppIdAndId()
    {
        if ($this->testAppId !== '1CIOOHCD70050101007F00000910CACD') {
            throw new CommonException(['msg' => '登录信息验证不通过']);
        }
        if ($this->id !== 'fc196654571f8ba9a893350cbc40a59fceb615257d436a67') {
            throw new CommonException(['msg' => '登录信息验证不通过']);
        }
        //随便生成一个
        $this->testOpenid = 'eEcNpqDL37MerJW6rfqJSTdpD683B8s5';

        $this->userInfo = GlUser::where(['test_openid' => $this->testOpenid])->find();

        if (!$this->userInfo) {
            //表示新用户
            $insert_info_array = ['test_openid' => $this->testOpenid];
            $user_id = self::addUser($insert_info_array,'test');
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