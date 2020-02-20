<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/5/27
 * Time: 14:23
 */

namespace app\api\controller\v1\common;


use app\api\model\GlByStages;
use app\api\model\GlPayType;
use app\api\service\Login\BaseLogin;
use app\api\service\OrderPayment\Payment;
use app\api\validate\CurrencyValidate;
use app\lib\exception\CommonException;

class Pay
{
    /**
     * @return array|\PDOStatement|string|\think\Collection
     * @throws CommonException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 通过登录入口获取支付列表
     */
    public function givePayList()
    {

        //验证必要
        (new CurrencyValidate())->myGoCheck(['user_token'], 'require');

        //获取用户信息
        $user_token = request()->param("user_token");
        $user_desc = BaseLogin::getCurrentIdentity(['user_id', 'into_type', 'son_into_type'], $user_token);
        $user_id = $user_desc['user_id'];
        $into_type = $user_desc['into_type'];
        $son_into_type = $user_desc['son_into_type'];
        $pay_list = [];

        $temp_pay_list = GlPayType::where([['into_type', '=', $into_type]
            , ['son_into_type', '=', $son_into_type]
            , ['is_del', '=', 0]])
            ->field('pay_code,pay_name,pay_id')
            ->select();

        if (count($pay_list) === 0) {
            throw new CommonException(['msg' => '无有效支付方式', 'error_code' => '20001']);
        }

        //剔除测试支付
        if ($user_id === 2) {
            $pay_list = $temp_pay_list;
        } else {
            foreach ($temp_pay_list as $k => $v) {
                if ($v["pay_code"] !== 'TestPayment') {
                    array_push($pay_list, $v);
                }
            }
        }


        foreach ($pay_list as $k => $v) {
            $pay_list[$k]['ByStages'] = GlByStages::where([['pay_code', '=', $v['pay_code']]
                , ['is_del', '=', 0]])
                ->field('is_del,bystages_planCode', true)
                ->select();
        }

        return $pay_list;
    }

    /**
     * @throws CommonException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 常规订单支付
     */
    public function OrderPayment()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['order_sn', 'user_token'], 'require');

        $PaymentClass = new Payment();
        $PaymentClass->userToken = request()->param('user_token');
        $PaymentClass->orderSn = request()->param('order_sn');
        if (request()->param('success_url')) {
            $PaymentClass->successUrl = request()->param('success_url');
        }
        if (request()->param('back_url')) {
            $PaymentClass->backUrl = request()->param('back_url');
        }
        return $PaymentClass->orderPayment();
    }


}