<?php


namespace app\api\controller\v1;


use app\api\model\GlOrder;
use app\api\service\OrderPayment\Payment;
use app\api\validate\CurrencyValidate;
use app\lib\exception\CommonException;
use think\facade\Cache;
use think\facade\Log;

class TimedTask
{
    private $checkOrderPaymentStateKeyName = 'checkOrderPaymentStateKey';

    /**
     * @return bool
     * @throws CommonException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     *循环检查订单支付状态
     */
    public function timingCheckOrderPaymentState()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['key'], 'require');

        if (request()->param('key') !== 'gl888888') {
            throw new CommonException();
        }
        //支付未超时的订单
        //['invalid_pay_time', '>=', time()]
        $orderList = GlOrder::where([
            ['order_state', '=', '1'],
            ['is_del', '=', 0],
        ])->select()->toArray();

        foreach ($orderList as $key => $value) {
            //生成键名字和值
            $keyName = $this->checkOrderPaymentStateKeyName . getRandChar(10);
            $valueCode = getRandChar(10);
            //保存缓存
            Cache::set($keyName, $valueCode, 300);
            //发送请求
            $this->_sendCheckOrderPaymentState($value['order_sn'], $keyName, $valueCode);
        }
        return true;
    }

    /**
     * @return bool
     * @throws CommonException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 检查单个订单支付状态
     */
    public function checkOrderPaymentState()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['orderSn', 'keyName', 'valueCode'], 'require');
        $orderSn = request()->param('orderSn');
        $keyName = request()->param('keyName');
        $valueCode = request()->param('valueCode');

        //检查缓存
        if ($valueCode !== Cache::pull($keyName)) {
            throw new CommonException(['msg' => '发生验证错误']);
        }

        //开始检查支付状态
        $PayClass = new Payment();
        $PayClass->orderSn = $orderSn;
        $result = $PayClass->orderPayQuery();

        Log::write($result, 'log');

        return true;

    }

    private function _sendCheckOrderPaymentState($orderSn, $keyName, $valueCode)
    {
        $post_data['orderSn'] = $orderSn;
        $post_data['keyName'] = $keyName;
        $post_data['valueCode'] = $valueCode;
        $url = config('my_config.local_url') . 'api/v1/check_order_payment_state';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 200);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, 200);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);//这个是重点。
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $t = curl_exec($ch);
        curl_close($ch);
    }
}