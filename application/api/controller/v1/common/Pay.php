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
        $into_type = $user_desc['into_type'];
        $son_into_type = $user_desc['son_into_type'];

        $result = GlPayType::where([['into_type', '=', $into_type]
            , ['son_into_type', '=', $son_into_type]
            , ['is_del', '=', 0]])
            ->field('pay_code,pay_name,pay_id')
            ->select();

        if (count($result) === 0) {
            throw new CommonException(['msg' => '无有效支付方式','error_code'=>'20001']);
        }

        foreach ($result as $k => $v) {
            $result[$k]['ByStages'] = GlByStages::where([['pay_code', '=', $v['pay_code']]
                , ['is_del', '=', 0]])
                ->field('is_del,bystages_planCode', true)
                ->select();
        }

        return $result;
    }

    /**
     * @throws CommonException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 订单支付
     */
    public function OrderPayment()
    {

        //验证必要
        (new CurrencyValidate())->myGoCheck(['order_sn', 'user_token', 'success_url', 'back_url'], 'require');

        $PaymentClass = new Payment();
        $PaymentClass->userToken = request()->param('user_token');
        $PaymentClass->orderSn = request()->param('order_sn');
        $PaymentClass->successUrl = request()->param('success_url');
        $PaymentClass->backUrl = request()->param('back_url');

        return $PaymentClass->orderPayment();

    }

}