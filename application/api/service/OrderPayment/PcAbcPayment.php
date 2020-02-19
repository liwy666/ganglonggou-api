<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/6/29
 * Time: 15:28
 */

namespace app\api\service\OrderPayment;


use app\lib\exception\CommonException;

class PcAbcPayment
{
    private $file;
    private $paymentUrl;//生成的支付html
    private $midOrderInfo;
    private $backUrl;
    private $successUrl;
    private $orderInfo;

    public function __construct()
    {
        $this->file = dirname(\think\facade\Env::get('root_path')) . '/extend/abc-pay/';
    }

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

        require($this->file . 'PaymentRequest.php');

        $tRequest = new \PaymentRequest();

        $this->orderInfo = $order_info;
        $this->midOrderInfo = $mid_order_info;
        $this->successUrl = $success_url;
        $this->backUrl = $back_url;

        if ($this->orderInfo['bystages_id'] === 400) {
            $tRequest->order["PayTypeID"] = "ImmediatePay"; //设定交易类型(直接支付)
        } else {
            $tRequest->order["PayTypeID"] = "DividedPay"; //设定交易类型(分期支付)
        }

        $tRequest->order["OrderNo"] = $this->orderInfo['order_sn']; //设定订单编号
        $tRequest->order["OrderAmount"] = $this->orderInfo['order_price']; //设定交易金额
        $tRequest->order["CurrencyCode"] = "156"; //设定交易币种
        if ($this->orderInfo['bystages_id'] === 400) {
            $tRequest->order["InstallmentMark"] = "0"; //分期标识
        } else {
            $tRequest->order["InstallmentMark"] = "1"; //分期标识
        }
        if ($this->orderInfo['bystages_id'] === 400) {

        } else {
            $tRequest->order["InstallmentCode"] = "20035060"; //设定分期代码
            if ($this->orderInfo['bystages_id'] === 412) {
                $tRequest->order["InstallmentNum"] = "12"; //设定分期期数
            } elseif ($this->orderInfo['bystages_id'] === 424) {
                $tRequest->order["InstallmentNum"] = "24"; //设定分期期数
            } else {
                throw new CommonException(['msg' => '无效分期方式']);
            }

        }
        $tRequest->order["CommodityType"] = "0202"; //设置商品种类
        $tRequest->order["OrderDesc"] = ("岗隆购农行专区"); //设定订单说明
        $tRequest->order["OrderDate"] = date("Y/m/d"); //设定订单日期 （必要信息 - YYYY/MM/DD）
        $tRequest->order["OrderTime"] = date("H:i:s"); //设定订单时间 （必要信息 - HH:MM:SS）
        //2、订单明细
        $order_item = array();
        $order_item["ProductName"] = "岗隆购pc端商品购买"; //商品名称
        $order_item["ProductRemarks"] = "岗隆购pc端"; //商品备注项
        $tRequest->orderitems[0] = $order_item;


//3、生成支付请求对象
        if ($this->orderInfo['bystages_id'] === 400) {
            $tRequest->request["PaymentType"] = "A"; //设定支付类型
        } else {
            $tRequest->request["PaymentType"] = "3"; //设定支付类型
        }
        $tRequest->request["PaymentLinkType"] = "1"; //设定支付接入方式
        $tRequest->request["UnionPayLinkType"] = "0"; //设定支付接入方式
        $tRequest->request["NotifyType"] = "0"; //设定通知方式
        $tRequest->request["ResultNotifyURL"] = config('my_config.api_url') . 'api/v1/notify/abc_notify'; //设定通知URL地址
        $tRequest->request["IsBreakAccount"] = "0"; //设定交易是否分账

        $tResponse = $tRequest->postRequest();

        if ($tResponse->isSuccess()) {
            $this->paymentUrl = $tResponse->GetValue("PaymentURL");
            return $this->paymentHtml();
        } else {
            throw new CommonException(["msg" => "支付发起失败"]);
        }

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
        ];

        return view('/PcPayment')->assign('order_info', $order_info);

    }
}