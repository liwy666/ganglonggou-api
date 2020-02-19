<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/11/25
 * Time: 15:06
 */

namespace app\api\service\OrderPayment;


use app\api\service\AliPay\AliPayTradeAppPayRequest;

class AppAliPayment
{
    private $file;
    private $midOrderInfo;
    private $notifyUrl;
    private $orderInfo;

    public function __construct()
    {
        $this->file = dirname(\think\facade\Env::get('root_path')) . '/extend/PcAliPay/';
        $this->notifyUrl = config('my_config.api_url') . 'api/v1/notify/ali_pay_notify';
    }

    public function startPayment($order_info, $mid_order_info, $success_url, $back_url)
    {
        $this->orderInfo = $order_info;
        $this->midOrderInfo = $mid_order_info;


        $config = [];//可以不写，只是为了避免IDE报错
        require_once($this->file . 'config.php');
        require_once($this->file . 'aop/AopClient.php');
        $aop = new \AopClient;
        $aop->gatewayUrl = "https://openapi.alipay.com/gateway.do";
        $aop->appId = $config['app_id'];
        $aop->rsaPrivateKey = $config['merchant_private_key'];
        $aop->format = "json";
        $aop->charset = $config['charset'];
        $aop->signType = $config['sign_type'];
        $aop->alipayrsaPublicKey = $config['alipay_public_key'];


        /*实例化具体API对应的request类,类名称和接口名称对应,当前调用接口名称：alipay.trade.app.pay*/
        $request = new AliPayTradeAppPayRequest();

        /*生成订单参数*/
        //商户订单号，商户网站订单系统中唯一订单号，必填
        $out_trade_no = $this->orderInfo['order_sn'];
        //付款金额，必填
        $total_amount = $this->orderInfo['order_price'];
        //商品描述，可空
        $body = trim('江苏岗隆数码-商品购买');
        //订单名称
        $goods_name_array = [];
        foreach ($this->midOrderInfo as $k => $v) {
            array_push($goods_name_array, $v['goods_name']);
        }
        $goods_name_str = implode(',', $goods_name_array);
        $goods_name_str = substr($goods_name_str, 0, 250);
        $subject = trim($goods_name_str);

        /*传入订单参数*/
        $request->setBody($body);
        $request->setSubject($subject);
        $request->setOutTradeNo($out_trade_no);
        $request->setTimeoutExpress();
        $request->setTotalAmount($total_amount);
        $request->setProduct_code();
        //判断是否分期支付
        switch ($this->orderInfo['bystages_id']) {
            case 700:
                $request->shutPcedit();
                break;
            case 703:
                $request->allowPcedit(3);
                break;
            case 706:
                $request->allowPcedit(6);
                break;
            case 712:
                $request->allowPcedit(12);
                break;
        }

        $request->setNotifyUrl($this->notifyUrl);
        $request->setBizContent(json_encode($request->getBizContentArr(), JSON_UNESCAPED_UNICODE));
        //这里和普通的接口调用不同，使用的是sdkExecute
        $response = $aop->sdkExecute($request);

        //htmlspecialchars是为了输出到页面时防止被浏览器将关键参数html转义，实际打印到日志以及http传输不会有这个问题
        //return htmlspecialchars($response);//就是orderString 可以直接给客户端请求，无需再做处理。
        return $response;
    }
}