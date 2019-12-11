<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/5/23
 * Time: 9:24
 */

namespace app\api\controller\v1\common;


use app\api\model\GlUser;
use app\api\service\Login\AbcAppLogin;
use app\api\service\Login\AbcWxLogin;
use app\api\service\Login\AndroidLogin;
use app\api\service\Login\AppAliPayLogin;
use app\api\service\Login\AppWeChatLogin;
use app\api\service\Login\BaseLogin;
use app\api\service\Login\MobileLogin;
use app\api\service\Login\PcLogin;
use app\api\service\Login\TestLogin;
use app\api\service\Login\WxLogin;
use app\api\validate\CurrencyValidate;
use think\facade\Cache;

class Login
{


    /**
     * @return bool
     * @throws \app\lib\exception\CommonException
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     *登录统计
     */
    public function loginCount()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['user_token'], 'require');

        //获取用户信息
        $user_token = request()->param("user_token");
        $user_desc = BaseLogin::getCurrentIdentity(['user_id', 'into_type', 'son_into_type'], $user_token);
        $user_id = $user_desc['user_id'];

        GlUser::where([
            ['user_id', '=', $user_id]
        ])
            ->update([
                'login_ip' => request()->ip(),
                'login_time' => time()
            ]);

        GlUser::where([
            ['user_id', '=', $user_id]
        ])
            ->setInc('login_count');

        return true;

    }

    /**
     * @return string
     * @throws \app\lib\exception\CommonException
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * 测试登录
     */
    public function testLogin()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['test_app_appid', 'id'], 'require');

        return (new TestLogin())->giveToken();
    }

    /**
     * @return string
     * @throws \app\lib\exception\CommonException
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * 农行app登录
     */
    public function abcAppLogin()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['abc_app_appid', 'id'], 'require');

        return (new AbcAppLogin())->giveToken();
    }

    /**
     * @return string
     * @throws \app\lib\exception\CommonException
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * 农行wx登录
     */
    public function abcWxLogin()
    {

        //验证必要
        (new CurrencyValidate())->myGoCheck(['abc_wx_openid'], 'require');

        return (new AbcWxLogin())->giveToken();

    }

    /**
     * @return string
     * @throws \app\lib\exception\CommonException
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * 微信公众号登录
     */
    public function wxLogin()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['code'], 'require');
        $code = request()->param('code');


        return (new WxLogin($code))->giveToken();


    }

    /**
     * @return bool
     * @throws \app\lib\exception\CommonException
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 更新pc端缓存
     */
    public function writePcTokenByWxToken()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['code', 'user_token'], 'require');
        $code = request()->param('code');

        $user_token = request()->param("user_token");
        $user_desc = BaseLogin::getCurrentIdentity(['user_id', 'into_type', 'son_into_type'], $user_token);
        $user_id = $user_desc['user_id'];

        return (new PcLogin())->writeTokenByWxToken($code, $user_id);

    }

    /**
     * @return bool
     * @throws \app\lib\exception\CommonException
     * pc端通过WxOpenid登录
     */
    public function pcByWxOpenidLogin()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['code'], 'require');
        $code = request()->param('code');

        return (new PcLogin())->giveTokenByWxOpenid($code);

    }

    /**
     * @return string
     * pc端获取请求code
     */
    public function pcGetLoginCode()
    {

        $randChar = getRandChar(32);
        $timestamp = $_SERVER['REQUEST_TIME_FLOAT'];//得到请求开始时的时间戳
        $tokenSalt = config('my_config.token_salt');
        $code = md5($randChar . $timestamp . $tokenSalt);
        $pc_login_code = 'PcLC_' . $code;
        $data['token'] = null;

        /*写入缓存*/
        Cache::set($pc_login_code, $data, 300);//设定时间为5分钟

        return $pc_login_code;

    }

    /**
     * @return string
     * @throws \app\lib\exception\CommonException
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * 手机用户登录
     */
    public function mobileLogin()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['phone', 'password'], 'require');
        $phone = request()->param('phone');
        $password = request()->param('password');

        return (new MobileLogin())->mobilLogin($phone, $password);
    }


    /**
     * @return string
     * @throws \app\lib\exception\CommonException
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * 安卓用户登录
     */
    public function androidLogin()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['email_address', 'password'], 'require');
        $email_address = request()->param('email_address');
        $password = request()->param('password');

        return (new AndroidLogin())->login($email_address, $password);
    }

    /**
     * @return string
     * @throws \app\lib\exception\CommonException
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * 安卓用户微信登录
     */
    public function androidWeChatLogin()
    {
        (new CurrencyValidate())->myGoCheck(['code'], 'require');
        $code = request()->param('code');

        return (new AppWeChatLogin($code, 'android'))->getToken();
    }

    /**
     * @return string
     * @throws \app\lib\exception\CommonException
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * 安卓用户支付宝登录
     */
    public function androidAliPayLogin()
    {
        (new CurrencyValidate())->myGoCheck(['code'], 'require');
        $code = request()->param('code');

        return (new AppAliPayLogin($code, 'android'))->getToken();
    }
}