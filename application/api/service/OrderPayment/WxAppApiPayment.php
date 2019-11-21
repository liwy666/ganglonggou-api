<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/6/20
 * Time: 16:57
 */

namespace app\api\service\OrderPayment;


use app\api\model\GlUser;
use app\lib\exception\CommonException;
use think\facade\Log;

class WxAppApiPayment
{
    private $file;
    private $midOrderInfo;
    private $orderInfo;
    private $unifiedOrder;
    private $config;
    private $input;
    private $refund;

    public function __construct()
    {
        $this->file = dirname(\think\facade\Env::get('root_path')) . '/extend/WxJSAPIPay/';

        require($this->file . 'WxPay.Api.php');
        require($this->file . 'WxAppPayConfig.php');

        $this->unifiedOrder = new \WxPayUnifiedOrder();
        $this->config = new \WxPayConfig();
        $this->input = new \WxPayOrderQuery();
        $this->refund = new \WxPayRefund();
    }

    /**
     * @param $order_info
     * @param $mid_order_info
     * @param $success_url
     * @param $back_url
     * @return mixed
     * @throws CommonException
     * 发起支付
     */
    public function startPayment($order_info, $mid_order_info, $success_url, $back_url)
    {
        $this->orderInfo = $order_info;
        $this->midOrderInfo = $mid_order_info;

        $this->unifiedOrder->SetBody("江苏岗隆数码-商品购买");
        $this->unifiedOrder->SetAttach("江苏岗隆数码科技有限公司");
        $this->unifiedOrder->SetOut_trade_no($this->orderInfo['order_sn']);
        $this->unifiedOrder->SetTotal_fee($this->orderInfo['order_price'] * 100);
        $this->unifiedOrder->SetTime_start(date("YmdHis"));
        $this->unifiedOrder->SetTime_expire(date("YmdHis", time() + 600));
        $this->unifiedOrder->SetTrade_type("APP");

        $data = \WxPayApi::unifiedOrder($this->config, $this->unifiedOrder);

        if ($data['return_code'] != "SUCCESS" || $data['result_code'] != "SUCCESS") {
            Log::write($order_info, 'error');
            Log::write($data, 'error');
            Log::write("WxAppApiPayment获取预支付订单信息失败", 'error');
            throw new CommonException(['msg' => '获取预支付订单信息失败']);
        }
        /**
         * "data": {
         * "appid": "wxaea0312f1b660706",
         * "mch_id": "1490220962",
         * "nonce_str": "0JtcHT7scTf2hEwT",
         * "prepay_id": "wx211542090960024a06e3c58d1328202300",
         * "result_code": "SUCCESS",
         * "return_code": "SUCCESS",
         * "return_msg": "OK",
         * "sign": "711FA1861E141B4F54001C7D2BFFEECF",
         * "trade_type": "APP"
         * },
         * "result": {
         * "mch_id": "1490220962",
         * "nonce_str": "920ku6ztcraprbxi0xxfpa97snlasco0",
         * "prepay_id": "wx211542090960024a06e3c58d1328202300",
         * "result_code": "SUCCESS",
         * "return_code": "SUCCESS",
         * "sign": "53BCE9F0D2301002A287516CCFFD2723",
         * "timestamp": "1574322132"
         * }
         */
        return $this->_getAppApiParameters($data);
    }

    /**
     * 支付回调
     */
    public function notifyProcess()
    {

        $Notify = new WxNotify();
        $config = $this->config;
        $Notify->Handle($config);

    }


