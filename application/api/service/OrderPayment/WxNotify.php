<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/6/21
 * Time: 9:34
 */

namespace app\api\service\OrderPayment;

use think\Exception;
use think\facade\Log;

//$file = dirname(\think\facade\Env::get('root_path'));
//require($file. '/extend/WxJSAPIPay/' . 'WxPay.Api.php');
//require($file . '/extend/WxJSAPIPay/' . 'WxPay.Notify.php');

class WxNotify extends \WxPayNotify
{
    public function NotifyProcess($objData, $config, &$msg)
    {

        $data = $objData->GetValues();

        //TODO 1、进行参数校验
        if (!array_key_exists("return_code", $data)
            || (array_key_exists("return_code", $data) && $data['return_code'] != "SUCCESS")) {
            //TODO失败,不是支付成功的通知
            //如果有需要可以做失败时候的一些清理处理，并且做一些监控
            $msg = "异常异常";
            return false;
        }
        if (!array_key_exists("transaction_id", $data)) {
            $msg = "输入参数不正确";
            return false;
        }
        //TODO 2、进行签名验证
        try {
            $checkResult = $objData->CheckSign($config);
            if ($checkResult == false) {
                //签名错误
                Log::ERROR("签名错误...");
                return false;
            }
        } catch (Exception $e) {
            Log::ERROR(json_encode($e));
        }
        //TODO 3、处理业务逻辑
        if ($data['result_code'] == 'SUCCESS') {
            $order_sn = $data['out_trade_no'];
            $wx_order_sn = $data['transaction_id'];

            //支付成功
            $PaymentClass = new Payment();
            $PaymentClass->orderSn = $order_sn;
            $third_party_sn_array['wx_js_api_order_sn'] = $wx_order_sn;
            try {
                $PaymentClass->OrderPaySuccess($third_party_sn_array);
            } catch (Exception $exception) {
                Log::write('微信异步进入,没有问题，服务器内部错误(订单编号：' . $order_sn . ')', 'error');
                Log::write($exception, 'error');
                $result = $this->callBackHtml();
                return $result;
            }
        } else {
            //支付失败
            Log::write($data, 'error');
            Log::write('微信异步进入，result_code不等于SUCCESS', 'error');
        }
        return true;
    }

    private function callBackHtml()
    {
        return view('/abcCallback');
    }
}