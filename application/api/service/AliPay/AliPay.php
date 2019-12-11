<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/11/22
 * Time: 17:27
 */

namespace app\api\service\AliPay;


class AliPay
{
    private $_aop;
    private $_file;
    public $aliPayConfig;
    private $_privateKey;
    private $_signType;
    private $_appId;
    private $_charset;
    private $_gatewayUrl;
    private $_aliPayPublicKey;
    private $_postCharset = "UTF-8";
    private $_apiVersion = "1.0";
    private $_format = "json";

    public function __construct()
    {
        $config = [];//可以不写，只是为了避免IDE报错
        $this->_file = dirname(\think\facade\Env::get('root_path')) . '/extend/PcAliPay/';
        require_once($this->_file . 'config.php');
        require_once($this->_file . 'aop/AopClient.php');
        require_once($this->_file . 'aop/SignData.php');
        $this->aliPayConfig = $config;
        $this->_privateKey = $config['merchant_private_key'];
        $this->_signType = $config['sign_type'];
        $this->_appId = $config['app_id'];
        $this->_charset = $config['charset'];
        $this->_gatewayUrl = $config['gatewayUrl'];
        $this->_aliPayPublicKey = $config['alipay_public_key'];

        $this->_aop = new \AopClient ();
        $this->_aop->gatewayUrl = $this->_gatewayUrl;
        $this->_aop->appId = $this->_appId;
        $this->_aop->rsaPrivateKey = $this->_privateKey;
        $this->_aop->alipayrsaPublicKey = $this->_aliPayPublicKey;
        $this->_aop->apiVersion = $this->_apiVersion;
        $this->_aop->signType = $this->_signType;
        $this->_aop->postCharset = $this->_postCharset;
        $this->_aop->format = $this->_format;
    }

    public function oauthToken($code)
    {
        require_once($this->_file . 'aop/request/AlipaySystemOauthTokenRequest.php');
        $request = new \AlipaySystemOauthTokenRequest ();
        $request->setGrantType("authorization_code");
        $request->setCode($code);
        $result = $this->_aop->execute($request);
        $result = json_encode($result);
        $result = json_decode($result, true);

        return $result;

    }

