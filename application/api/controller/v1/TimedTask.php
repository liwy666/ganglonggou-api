<?php


namespace app\api\controller\v1;


use app\api\model\GlMidOrder;
use app\api\model\GlOrder;
use app\api\service\OrderPayment\Payment;
use app\api\service\SerEvaluate;
use app\api\validate\CurrencyValidate;
use app\lib\exception\CommonException;
use think\Exception;
use think\facade\Cache;
use think\facade\Log;

class TimedTask
{
    private $checkOrderPaymentStateKeyName = 'checkOrderPaymentStateKey';
    private $key = 'gl888888';

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

        if (request()->param('key') !== $this->key) {
            throw new CommonException();
        }
        //支付未超时的订单
        //['invalid_pay_time', '>=', time()]
        $orderList = GlOrder::where([
            ['order_state', '=', '1'],
            ['is_del', '=', 0],
            ['invalid_pay_time', '>=', time()]
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
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 常规定时任务
     */
    public function commonTimedTask()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['key'], 'require');

        if (request()->param('key') !== $this->key) {
            throw new CommonException();
        }
        //取消超时订单
        $cancelNumber = GlOrder::where([
            ['order_state', '=', '1'],
            ['is_del', '=', 0],
            ['invalid_pay_time', '<', time()]
        ])->update([
            'upd_time' => time(),
            'order_state' => 0,
            'prev_order_state' => 1,
            'order_visible_note' => '超出支付时间，订单自动取消'
        ]);
        //签收超时订单
        $signNumber = GlOrder::where([
            ['order_state', '=', 3],
            ['is_del', '=', 0],
            ['invalid_sign_goods_time', '<', time()]
        ])->update([
            'order_state' => 4,
            'upd_time' => time(),
            'sign_goods_time' => time(),
            'prev_order_state' => 3,
            'order_visible_note' => '超出签收时间，订单自动签收'
        ]);

        //自动评价订单
        if ($signNumber > 0) {
            $this->_autoEvaluate();
        }

        if ($cancelNumber > 0 || $signNumber > 0) {
            Log::write("自动取消订单数量：$cancelNumber,自动签收订单数量：$signNumber", 'log');
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

        //Log::write($result, 'log');

        return true;

    }

    /**
     * @param $orderSn
     * @param $keyName
     * @param $valueCode
     * 发送检查订单支付情况的post请求
     */
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

    /**
     * @throws \think\exception\DbException
     * 自动评价
     */
    private function _autoEvaluate()
    {
        $EvaluateClass = new SerEvaluate();

        //是自动签收，并且签收时间是最近240S秒以内的
        GlOrder::where([
            ['order_visible_note', '=', '超出签收时间，订单自动签收'],
            ['sign_goods_time', '>', time() - 240],
            ['sign_goods_time', '<=', time() + 240],
        ])
            ->field('order_sn,user_id,deliver_goods_time')
            ->chunk(100, function ($orderList) use ($EvaluateClass) {
                foreach ($orderList as $key => $value) {
                    $value = $value->toArray();
                    $orderSn = $value['order_sn'];
                    $userId = $value['user_id'];
                    $time = time();
                    //找出中间表
                    $midOrder = GlMidOrder::where(['order_sn' => $orderSn, 'is_evaluate' => 0])
                        ->field('id,goods_id')
                        ->find();
                    if ($midOrder) {
                        try {
                            //开始评价商品
                            $EvaluateClass->userInsEvaluate('该用户没有填写评价。', $userId, $midOrder['id'], 5, 1, $midOrder['goods_id'], $time);
                        } catch (Exception $exception) {
                            continue;
                        }
                    }
                }
            }, 'order_sn');
    }
}