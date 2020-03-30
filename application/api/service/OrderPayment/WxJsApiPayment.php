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

class WxJsApiPayment
{
    private $file;
    private $paymentUrl;//生成的支付html
    private $midOrderInfo;
    private $backUrl;
    private $successUrl;
    private $orderInfo;
    private $userInfo;
    private $unifiedOrder;
    private $config;
    private $input;
    private $refund;
    private $jsApiParameters;

    public function __construct()
    {
        $this->file = dirname(\think\facade\Env::get('root_path')) . '/extend/WxJSAPIPay/';

        require($this->file . 'WxPay.Api.php');
        require($this->file . 'WxPayConfig.php');

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
     * @return \think\response\View
     * @throws CommonException
     * @throws \WxPayException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 发起支付
     */
    public function startPayment($order_info, $mid_order_info, $success_url, $back_url)
    {
        $this->orderInfo = $order_info;
        $this->midOrderInfo = $mid_order_info;
        $this->successUrl = $success_url;
        $this->backUrl = $back_url;

        /*获取用户微信openId*/
        $this->userInfo = GlUser::where([
            ['user_id', '=', $this->orderInfo['user_id']]
        ])
            ->find();

        $this->unifiedOrder->SetBody("江苏岗隆数码-商品购买");
        $this->unifiedOrder->SetAttach("江苏岗隆数码科技有限公司");
        $this->unifiedOrder->SetOut_trade_no($this->orderInfo['order_sn']);
        $this->unifiedOrder->SetTotal_fee($this->orderInfo['order_price'] * 100);
        $this->unifiedOrder->SetTime_start(date("YmdHis"));
        $this->unifiedOrder->SetTime_expire(date("YmdHis", time() + 600));
        $this->unifiedOrder->SetTrade_type("JSAPI");
        $this->unifiedOrder->SetOpenid($this->userInfo['wx_openid']);

        $data = \WxPayApi::unifiedOrder($this->config, $this->unifiedOrder);

        if ($data['return_code'] != "SUCCESS" || $data['result_code'] != "SUCCESS") {
            Log::write($data, 'error');
            Log::write("获取预支付订单信息失败", 'error');
            throw new CommonException(['msg' => '获取预支付订单信息失败']);
        }

        $this->jsApiParameters = $this->getJsApiParameters($data);

        return $this->paymentHtml();
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
     * @param $UnifiedOrderResult
     * @return false|string
     * @throws CommonException
     * @throws \WxPayException
     * 获取jsapi支付的参数
     */
    private function getJsApiParameters($UnifiedOrderResult)
    {
        if (!array_key_exists("appid", $UnifiedOrderResult)
            || !array_key_exists("prepay_id", $UnifiedOrderResult)
            || $UnifiedOrderResult['prepay_id'] == "") {
            throw new CommonException(['msg' => '获取jsapi支付的参数失败']);
        }

        $JsApi = new \WxPayJsApiPay();
        $JsApi->SetAppid($UnifiedOrderResult["appid"]);
        $timeStamp = time();
        $JsApi->SetTimeStamp("$timeStamp");
        $JsApi->SetNonceStr(\WxPayApi::getNonceStr());
        $JsApi->SetPackage("prepay_id=" . $UnifiedOrderResult['prepay_id']);

        $config = $this->config;
        $JsApi->SetPaySign($JsApi->MakeSign($config));
        $parameters = json_encode($JsApi->GetValues());

        return $parameters;
    }

    /**
     * @return \think\response\View
     * 生成支付页面
     */
    private function paymentHtml()
    {

        $goods_list = [];

        foreach ($this->midOrderInfo as $k => $v) {

            array_push($goods_list, ['goods_name' => $v['goods_name']]);

        }

        $order_info = [
            'order_price' => strval($this->orderInfo['order_price']),
            'goods_list' => $goods_list,
            'bank_url' => $this->backUrl,
            'success_url' => $this->successUrl,
            'payment_url' => $this->paymentUrl,
            'js_api_parameters' => json_encode($this->jsApiParameters, true),
        ];

        return view('/wxPayment')->assign('order_info', $order_info);

    }
}
