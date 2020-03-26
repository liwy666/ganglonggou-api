<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/5/23
 * Time: 9:27
 */

namespace app\api\service\Login;


use app\api\model\GlUser;
use app\api\service\AliPay\AliPay;
use app\api\service\DownloadImage;
use app\lib\exception\CommonException;
use think\facade\Cache;
use think\facade\Log;

class BaseLogin
{
    /**
     * @return string
     * 生成令牌
     */
    public static function generateToken()
    {
        $randChar = getRandChar(32);
        $timestamp = $_SERVER['REQUEST_TIME_FLOAT'];//得到请求开始时的时间戳
        $tokenSalt = config('my_config.token_salt');
        return md5($randChar . $timestamp . $tokenSalt);
    }

    /**
     * @param $result
     * @return string
     * @throws CommonException
     * 写入缓存
     */
    public static function saveToCache($result)
    {
        $key = self::generateToken();
        $value = json_encode($result);
        $expire_in = config('my_config.token_expire_in');
        $result = cache($key, $value, $expire_in);
        if (!$result) {
            throw new CommonException([
                'msg' => '服务器缓存异常',
                'errorCode' => 10005
            ]);
        }
        return $key;
    }

    /**
     * @param $result
     * @return string
     * @throws CommonException
     * 永久写入缓存
     */
    public static function saveToCache7Day($result)
    {
        $key = self::generateToken();
        $value = json_encode($result);
        $expire_in = 0;
        $result = cache($key, $value, $expire_in);
        if (!$result) {
            throw new CommonException([
                'msg' => '服务器缓存异常',
                'errorCode' => 10005
            ]);
        }
        return $key;
    }

    /**
     * @param $keys //获取那种信息
     * @param $token
     * @return array
     * @throws CommonException
     * 从缓存中获取当前用户指定身份标识
     */
    public static function getCurrentIdentity($keys, $token)
    {
        /*设置header头有问题，暂时换个方式
        $token = Request::instance()
            ->header('token');
        */
        $identities = \think\facade\Cache::get($token);
        //cache 助手函数有bug
        // $identities = cache($token);

        if (!$identities) {
            throw new CommonException(['msg' => '获取用户信息失败', 'code' => '400', 'error_code' => 10002]);
        } else {
            if (!is_array($identities)) {
                $identities = json_decode($identities, true);
            }
            $result = [];
            foreach ($keys as $key) {
                if (array_key_exists($key, $identities)) {
                    $result[$key] = $identities[$key];
                }
            }
            return $result;
        }
    }

    /**
     * @param array $insert_info_array
     * @param string $user_head
     * @return mixed
     * 添加新用户
     */
    public static function addUser($insert_info_array = [], $user_head = 'routine')
    {
        //表示新用户
        $data = [
            'user_name' => $user_head . time(),
            'user_password' => md5("ganglong8888"),
            'login_ip' => request()->ip(),
            'user_img' => "head_portrait.png",
            'add_time' => time(),
            'login_time' => time(),
            'integral' => 0,
            'is_del' => 0,
            'login_count' => 1,
        ];
        foreach ($insert_info_array as $k => $v) {
            $data[$k] = $v;
        }

        return GlUser::create($data)->id;

    }

    /**
     * @param $user_id
     * @return bool
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 记录用户登录
     */
    public static function recordUserLogin($user_id)
    {
        $data = [
            'login_ip' => request()->ip(),
            'login_time' => time()
        ];
        //更新用户登录时间
        GlUser::where(['user_id' => $user_id])
            ->update($data);
        GlUser::where(['user_id' => $user_id])
            ->setInc('login_count');

        return true;
    }


