<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/5/23
 * Time: 14:23
 */

namespace app\api\controller\v1\common;


use app\api\model\GlCoupon;
use app\api\model\GlMidUserCoupon;
use app\api\service\Login\BaseLogin;
use app\api\service\SerCoupon;
use app\api\validate\CurrencyValidate;

class Coupon
{
    /**
     * @return string
     * @throws \app\lib\exception\CommonException
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 用户领取优惠券
     */
    public function userGetCoupon()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['coupon_id', 'user_token'], 'require');
        //验证正整数
        (new CurrencyValidate())->myGoCheck(['coupon_id'], 'positiveInt');

        $coupon_id = request()->param("coupon_id");
        //获取用户信息
        $user_token = request()->param("user_token");
        $user_desc = BaseLogin::getCurrentIdentity(['user_id', 'into_type', 'son_into_type'], $user_token);
        $user_id = $user_desc['user_id'];
        $into_type = $user_desc['into_type'];

        return (new SerCoupon())->userGetCouponByUserIdAndCouponIdAndLoginType($coupon_id, $user_id, $into_type);
    }

    /**
     * @return array
     * @throws \app\lib\exception\CommonException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 返回优惠券列表
     */
    public function giveCouponListByUserId()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['user_token'], 'require');

        //获取用户信息
        $user_token = request()->param("user_token");
        $user_desc = BaseLogin::getCurrentIdentity(['user_id', 'into_type', 'son_into_type'], $user_token);
        $user_id = $user_desc['user_id'];

        return (new SerCoupon())->giveCouponListByUserId($user_id);


    }

    /**
     * @return array
     * @throws \app\lib\exception\CommonException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 入口返回优惠券列表
     */
    public function giveCouponListByIntoType(){
        //验证必要
        (new CurrencyValidate())->myGoCheck(['into_type'], 'require');

        $into_type = request()->param("into_type");

        return (new SerCoupon())->giveCouponListByIntoType($into_type);

    }
}