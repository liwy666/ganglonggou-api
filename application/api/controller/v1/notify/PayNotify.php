<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/6/5
 * Time: 15:10
 */

namespace app\api\controller\v1\notify;


use app\api\service\OrderPayment\AbcPayment;
use app\api\service\OrderPayment\GsyhPayment;
use app\api\service\OrderPayment\PcAliPayment;
use app\api\service\OrderPayment\WxJsApiPayment;
use think\facade\Log;

class PayNotify
{
    /**
     * @return \think\response\View
     * 农行支付回调
     */
    public function abcPayNotify()
    {

        $result = (new AbcPayment())->notifyProcess();

        return $result;
    }

    /**
     *微信支付回调
     */
    public function wxJsApiNotify()
    {

        $result = (new WxJsApiPayment())->notifyProcess();

        return $result;
    }


    /**
     * @throws \Exception
     * 支付宝回调
     */
    public function aliPayNotify()
    {

        $result = (new PcAliPayment())->notifyProcess();

        return $result;
    }
    /**
     * @throws \Exception
     * 工行回调
     */
    public function GsyhPayNotify()
    {

        $result = (new GsyhPayment())->notifyProcess();

        return $result;
    }
}