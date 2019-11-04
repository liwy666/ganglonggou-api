<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/7/1
 * Time: 10:56
 */

namespace app\api\controller\v1\common;


use app\api\model\GlUser;
use app\api\service\Login\BaseLogin;
use app\api\service\Login\MobileLogin;
use app\api\service\SerEmail;
use app\api\validate\CurrencyValidate;
use app\lib\exception\CommonException;
use think\facade\Cache;

class Register
{
    /**
     * @return bool
     * @throws CommonException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 手机端用户注册验证
     */
    public function checkMobileRegister()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['phone', 'password', 'again_password'], 'require');
        $phone = request()->param('phone');
        $password = request()->param('password');
        $again_password = request()->param('again_password');
        //验证手机号
        if (!preg_match('/^1([0-9]{9})/', $phone)) {
            throw new CommonException(['msg' => '你输入的手机号不正确']);
        };
        if (GlUser::where([['phone', '=', $phone]])->find()) {
            throw new CommonException(['msg' => '该手机号已被注册']);
        };
        if (mb_strlen($password) < 5 || mb_strlen($password) > 15) {
            throw new CommonException(['msg' => '你输入的密码长度不符合规范']);
        }
        if ($password !== $again_password) {
            throw new CommonException(['msg' => '两次密码输入不一致']);
        }

        return true;
    }

    /**
     * @return bool
     * @throws CommonException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 手机端用户注册
     */
    public function mobileRegister()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['phone', 'password', 'again_password'], 'require');
        $phone = request()->param('phone');
        $password = request()->param('password');
        $again_password = request()->param('again_password');
        //验证手机号
        if (!preg_match('/^1([0-9]{9})/', $phone)) {
            throw new CommonException(['msg' => '你输入的手机号不正确']);
        };
        if (GlUser::where([['phone', '=', $phone]])->find()) {
            throw new CommonException(['msg' => '该手机号已被注册']);
        };
        if (mb_strlen($password) < 5 || mb_strlen($password) > 15) {
            throw new CommonException(['msg' => '你输入的密码长度不符合规范']);
        }
        if ($password !== $again_password) {
            throw new CommonException(['msg' => '两次密码输入不一致']);
        }

        return (new MobileLogin())->mobileRegister($phone, $password);
    }


    /**
     * @return bool
     * @throws CommonException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     *发送邮箱验证码
     */
    public function sendRegisterEmailVerifyCode()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['email_address'], 'require');
        $email_address = request()->param('email_address');
        //验证邮箱合法性
        if (!preg_match('/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/', $email_address)) {
            throw new CommonException(['msg' => '输入邮箱格式不正确']);
        };
        //定义常量
        $send_cycle = 55;
        $verify_code_term_of_validity = 600;
        $cache_name = 'register_email_verify_code' . $email_address;
        //验证请求周期
        $cache_data = Cache::get($cache_name);
        if ($cache_data && $cache_data['send_cycle_invalid'] > time()) {
            throw new CommonException(['msg' => '系统繁忙请稍后再试']);
        }
        //验证邮箱是否存在
        if (GlUser::where([['email', '=', $email_address]])->find()) {
            throw new CommonException(['msg' => '该邮箱号已被注册']);
        };
        //生成验证码和写入缓存(6位纯数字，10分钟有效期，55秒发送周期)
        $verify_code = rand(123456, 987654);//生成验证码
        cache($cache_name, ["verify_code" => $verify_code, "send_cycle_invalid" => time() + $send_cycle], $verify_code_term_of_validity);
        //发送邮件
        (new SerEmail())->sendEmail('注册验证', "您好，$email_address!\n欢迎来到岗隆，请将验证码填写到注册页面。\n验证码：$verify_code", [$email_address]);

        return true;
    }


    /**\
     * @return string
     * @throws CommonException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 通过邮箱号注册账户
     */
    public function registerAccountsByEmailAddress()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['email_address', 'password', 'verify_code', 'into_type','son_into_type'], 'require');
        $email_address = request()->param('email_address');
        $password = request()->param('password');
        $verify_code = request()->param('verify_code');
        $into_type = request()->param('into_type');
        $son_into_type = request()->param('son_into_type');

        //验证邮箱合法性
        if (!preg_match('/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/', $email_address)) {
            throw new CommonException(['msg' => '输入邮箱格式不正确']);
        };
        //验证密码强度
        if (!preg_match('/^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z]{8,16}$/', $password)) {
            throw new CommonException(['msg' => '你输入的密码强度不符合规范']);
        };
        //验证入口
        if (!array_key_exists($into_type, config('my_config.parentId_by_intoType'))) {
            throw new CommonException(['msg' => '无效入口']);
        }
        //验证子入口
        if (!array_key_exists($son_into_type, config('my_config.son_into_type_name'))) {
            throw new CommonException(['msg' => '无效入口']);
        }
        //验证验证码
        $cache_name = 'register_email_verify_code' . $email_address;
        $cache_data = Cache::get($cache_name);
        if (!$cache_data || $cache_data['verify_code'] + 0 !== $verify_code + 0) {
            throw new CommonException(['msg' => '验证码错误']);
        }
        //验证邮箱是否存在
        if (GlUser::where([['email', '=', $email_address]])->find()) {
            throw new CommonException(['msg' => '该邮箱号已被注册']);
        }

        $data = [
            'user_name' => 'mobile' . time(),
            'user_password' => md5($password),
            'login_ip' => request()->ip(),
            'user_img' => "head_portrait.png",
            'add_time' => time(),
            'login_time' => time(),
            'integral' => 0,
            'is_del' => 0,
            'login_count' => 1,
            'email' => $email_address
        ];
        $user_id = GlUser::create($data)->id;
        $result['user_id'] = $user_id;
        $result['into_type'] = $son_into_type;
        $result['son_into_type'] = $son_into_type;
        $token =  BaseLogin::saveToCache7Day($result);

        return $token;

    }
}