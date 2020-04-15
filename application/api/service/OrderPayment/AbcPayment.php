<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/6/3
 * Time: 10:51
 */

namespace app\api\service\OrderPayment;


use app\lib\exception\CommonException;
use think\Exception;
use think\facade\Log;
use think\facade\Request;
use think\response\Json;


class AbcPayment
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

        if ($this->orderInfo['bystages_id'] === 100) {
            $tRequest->order["PayTypeID"] = "ImmediatePay"; //设定交易类型(直接支付)
        } else {
            $tRequest->order["PayTypeID"] = "DividedPay"; //设定交易类型(分期支付)
        }

        $tRequest->order["OrderNo"] = $this->orderInfo['order_sn']; //设定订单编号
        $tRequest->order["OrderAmount"] = $this->orderInfo['order_price']; //设定交易金额
        $tRequest->order["CurrencyCode"] = "156"; //设定交易币种
        if ($this->orderInfo['bystages_id'] === 100) {
            $tRequest->order["InstallmentMark"] = "0"; //分期标识
        } else {
            $tRequest->order["InstallmentMark"] = "1"; //分期标识
        }
        if ($this->orderInfo['bystages_id'] === 100) {

        } else {
            $tRequest->order["InstallmentCode"] = "10035060"; //设定分期代码
            if ($this->orderInfo['bystages_id'] === 112) {
                $tRequest->order["InstallmentNum"] = "12"; //设定分期期数
            } elseif ($this->orderInfo['bystages_id'] === 124) {
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
        $order_item["ProductName"] = "岗隆购农行专区商品购买"; //商品名称
        $order_item["ProductRemarks"] = "岗隆购农行专区"; //商品备注项
        $tRequest->orderitems[0] = $order_item;


//3、生成支付请求对象
        if ($this->orderInfo['bystages_id'] === 100) {
            $tRequest->request["PaymentType"] = "A"; //设定支付类型
        } else {
            $tRequest->request["PaymentType"] = "3"; //设定支付类型
        }
        $tRequest->request["PaymentLinkType"] = "2"; //设定支付接入方式
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
     * 支付回调
     */
    public function notifyProcess()
    {

        require($this->file . 'Result.php');

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
        return $result;
    }


    /**
     * @param $order_info
     * @return bool
     * @throws Exception
     * @throws \think\exception\PDOException
     * 退款
     */
    public function startRefund($order_info)
    {

        require($this->file . 'RefundRequest.php');

        $tRefund = new \RefundRequest();

        $refund_order_sn = Payment::createRefundSn($order_info['order_sn']);

        //1、生成退款请求对象
        $tRefund->request["OrderDate"] = date("Y/m/d"); //订单日期（必要信息）
        $tRefund->request["OrderTime"] = date("H:i:s");//订单时间（必要信息）
        $tRefund->request["OrderNo"] = $order_info["order_sn"]; //原交易编号（必要信息）
        $tRefund->request["NewOrderNo"] = $refund_order_sn; //交易编号（必要信息）
        $tRefund->request["CurrencyCode"] = "156"; //交易币种（必要信息）
        $tRefund->request["TrxAmount"] = $order_info["order_price"]; //退货金额 （必要信息）
        $tRefund->request["MerchantRemarks"] = "江苏岗隆数码自动退款"; //附言

        //2、传送退款请求并取得退货结果
        $tResponse = $tRefund->postRequest();

        if ($tResponse->isSuccess()) {
            //改变订单状态

            $PaymentClass = new Payment();
            $PaymentClass->orderSn = $order_info['order_sn'];
            $PaymentClass->OrderRefundSuccess();

            return true;
        } else {
            //3、失败
            Log::write($tResponse->getReturnCode(), 'error');
            Log::write($tResponse->getErrorMessage(), 'error');
            Log::write('农行退款失败(订单号：' . $order_info['order_sn'] . ')', 'error');
            return false;
        }


    }

    /**
     * @param $order_info
     * @return mixed
     * @throws CommonException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 支付查询
     */
    public function startQuery($order_info)
    {

        require($this->file . 'QueryOrderRequest.php');
        //require($this->file . 'core/Json.php');

        $tRequest = new \QueryOrderRequest();

        if ($order_info['bystages_id'] === 200 || $order_info['bystages_id'] === 100 || $order_info['bystages_id'] === 400) {
            $tRequest->request["PayTypeID"] = "ImmediatePay"; //设定交易类型
        } else {
            $tRequest->request["PayTypeID"] = "DividedPay"; //设定交易类型
        }

        $tRequest->request["OrderNo"] = $order_info["order_sn"]; //设定订单编号 （必要信息）
        $tRequest->request["QueryDetail"] = "true"; //是否查询详细信息

        $tResponse = $tRequest->postRequest();

        if ($tResponse->isSuccess()) {
            //获取结果信息
            $orderInfo = $tResponse->GetValue("Order");
            if ($orderInfo !== null) {
                //1、还原经过base64编码的信息
                $orderDetail = base64_decode($orderInfo);
                $orderDetail = iconv("GB2312", "UTF-8", $orderDetail);
                $detail = new \Json($orderDetail);
                $Status = $detail->GetValue("Status");
                if ($Status === "04") {
                    if ($order_info['order_state'] === 1) {
                        $PaymentClass = new Payment();
                        $PaymentClass->orderSn = $order_info['order_sn'];
                        $third_party_sn_array['abc_order_sn'] = $detail->getValue("VoucherNo");
                        $PaymentClass->OrderPaySuccess($third_party_sn_array);
                    }
                    //更新订单支付时间
                    Log::write('农行支付查询返回信息(订单号：' . $order_info['order_sn'] .
                        'OrderDate：' . $detail->GetValue("OrderDate") .
                        'OrderTime：' . $detail->GetValue("OrderTime") .
                        ')', 'debug');
                    $result["msg"] = "该订单已经支付";
                    $result["success"] = true;
                    $result["status"] = $Status;
                } else {
                    $result["msg"] = $tResponse->getErrorMessage();
                    $result["success"] = false;
                    $result["status"] = $Status;
                }
            } else {
                $result["msg"] = '未获取到农行返回的订单信息' . $tResponse->getErrorMessage();
                $result["success"] = false;
                $result["status"] = '';
            }

        } else {
            $result["msg"] = $tResponse->getErrorMessage();
            $result["success"] = false;
            $result["status"] = "支付失败或未发起过支付";
        }
        return $result;
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

        return view('/abcPayment')->assign('order_info', $order_info);

    }

    /**
     * @return \think\response\View
     * 生成支付回调页面
     */
    private function callBackHtml()
    {

        return view('/abcCallback');


    }

}