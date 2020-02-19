<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/7/3
 * Time: 13:56
 */

namespace app\api\service\OrderPayment;


use think\Exception;
use think\facade\Log;
use think\facade\Request;
use think\response\Json;

class PcAliPayment
{
    private $file;
    private $paymentHtmlInfo;//生成的支付html信息
    private $midOrderInfo;
    private $backUrl;
    private $successUrl;
    private $notifyUrl;
    private $orderInfo;


    public function __construct()
    {
        $this->file = dirname(\think\facade\Env::get('root_path')) . '/extend/PcAliPay/';
        $this->notifyUrl = config('my_config.api_url') . 'api/v1/notify/ali_pay_notify';
    }

    /**
     * @param $order_info
     * @param $mid_order_info
     * @param $success_url
     * @param $back_url
     * @return \think\response\View
     * @throws \Exception
     * 发起支付
     */
    public function startPayment($order_info, $mid_order_info, $success_url, $back_url)
    {
        $config = [];//可以不写，只是为了避免IDE报错

        require_once($this->file . 'config.php');
        require_once($this->file . 'pagepay/service/AlipayTradeService.php');
        require_once($this->file . 'pagepay/buildermodel/AlipayTradePagePayContentBuilder.php');

        $this->orderInfo = $order_info;
        $this->midOrderInfo = $mid_order_info;
        $this->successUrl = $success_url;
        $this->backUrl = $back_url;

        /*生成订单名称*/
        $goods_name_array = [];
        foreach ($this->midOrderInfo as $k => $v) {
            array_push($goods_name_array, $v['goods_name']);
        }
        $goods_name_str = implode(',', $goods_name_array);
        $goods_name_str = substr($goods_name_str, 0, 250);


        //商户订单号，商户网站订单系统中唯一订单号，必填
        $out_trade_no = $this->orderInfo['order_sn'];
        //订单名称，必填
        $subject = trim($goods_name_str);
        //付款金额，必填
        $total_amount = $this->orderInfo['order_price'];
        //商品描述，可空
        $body = trim('江苏岗隆数码-商品购买');

        //构造参数
        $payRequestBuilder = new \AlipayTradePagePayContentBuilder();
        $payRequestBuilder->setBody($body);
        $payRequestBuilder->setSubject($subject);
        $payRequestBuilder->setTotalAmount($total_amount);
        $payRequestBuilder->setOutTradeNo($out_trade_no);

        /*判断是否分期支付*/
        switch ($this->orderInfo['bystages_id']) {
            case 500:
                $payRequestBuilder->shutPcedit();
                break;
            case 503:
                $payRequestBuilder->allowPcedit(3);
                break;
            case 506:
                $payRequestBuilder->allowPcedit(6);
                break;
            case 512:
                $payRequestBuilder->allowPcedit(12);
                break;
        }

        $aop = new \AlipayTradeService($config);

        /*生成支付html*/
        $this->paymentHtmlInfo = $aop->pagePay($payRequestBuilder, $this->successUrl, $this->notifyUrl);

        return $this->paymentHtml();


    }

    /**
     * @throws \Exception
     * 异步通知
     */
    public function notifyProcess()
    {

        $config = [];//可以不写，只是为了避免IDE报错

        require_once($this->file . 'config.php');
        require_once($this->file . 'pagepay/service/AlipayTradeService.php');
        $arr = (Request::post(false));
        $alipaySevice = new \AlipayTradeService($config);
        $alipaySevice->writeLog(var_export($arr, true));
        $result = $alipaySevice->check($arr);

        if ($result) {//验证成功
            //交易状态
            $trade_status = $arr['trade_status'];
            if ($trade_status == 'TRADE_SUCCESS') {//支付成功
                //商户订单号
                $out_trade_no = $arr['out_trade_no'];
                //支付宝交易号
                $trade_no = $arr['trade_no'];

                $PaymentClass = new Payment();
                $PaymentClass->orderSn = $out_trade_no;
                $third_party_sn_array['ali_pay_order_sn'] = $trade_no;
                try {
                    $PaymentClass->OrderPaySuccess($third_party_sn_array);
                } catch (Exception $exception) {
                    Log::write('支付宝异步进入,没有问题，服务器内部错误(订单编号：' . $trade_no . ')', 'error');
                    Log::write($exception, 'error');
                    //返回支付宝，不做修改和删除
                    echo "success";
                }

            } else {
                //支付失败,非‘TRADE_SUCCESS’
                Log::write($_POST, 'error');
                Log::write('支付宝异步进入，trade_status状态码不为TRADE_SUCCESS', 'error');
            }

            //返回支付宝，不做修改和删除
            echo "success";
        } else {
            //返回支付宝，不做修改和删除
            echo "fail";

        }

    }