    /**
     * @param $code
     * @return mixed
     * @throws CommonException
     * APP获取微信用户信息
     */
    public static function getWeChatUserInfoForApp($code)
    {
        $app_id = config('my_config.app_wx_app_id');
        $secret = config('my_config.app_wx_secret');
        $openid_cache_name = $code . '_app_wx_openid';
        $grant_type = 'authorization_code';
        $debug = config('my_config.debug');

        $openid = Cache::get($openid_cache_name);
        if (!$openid || $debug) {
            $get_access_url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=$app_id&secret=$secret&code=$code&grant_type=$grant_type";
            $curl = curl_init(); // 启动一个CURL会话
            curl_setopt($curl, CURLOPT_URL, $get_access_url);
            curl_setopt($curl, CURLOPT_HEADER, 0);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);  // 从证书中检查SSL加密算法是否存在
            $tmpInfo = curl_exec($curl);     //返回api的json对象		    //关闭URL请求
            curl_close($curl);
            $access_info = json_decode($tmpInfo, true);
            if (!is_array($access_info) || !array_key_exists('openid', $access_info)) {
                Log::write($access_info, 'error');
                throw new CommonException(['msg' => 'app获取微信access信息失败', 'code' => '500']);
            }
            $openid = $access_info['openid'];
            $access_token = $access_info['access_token'];
            //通过code永久缓存openid
            Cache::set($openid_cache_name, $access_info['openid'], 0);

            $user_info_cache_name = $openid . '_app_wx_user_info';
            $wx_user_info = Cache::get($user_info_cache_name);
            if (!$wx_user_info || $debug) {
                $get_user_info_url = "https://api.weixin.qq.com/sns/userinfo?access_token=$access_token&openid=$openid&lang=zh_CN";
                $curl = curl_init(); // 启动一个CURL会话
                curl_setopt($curl, CURLOPT_URL, $get_user_info_url);
                curl_setopt($curl, CURLOPT_HEADER, 0);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);  // 从证书中检查SSL加密算法是否存在
                $tmpInfo = curl_exec($curl);     //返回api的json对象		    //关闭URL请求
                curl_close($curl);
                $wx_user_info = json_decode($tmpInfo, true);
                if (!is_array($wx_user_info) || !array_key_exists('openid', $wx_user_info)) {
                    Log::write($wx_user_info, 'error');
                    throw new CommonException(['msg' => 'app获取微信用户信息失败', 'code' => '500']);
                }
                if (array_key_exists('headimgurl', $wx_user_info) && $wx_user_info['headimgurl']) {
                    //替换头像地址为大图
                    $reg = '/\/\d+$/';
                    $wx_user_info['headimgurl'] = preg_replace($reg, '/0', $wx_user_info['headimgurl']);
                    //下载图片到本地
                    $wx_user_info['head_img_file'] = (new DownloadImage())->download($wx_user_info['headimgurl']);
                } else {
                    $wx_user_info['head_img_file'] = 'head_portrait.png';
                }
                //通过openid永久缓存用户信息
                Cache::set($user_info_cache_name, $wx_user_info, 0);
            }
        } else {
            $user_info_cache_name = $openid . '_app_wx_user_info';
            $wx_user_info = Cache::get($user_info_cache_name);
        }

        return $wx_user_info;
    }

    /**
     * @param $code
     * @return array|mixed
     * @throws CommonException
     * APP获取支付宝用户信息
     */
    public static function getAliPayUserInfoForApp($code)
    {
        $debug = config('my_config.debug');
        $AliPay = new AliPay();
        $oauth_token_result = $AliPay->oauthToken($code);
        if (!is_array($oauth_token_result) || array_key_exists('error_response', $oauth_token_result)) {
            Log::write($oauth_token_result, 'error');
            throw new CommonException(['msg' => 'app获取支付宝access_token信息失败', 'code' => '500']);
        }
        /**
         * $oauth_token_result
         * {
         * "alipay_system_oauth_token_response": {
         * "access_token": "kuaijieB33478b903ee2408ab03e1e5b99b00X40",
         * "alipay_user_id": "20880050551716885137578560816740",
         * "expires_in": 1209600,
         * "re_expires_in": 15552000,
         * "refresh_token": "kuaijieB3ba44cdbdb9c4d4dbe84a2b54c3e9X40",
         * "user_id": "2088702746395409"
         * },
         * "sign": ""
         * }*/
        $ali_pay_user_id = $oauth_token_result['alipay_system_oauth_token_response']['user_id'];
        $user_info_cache_name = $ali_pay_user_id . '_app_ali_pay_user_info';
        $user_info = Cache::get($user_info_cache_name);
        if (!$user_info || $debug) {
            $user_info_share = $AliPay->userInfoShare($oauth_token_result['alipay_system_oauth_token_response']['access_token']);
            if (!is_array($user_info_share) || $user_info_share['alipay_user_info_share_response']['code'] !== '10000') {
                Log::write($user_info_share, 'error');
                throw new CommonException(['msg' => 'app获取支付宝用户信息失败', 'code' => '500']);
            }
            //下载图片到本地
            $user_info['head_img_file'] = (new DownloadImage())->download($user_info_share['alipay_user_info_share_response']['avatar']);
            //生成用户姓名
            $user_info['nick_name'] = array_key_exists('nick_name', $user_info_share['alipay_user_info_share_response']) ? $user_info_share['alipay_user_info_share_response']['nick_name'] : 'aliPay' . time();
            //user_id
            $user_info['ali_pay_user_id'] = $user_info_share['alipay_user_info_share_response']['user_id'];
            //通过openid永久缓存用户信息
            Cache::set($user_info_cache_name, $user_info, 0);
        }
        debugLog('userInfo', $user_info);

        return $user_info;
    }
}