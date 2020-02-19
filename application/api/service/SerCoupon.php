<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/5/23
 * Time: 11:31
 */

namespace app\api\service;


use app\api\model\GlCoupon;
use app\api\model\GlGoods;
use app\api\model\GlMidUserCoupon;

class SerCoupon
{
    /**
     * @param $goods_id
     * @param $into_type
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 根据商品id返回可用优惠券
     */
    public function giveUsableCouponByGoodsIdAndIntoType($goods_id, $into_type)
    {
        /*获取该入口所有可用优惠券*/
        $coupon_array = GlCoupon::where([['into_type', '=', $into_type]
            , ['is_del', '=', 0]
            , ['start_grant_time', '<', time()]
            , ['end_grant_time', '>', time()]])
            ->select()
            ->toArray();

        $result = [];

        $goods_info = GlGoods::where(["goods_id" => $goods_id])
            ->find()
            ->toArray();

        foreach ($coupon_array as $k => $v) {
            if ($v["grant_type"] === "all") {//全场券
                array_push($result, $v);
            } elseif ($v["grant_type"] === "classify") {//商品分类券
                foreach ($v['classify'] as $k2 => $v2) {
                    if ($goods_info['cat_id']+0 === $v2+0) {
                        array_push($result, $v);
                    }
                }
            } elseif ($v["grant_type"] === "solo") {//单个商品券
                foreach ($v['solo'] as $k2 => $v2) {
                    if ($v2+0 === $goods_id+0) {
                        array_push($result, $v);
                    }
                }
            }
        }

        if (count($result) > 0) {
            foreach ($result as $k => $v) {
                $result[$k] = byKeyRemoveArrVal($result[$k], 'solo');
                $result[$k] = byKeyRemoveArrVal($result[$k], 'classify');
            }
            return $result;
        } else {
            return [];
        }
    }

    /**
     * @param $coupon_id
     * @param $user_id
     * @param $into_type
     * @return string
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 用户领取优惠券
     */
    public function userGetCouponByUserIdAndCouponIdAndLoginType($coupon_id, $user_id, $into_type)
    {

        $result = '非常抱歉，领取优惠券时发生未知错误，领券失败';

        $coupon_info = GlCoupon::where([['coupon_id', '=', $coupon_id]
            , ['into_type', '=', $into_type]
            , ['is_del', '=', 0]
            , ['start_grant_time', '<', time()]
            , ['end_grant_time', '>', time()]])
            ->find();

        if (!$coupon_info) {
            $result = '没有获取到有效优惠券信息，领取失败';
            return $result;
        } elseif ($coupon_info->coupon_remainder_number < 1) {
            $result = '优惠券已经领光啦，下次再来吧';
            return $result;
        }
        /*判断用户是否已经领取过优惠券*/
        $mid_user_coupon = GlMidUserCoupon::where(['user_id' => $user_id,
            'coupon_id' => $coupon_id])
            ->find();
        if ($mid_user_coupon) {
            $result = "您已经领取过这张券啦，抓紧时间去使用吧~~";
            return $result;
        } else {
            $result = "领券成功";

            /*保存中间表,减少优惠券数量*/
            //1.保存中间表
            GlMidUserCoupon::create(["coupon_id" => $coupon_info->coupon_id,
                "user_id" => $user_id,
                "get_time" => time(),
                "is_use" => 0]);

            //2.减少优惠券数量
            GlCoupon::where(["coupon_id" => $coupon_info->coupon_id])
                ->setDec('coupon_remainder_number');

            return $result;
        }

    }

    /**
     * @param $user_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 通过用户id返回优惠券列表
     */
    public function giveCouponListByUserId($user_id)
    {
        $coupon_id_array_ = GlMidUserCoupon::where([['user_id', '=', $user_id]
            , ['is_use', '=', 0]])
            ->select()
            ->toArray();
        $coupon_id_array = [];
        $coupon_list = [];
        if (count($coupon_id_array_) > 0) {
            foreach ($coupon_id_array_ as $k => $v) {
                array_push($coupon_id_array, $v['coupon_id']);
            }
            $coupon_list = GlCoupon::where([['coupon_id', 'in', $coupon_id_array]
                , ['is_del', '=', 0]
                , ['start_use_time', '<', time()]
                , ['end_use_time', '>', time()]
            ])
                ->field('is_del,into_type', true)
                ->select()
                ->toArray();
            if (count($coupon_list) === 0) {
                $coupon_list = [];
            }
        }
        return $coupon_list;
    }

    /**
     * @param $into_type
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 通过入口返回优惠券列表
     */
    public function giveCouponListByIntoType($into_type)
    {
        $coupon_list = GlCoupon::where([
            ['into_type', '=', $into_type]
            , ['is_del', '=', 0]
            , ['start_grant_time', '<', time()]
            , ['end_grant_time', '>', time()]
        ])
            ->select();

        return $coupon_list;
    }
}