    /**
     * @param $order_info
     * @return bool
     * @throws Exception
     * @throws \think\exception\PDOException
     * 订单退款
     */
    public function startRefund($order_info)
    {

        $config = [];//可以不写，只是为了避免IDE报错
        require_once($this->file . 'config.php');
        require_once($this->file . 'pagepay/service/AlipayTradeService.php');
        require_once($this->file . 'pagepay/buildermodel/AlipayTradeRefundContentBuilder.php');

        //商户订单号，商户网站订单系统中唯一订单号
        $out_trade_no = $order_info['order_sn'];

        //需要退款的金额，该金额不能大于订单金额，必填
        $refund_amount = $order_info['order_price'];

        //退款的原因说明
        $refund_reason = trim('岗隆数码订单全额退款');

        //构造参数
        $RequestBuilder = new \AlipayTradeRefundContentBuilder();
        $RequestBuilder->setOutTradeNo($out_trade_no);
        $RequestBuilder->setRefundAmount($refund_amount);
        $RequestBuilder->setRefundReason($refund_reason);

        $aop = new \AlipayTradeService($config);

        $response = (array)$aop->Refund($RequestBuilder);

        /*
         *    //支付宝返回状态码
        'code' => '10000',
        'msg' => 'Success',
        'buyer_logon_id' => '138******17',
        'buyer_user_id' => '2088702746395409',
        'fund_change' => 'Y',
        'gmt_refund_pay' => '2019-07-04 14:46:40',
        'out_trade_no' => '1562218905FGZAFM',
        'refund_fee' => '0.01',
        'send_back_fee' => '0.00',
        'trade_no' => '2019070422001495401056417104',*/


        if ($response['code'] === "10000" && $response['msg'] === "Success" && $response['fund_change'] === "Y") {
            //改变订单状态
            $PaymentClass = new Payment();
            $PaymentClass->orderSn = $order_info['order_sn'];
            $PaymentClass->OrderRefundSuccess();

            return true;

        } else {
            //3、失败
            Log::write($response, 'error');
            Log::write('支付宝PC退款失败(订单号：' . $order_info['order_sn'] . ')', 'error');
            return false;
        }

    }

    /**
     * @param $order_info
     * @return mixed
     * @throws \app\lib\exception\CommonException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     *  支付查询
     */
    public function startQuery($order_info)
    {
        $config = [];//可以不写，只是为了避免IDE报错
        require_once($this->file . 'config.php');
        require_once($this->file . 'pagepay/service/AlipayTradeService.php');
        require_once($this->file . 'pagepay/buildermodel/AlipayTradeQueryContentBuilder.php');

        //商户订单号，商户网站订单系统中唯一订单号
        $out_trade_no = $order_info['order_sn'];

        //构造参数
        $RequestBuilder = new \AlipayTradeQueryContentBuilder();
        $RequestBuilder->setOutTradeNo($out_trade_no);


        $aop = new \AlipayTradeService($config);

        $response = (array)($aop->Query($RequestBuilder));


        /*支付宝返回参数
        'code' => '10000',
        'msg' => 'Success',
        'buyer_logon_id' => '138******17',
        'buyer_pay_amount' => '0.00',
        'buyer_user_id' => '2088702746395409',
        'invoice_amount' => '0.00',
        'out_trade_no' => '1562218905FGZAFM',
        'point_amount' => '0.00',
        'receipt_amount' => '0.00',
        'send_pay_date' => '2019-07-04 13:41:50',
        'total_amount' => '0.01',
        'trade_no' => '2019070422001495401056417104',
        'trade_status' => 'TRADE_SUCCESS',*/

        if ($response['code'] === "10000" && $response['msg'] === 'Success' && $response['trade_status'] === 'TRADE_SUCCESS') {
            $result["msg"] = $response["msg"];
            $result["success"] = true;
            $result["status"] = $response["trade_status"];
            /*修改订单状态*/
            if ($order_info['order_state'] === 1) {
                $PaymentClass = new Payment();
                $PaymentClass->orderSn = $order_info['order_sn'];
                $third_party_sn_array['ali_pay_order_sn'] = $response['trade_no'];
                $PaymentClass->OrderPaySuccess($third_party_sn_array);
            }

        } else {
            $result["msg"] = $response["msg"];
            $result["success"] = false;
            $result["status"] = $response["trade_status"];
        }


        return $result;
    }

    /**
     * @return \think\response\View
     * 生成支付HTML
     */
    private function paymentHtml()
    {
        return view('/PcAliPayment')->assign('html_info', $this->paymentHtmlInfo);

    }

}