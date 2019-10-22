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

class WxLogin extends BaseLogin
{
    private $intoType;
    private $sonIntoType;
    private $appId;
    private $secret;
    private $code;
    private $wxOpenid;
    private $userInfo;

    /**
     * WxLogin constructor.
     * @param $code
     * @throws CommonException
     */
    public function __construct($code)
    {
        $this->intoType = 'wx';
        $this->sonIntoType = 'wx';
        $this->code = $code;
        $this->getWxOpenId();
    }

    /**
     * @throws CommonException
     */
    private function getWxOpenId()
    {
        $this->appId = config('my_config.wx_app_id');
        $this->secret = config('my_config.wx_secret');

        $this->wxOpenid = Cache::get($this->code . '_user_wx_openid');

        if (!$this->wxOpenid) {
            $url = "https://api.weixin.qq.com/sns/oauth2/access_token?"
                . "appid=" . $this->appId
                . "&secret=" . $this->secret
                . "&code=" . $this->code
                . "&grant_type=authorization_code";
            $curl = curl_init(); // 启动一个CURL会话
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_HEADER, 0);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);  // 从证书中检查SSL加密算法是否存在
            $tmpInfo = curl_exec($curl);     //返回api的json对象		    //关闭URL请求
            curl_close($curl);

            $getInfo = json_decode($tmpInfo, true);

            if (!is_array($getInfo) || !array_key_exists('openid', $getInfo)) {
                Log::write($getInfo, 'error');
                throw new CommonException(['msg' => '获取用户信息失败', 'code' => '400', 'error_code' => 10002]);
            }
            Log::write($getInfo, 'error');
            $this->wxOpenid = $getInfo['openid'];
            //永久缓存openid
            Cache::set($this->code . '_user_wx_openid', $getInfo['openid'], 0);
        }
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
     * @return string
     * @throws CommonException
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * 通过微信openId换取Token
     */
    private function getTokenByOpenId()
    {
        $this->userInfo = GlUser::where(['wx_openid' => $this->wxOpenid])->find();

        if (!$this->userInfo) {
            //新用户
            $insert_info_array = ['wx_openid' => $this->wxOpenid];
            $user_id = self::addUser($insert_info_array, 'wx');
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