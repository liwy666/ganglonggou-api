<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/5/27
 * Time: 12:46
 */

namespace app\api\controller\v1\common;


use app\api\model\GlUser;
use app\api\service\Login\BaseLogin;
use app\api\service\SerEmail;
use app\api\validate\CurrencyValidate;
use app\lib\exception\CommonException;
use think\facade\Cache;
use think\File;

class User
{
    /**
     * @return array|\PDOStatement|string|\think\Model|null
     * @throws CommonException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取用户信息
     */
    public function giveUserInfoByUserToken()
    {

        //验证必要
        (new CurrencyValidate())->myGoCheck(['user_token'], 'require');

        //获取用户信息
        $user_token = request()->param("user_token");
        $user_desc = BaseLogin::getCurrentIdentity(['user_id', 'into_type', 'son_into_type'], $user_token);
        $user_id = $user_desc['user_id'];

        $result = GlUser::where([['user_id', '=', $user_id]
            , ['is_del', '=', 0]])
            ->field('user_id,user_name,user_img,add_time,name,email,phone,integral')
            ->find();

        if (!$result) {
            throw  new CommonException(['msg' => '无效用户']);
        }

        return $result;
    }


    /**
     * @return mixed
     * @throws CommonException
     * 更换头像
     */
    public function userUpdPortrait()
    {
        return (new \app\api\service\Upload\Upload())->ImgUpload(4194304);
    }

    /**
     * @return bool
     * @throws CommonException
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 修改用户信息
     */
    public function updUserInfoByUserId()
    {

        //验证必要
        (new CurrencyValidate())->myGoCheck(['user_token', 'user_img', 'user_name'], 'require');

        //获取用户信息
        $user_token = request()->param("user_token");
        $user_desc = BaseLogin::getCurrentIdentity(['user_id', 'into_type', 'son_into_type'], $user_token);
        $user_id = $user_desc['user_id'];

        $data['user_name'] = request()->param('user_name');
        $data['user_img'] = removeImgUrl(request()->param('user_img'));


        /*
          关闭邮箱和手机号修改
         $data['phone'] = request()->param('phone');
            $data['email'] = request()->param('email');

            if ($data['phone'] && GlUser::where([['phone', '=', $data['phone']]])->find()) {
                throw new CommonException(['msg' => '该手机号已被注册']);
            }

            if ($data['email'] && GlUser::where([['email', '=', $data['email']]])->find()) {
                throw new CommonException(['msg' => '该邮箱已被注册']);
            }*/


        if (!preg_match('/^([^#$@()<>\\\{}[\] \/]){5,20}$/', $data['user_name'])) {
            throw new CommonException(['msg' => '的用户名不符合规范']);
        };

        $upd_number = GlUser::where([
            ['user_id', '=', $user_id],
            ['is_del', '=', 0]
        ])
            ->update($data);

        if ($upd_number < 1) {
            throw new CommonException(['msg' => '修改失败']);
        }

        return true;


    }

    /**
     * @return bool
     * @throws CommonException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 发送登录或重置密码的邮箱验证码
     */
    public function sendRetrievePasswordOrLoginEmailVerifyCode()
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
        $cache_name = 'retrieve_password_or_login_email_verify_code' . $email_address;
        //验证请求周期
        $cache_data = Cache::get($cache_name);
        if ($cache_data && $cache_data['send_cycle_invalid'] > time()) {
            throw new CommonException(['msg' => '系统繁忙请稍后再试']);
        }
        //验证邮箱是否存在
        if (!GlUser::where([['email', '=', $email_address]])->find()) {
            throw new CommonException(['msg' => '邮箱帐号不存在']);
        };
        //生成验证码和写入缓存(6位纯数字，10分钟有效期，55秒发送周期)
        $verify_code = rand(123456, 987654);//生成验证码
        cache($cache_name, ["verify_code" => $verify_code, "send_cycle_invalid" => time() + $send_cycle], $verify_code_term_of_validity);
        //发送邮件
        (new SerEmail())->sendEmail('岗隆验证码', "您的验证码是：$verify_code\n此验证码用于登录岗隆或重置密码\n如非本人操作，请忽略此条信息", [$email_address]);

        return true;
    }

    /**
     * @return string
     * @throws CommonException
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * 通过邮箱验证码重置密码
     */
    public function retrievePasswordByEmailVerifyCode()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['email_address', 'password', 'verify_code', 'into_type', 'son_into_type'], 'require');
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
        //验证验证码
        $cache_name = 'retrieve_password_or_login_email_verify_code' . $email_address;
        $cache_data = Cache::get($cache_name);
        if (!$cache_data || $cache_data['verify_code'] + 0 !== $verify_code + 0) {
            throw new CommonException(['msg' => '验证码错误']);
        }
        //验证邮箱是否存在
        $user_info = GlUser::where([['email', '=', $email_address]])->find();
        if (!$user_info) {
            throw new CommonException(['msg' => '用户不存在']);
        }
        //修改密码
        GlUser::where([
            ['user_id', '=', $user_info['user_id']]
        ])
            ->update([
                'user_password' => md5($password)
            ]);

        $result['user_id'] = $user_info['user_id'];
        $result['into_type'] = $into_type;
        $result['son_into_type'] = $son_into_type;
        return BaseLogin::saveToCache7Day($result);

    }
}