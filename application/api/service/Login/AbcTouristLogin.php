<?php


namespace app\api\service\Login;

use app\api\model\GlUser;
use app\lib\exception\CommonException;

class AbcTouristLogin extends BaseLogin
{
    private $intoType;
    private $sonIntoType;
    private $abcTouristId;
    private $userInfo;

    public function __construct()
    {
        $this->abcTouristId = request()->param('abcTouristId');
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

        return $this->getTokenByTestAppIdAndId();
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

        $this->userInfo = GlUser::where(['abc_tourist_id' => $this->abcTouristId])->find();

        if (!$this->userInfo) {
            //表示新用户
            $insert_info_array = ['abc_tourist_id' => $this->abcTouristId];
            $user_id = self::addUser($insert_info_array, '游客');
        } else {
            //老用户
            $user_id = $this->userInfo['user_id'];
            self::recordUserLogin($user_id);
        }

        $result['user_id'] = $user_id;
        $result['into_type'] = $this->intoType;
        $result['son_into_type'] = $this->sonIntoType;
        return self::saveToCache7Day($result);


    }
}