    /**
     * @param $order_info
     * @return mixed
     * @throws CommonException
     * @throws \WxPayException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 支付查询
     */
    public function startQuery($order_info)
    {

        $out_trade_no = $order_info['order_sn'];
        $this->input->SetOut_trade_no($out_trade_no);

        $wx_result = \WxPayApi::orderQuery($this->config, $this->input);

        $wx_result["trade_state"] = array_key_exists("trade_state", $wx_result) ?
            $wx_result["trade_state"] : null;

        $wx_result["err_code"] = array_key_exists("err_code", $wx_result) ?
            $wx_result["err_code"] : null;

        $wx_result["return_msg"] = array_key_exists("return_msg", $wx_result) ?
            $wx_result["return_msg"] : null;

        if ($wx_result["return_code"] === "SUCCESS") {
            if ($wx_result["return_msg"] === "OK" && $wx_result["trade_state"] === "SUCCESS") {
                $result["msg"] = $wx_result["return_msg"];
                $result["success"] = true;
                $result["status"] = $wx_result["err_code"];
                /*修改订单状态*/
                if ($order_info['order_state'] === 1) {
                    $PaymentClass = new Payment();
                    $PaymentClass->orderSn = $order_info['order_sn'];
                    $third_party_sn_array['wx_js_api_order_sn'] = $wx_result['transaction_id'];
                    $PaymentClass->OrderPaySuccess($third_party_sn_array);
                }
                return $result;
            } else {
                $result["msg"] = $wx_result["return_msg"];
                $result["success"] = false;
                $result["status"] = $wx_result["err_code"];
                return $result;
            }
        } else {
            $result["msg"] = "支付失败或未发起过支付";
            $result["success"] = false;
            $result["status"] = "false";
            return $result;
        }
    }

    /**
     * @param $order_info
     * @return bool
     * @throws \WxPayException
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 支付退款
     */
    public function startRefund($order_info)
    {

        $out_trade_no = $order_info['order_sn'];//商户订单号
        $total_fee = $order_info['order_price'] * 100;//订单总金额(分)
        $refund_fee = $order_info['order_price'] * 100;//退款金额(分)

        $this->refund->SetOut_trade_no($out_trade_no);
        $this->refund->SetTotal_fee($total_fee);
        $this->refund->SetRefund_fee($refund_fee);

        //判断是否有退款单号
        if (!($order_info["refund_order_sn"])) {//没有退款单号

            $this->refund->SetOut_refund_no(Payment::createRefundSn($order_info['order_sn']));//退款订单号

        } else {//有退款单号

            $this->refund->SetOut_refund_no($order_info["refund_order_sn"]);//退款订单号

        }

        $this->refund->SetOp_user_id($this->config->GetMerchantId());

        $data = \WxPayApi::refund($this->config, $this->refund);

        if ($data['result_code'] === "SUCCESS" && $data['return_code'] === "SUCCESS" && $data['return_msg'] === "OK") {
            //改变订单状态
            $PaymentClass = new Payment();
            $PaymentClass->orderSn = $order_info['order_sn'];
            $PaymentClass->OrderRefundSuccess();

            return true;

        } else {
            //3、失败
            Log::write($data, 'error');
            Log::write('微信JsApi退款失败(订单号：' . $order_info['order_sn'] . ')', 'error');
            return false;
        }
    }

    /**
     * @param $data
     * @return array
     * 获取app支付的参数
     */
    private function _getAppApiParameters($data)
    {
        $time_stamp = time() . '';
        $nonce_str = \WxPayApi::getNonceStr();
        $sign_array = [
            'appid' => $data['appid'],
            'partnerid' => $data['mch_id'],
            'prepayid' => $data['prepay_id'],
            'package' => 'Sign=WXPay',
            'noncestr' => $nonce_str,
            'timestamp' => $time_stamp,
        ];
        //签名步骤一：按字典序排序参数
        ksort($sign_array);
        $sign_str = $this->_toUrlParams($sign_array);
        //签名步骤二：在string后加入KEY
        $sign_str = $sign_str . "&key=" . $this->config->GetKey();
        //签名步骤三：MD5加密
        $sign_str = md5($sign_str);
        //签名步骤四：所有字符转为大写
        $sign = strtoupper($sign_str);

        return [
            'mch_id' => $data['mch_id'],
            'nonce_str' => $nonce_str,
            'prepay_id' => $data['prepay_id'],
            'result_code' => $data['result_code'],
            'return_code' => $data['return_code'],
            'sign' => $sign,
            'timestamp' => $time_stamp,
        ];

    }

    /**
     * 格式化参数格式化成url参数
     */
    private function _toUrlParams($array)
    {
        $buff = "";
        foreach ($array as $k => $v) {
            if ($k != "sign" && $v != "" && !is_array($v)) {
                $buff .= $k . "=" . $v . "&";
            }
        }

        $buff = trim($buff, "&");
        return $buff;
    }


}
