<?php


namespace app\api\service\OrderPayment;


class TestPayment
{
    public function startPayment($order_info, $mid_order_info, $success_url, $back_url)
    {
        if ($order_info['user_id'] !== 2) return false;


        $PaymentClass = new Payment();
        $PaymentClass->orderSn = $order_info['order_sn'];
        $PaymentClass->OrderPaySuccess([]);
        return true;
    }
}