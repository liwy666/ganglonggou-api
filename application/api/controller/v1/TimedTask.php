<?php


namespace app\api\controller\v1;


use app\api\model\GlOrder;
use app\api\service\OrderPayment\Payment;
use app\api\validate\CurrencyValidate;
use app\lib\exception\CommonException;

class TimedTask
{
    public function timingCheckOrderPaymentState()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['key'], 'require');

        if (request()->param('key') !== 'gl888888') {
            throw new CommonException();
        }
        //支付未超时的订单
        $orderList = GlOrder::where([
            ['order_state', '=', '1'],
            ['is_del', '=', 0],
            ['invalid_pay_time', '>=', time()]
        ])->select()->toArray();

        $PayClass = new Payment();
        foreach ($orderList as $key => $value) {
            $PayClass->orderSn = $value['order_sn'];
            $PayClass->orderPayQuery();
        }
        return true;
    }
}