    public function userInfoShare($auth_token)
    {

        require_once($this->_file . 'aop/request/AlipayUserInfoShareRequest.php');
        $request = new \AlipayUserInfoShareRequest ();
        $result = $this->_aop->execute($request, $auth_token);

        $result = json_encode($result);
        $result = json_decode($result, true);

        return $result;
    }

}
/**
 * 返回结果
 *
 * //授权登录返回：
 * pay: 9000 - success=true
 * &result_code=200
 * &app_id=2017110609764829
 * &auth_code=2c65b24b77554c1482f0f98ab017YX40
 * &scope=kuaijie
 * &alipay_open_id=20880050551716885137578560816740
 * &user_id=2088702746395409
 * &target_id=1574405082
 * //oauthToken 错误
 * {
 * "error_response": {
 * "code": "40002",
 * "msg": "Invalid Arguments",
 * "sub_code": "isv.code-invalid",
 * "sub_msg": "授权码code无效"
 * },
 * "sign": "SSkae7ZpPAskRQt1YVCT0XNH+8yd7uBqVrWYKz7SmMN5keHY7cFxEEDUkYtBUxdO3s06iepYVwxtiHNichNTwVUVZDGZwcVQcI9nJhMTGRRjvRs2HQ8YfFQBvEoMXIeBPyxqw5wYtiKmkir0DhGcgzF6KuXDCukjDZBKlTN1LvuLnTS9NdNnw61Xxag1toZvzec/3Xi84Ab9qD2e90C7wD6fzjwYIoEeVyyfO27XoGCse8IIyd1vLcjEtPeWu8+vJq0O7XVohboug7UKAu6LgxdrOOVA+/XvyFioZHTzVEGq9nJGLT7KZ+sUaO9CArriuUOvIghgAh7V+M/BukKGew=="
 * }
 * //oauthToken 正确
 * {
 * "alipay_system_oauth_token_response": {
 * "access_token": "kuaijieBef6ba64353b6478aa85733b303d5aB40",
 * "alipay_user_id": "20880050551716885137578560816740",
 * "expires_in": 1209600,
 * "re_expires_in": 15552000,
 * "refresh_token": "kuaijieBd091e2198f1744f38d55100dc5d69F40",
 * "user_id": "2088702746395409"
 * },
 * "sign": "DaXEWaKR1ajlFL7+mWZsaO7iMeXbgr3YbZZy9f3No8Q3QfuzPt+yGkDmBvHIrjtZj3BquDVWEmJg7ZZJSdAyfu1osmdUMPg1LnZukTxZRSzva4DjQpmNAQTMsaZdcfsHG4v22JGfTlvsnaadtTmp34lkeEDnqH2oGp9flC0QGPrpsSc5+lMNhL4b4haFo6o1oO7f5rplNato23+P0zJ2O5FjzTJi9gYuGy78p1vwMDSSWQyxuNz9NaDZIpoX7xRuKw+UX+TlwWS5p8ZBI3DX0EH9LeTvgonul/mplvewEsap9F8Xh5GHj9bQNkpzoqtph3ORrUlhHjDImLhm/7StXQ=="
 * }
 * //userInfoShare 正确
 * {
 * "alipay_user_info_share_response": {
 * "code": "10000",
 * "msg": "Success",
 * "avatar": "https://tfs.alipayobjects.com/images/partner/T1YjdoXo0eXXXXXXXX",
 * "city": "无锡市",
 * "gender": "m",
 * "is_certified": "T",
 * "is_student_certified": "F",
 * "nick_name": "黎大海",
 * "province": "江苏省",
 * "user_id": "2088702746395409",
 * "user_status": "T",
 * "user_type": "2"
 * },
 * "sign": "F6y4uS0lxpJIF4/3L3uixoLXjHzebHe7yDgAjDpHaChJ/XOaJ0/u2siAAfmuqbPH1SnRjAFPSV7BB+ZjDyaJaXGSBSyrUnjqr/6bhFby2obMIaaMGMd1Ob7BaciUNvNDxDP/dN5mspP2CvSWUvHXUW9vdCX4clVJzpmAgP3hyOjPCjJXTItFE7maFhPmmmYIbcC4FSAQuornf56WNLaW7eHr68CgFU6GcE9+LuBiDcQAMCj3+oyoVFe+pPvi+FfhX/L2OvpePY3nqVSY7gWqhv9nSHw+SLuAvtbs7otu8nFAhCYr/awMyXDQoz2Q+Amq7Bamwc9gZvPhCIPZnIPpUA=="
 * }
 * //userInfoShare 错误
 * {
 * "alipay_user_info_share_response": {
 * "code": "20001",
 * "msg": "Insufficient Token Permissions",
 * "sub_code": "aop.invalid-auth-token",
 * "sub_msg": "无效的访问令牌"
 * },
 * "sign": "eFmCd7/wkroREty0HgHjlbWHBqDQz/zsBFTG7/d09C65tbIEf+dTK+JN1pA58rc8Fp9RRvWBocUv1TWfN8+o+52MhP9mg4ySsJLnwS5ijqNgpiKjSF5sBkCk2u//Znuv8uhN77p2NN+RWAhG9/Zp9I7YbARZeLj5znLzBZ9RrK4TZs4+uzlsUJuaVnbtfMGc7QvcbPUnHzDH3GfVHPve6oVszm0ajzUHdp6Z6UCXJqiEDqIAXowFsBGMZJ76fA6vgM5dcdjtmqcuzPNtQIKFOn3q2U+hIzhcExEMArkttd2AYgOo/wR0P74DeHHhxFFV8a12jHcBmUlD3d408ywFRQ=="
 * }
 */