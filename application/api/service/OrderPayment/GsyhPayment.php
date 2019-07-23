<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019-07-18
 * Time: 10:41
 */

namespace app\api\service\OrderPayment;

use app\lib\exception\CommonException;
use think\Exception;
use think\facade\Log;
use think\facade\Request;

class GsyhPayment
{
    private $paymentUrl;//生成的支付html
    private $midOrderInfo;
    private $backUrl;
    private $successUrl;
    private $orderInfo;
    private $byStagesStage;
    private $postResults;
    private $CreatTime;
    /**
     * @param $order_info
     * @param $mid_order_info
     * @param $success_url
     * @param $back_url
     * @return \think\response\View
     * @throws CommonException
     * 发起支付
     */
    public function startPayment($order_info, $mid_order_info, $success_url, $back_url)
    {
        $this->orderInfo = $order_info;
        $this->midOrderInfo = $mid_order_info;
        $this->successUrl = $success_url;
        $this->backUrl = $back_url;
        switch ($this->orderInfo['bystages_id']) {
            case 600:
                $this->byStagesStage = '1';
                break;
            case 612;
                $this->byStagesStage = '12';
                break;
            case 624;
                $this->byStagesStage = '24';
                break;
            default:
                throw new CommonException(['msg' => '无效分期方式']);

        }

        $this->CreatTime= date("YmdHis",time());
        $this->postResults = send_post("http://192.168.0.37:8080/ghdemo/my2/jm.do",
            ["createtime" =>$this->CreatTime,
                "ordersn" => $order_info['order_sn'],
                "orderprice" => $order_info['order_price']*100,
                "bystagesstage" => $this->byStagesStage,
                "goodsid" => $mid_order_info[0]['goods_id'],
                "goodsname" => iconv('UTF-8','GBK',$mid_order_info[0]['goods_name']),
                "goodsnumber" => $mid_order_info[0]['goods_number'],
            ]);
        Log::record($this->postResults);
        if ($this->postResults['qm'] === false) {
            throw new CommonException(['msg' => '签名失败']);
        } else if ($this->postResults['zsgyjm'] === false) {
            throw new CommonException(['msg' => '证书BASE64解码失败']);
        } else if ($this->postResults['qmxxjm'] === false) {
            throw new CommonException(['msg' => '签名信息BASE64解码失败']);
        }

        return $this->paymentHtml();
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

        $payment_info = [
            'tranData' => $this->postResults['tranData'],
            'merSignMsg' => $this->postResults['merSignMsg'],
            'merCert' => $this->postResults['merCert']
        ];

        $order_info = [
            'order_price' => strval($this->orderInfo['order_price']),
            'goods_list' => $goods_list,
            'bank_url' => $this->backUrl,
            'success_url' => $this->successUrl,
            'payment_info' => $payment_info,
        ];
        return view('/GsyhPayment')->assign('order_info', $order_info);

    }
    /**
     * @return \think\response\View
     * 支付回调
     */
    public function notifyProcess()
    {
        $notifyData = $_POST["notifyData"];//明文
        $merVAR = $_POST['merVAR'];
        $signMsg = $_POST['signMsg'];//签名

       /* $merVAR=request()->param('merVAR');//银行返回商户变量
        $notifyData = request()->param('notifyData');//银行返回通知结果数据
        $signMsg = request()->param('signMsg');//银行返回签名数据*/
        Log::write("notifyData:".$notifyData,'debug');
        Log::write("signMsg:".$signMsg,'debug');
        Log::write("merVAR".$merVAR,'debug');
        $notifyDatamw=base64_decode($notifyData);
        log::write($notifyDatamw,'debug');

        /* $r= send_post("http://192.168.0.37:8080/gsyh/my2/yq.do",
             [
                 'notifyDatamw' => $notifyDatamw,
                 'signMsg' => $signMsg,
             ]);
         if($r){
             $p=xml_parser_create();
             xml_parse_into_struct($p,$notifyDatamw,$vals,$index);

         }*/
      /*  require($this->file . 'Result.php');

        $tResult = new \Result();

        $request = Request::instance();

        $tResponse = $tResult->init($request->param("MSG"));

        if ($tResponse->isSuccess()) {

            $order_sn = $tResponse->getValue("OrderNo");
            //支付成功
            $PaymentClass = new Payment();
            $PaymentClass->orderSn = $order_sn;
            $third_party_sn_array['abc_order_sn'] = $tResponse->getValue("VoucherNo");
            try {
                $PaymentClass->OrderPaySuccess($third_party_sn_array);
            } catch (Exception $exception) {
                Log::write('农行异步进入,没有问题，服务器内部错误(订单编号：' . $order_sn . ')', 'error');
                Log::write($exception, 'error');
                $result = $this->callBackHtml();
                return $result;
            }
        } else {
            //支付失败
            Log::write($request->param("MSG"), 'error');
            Log::write('农行异步进入，执行$tResponse->isSuccess()方法不通过', 'error');
        }


        $result = $this->callBackHtml();
        return $result;*/
    }


}