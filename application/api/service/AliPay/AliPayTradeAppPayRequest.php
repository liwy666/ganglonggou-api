<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/11/25
 * Time: 15:54
 */

namespace app\api\service\AliPay;


class AliPayTradeAppPayRequest
{
    /**
     * app支付接口2.0
     **/
    private $bizContent = "";
    private $bizContentArr = array();
    private $apiParas = array();
    private $terminalType;
    private $terminalInfo;
    private $prodCode;
    private $apiVersion = "1.0";
    private $notifyUrl;
    private $returnUrl;
    private $needEncrypt = false;


    public function setBizContent($bizContent)
    {
        $this->bizContent = $bizContent;
        $this->apiParas["biz_content"] = $bizContent;
    }

    public function getBizContent()
    {
        return $this->bizContent;
    }

    public function getBizContentArr()
    {
        return $this->bizContentArr;
    }

    public function getApiMethodName()
    {
        return "alipay.trade.app.pay";
    }

    public function setNotifyUrl($notifyUrl)
    {
        $this->notifyUrl = $notifyUrl;
    }

    public function getNotifyUrl()
    {
        return $this->notifyUrl;
    }

    public function setReturnUrl($returnUrl)
    {
        $this->returnUrl = $returnUrl;
    }

    public function getReturnUrl()
    {
        return $this->returnUrl;
    }

    public function getApiParas()
    {
        return $this->apiParas;
    }

    public function getTerminalType()
    {
        return $this->terminalType;
    }

    public function setTerminalType($terminalType)
    {
        $this->terminalType = $terminalType;
    }

    public function getTerminalInfo()
    {
        return $this->terminalInfo;
    }

    public function setTerminalInfo($terminalInfo)
    {
        $this->terminalInfo = $terminalInfo;
    }

    public function getProdCode()
    {
        return $this->prodCode;
    }

    public function setProdCode($prodCode)
    {
        $this->prodCode = $prodCode;
    }

    public function setApiVersion($apiVersion)
    {
        $this->apiVersion = $apiVersion;
    }

    public function getApiVersion()
    {
        return $this->apiVersion;
    }

    public function setNeedEncrypt($needEncrypt)
    {

        $this->needEncrypt = $needEncrypt;

    }

    public function getNeedEncrypt()
    {
        return $this->needEncrypt;
    }


    public function setBody($body)
    {
        $this->bizContentArr['body'] = $body;
    }

    public function setSubject($subject)
    {
        $this->bizContentArr['subject'] = $subject;
    }

    public function setOutTradeNo($out_trade_no)
    {
        $this->bizContentArr['out_trade_no'] = $out_trade_no;
    }

    public function setTimeoutExpress($timeout_express = '30m')
    {
        $this->bizContentArr['timeout_express'] = $timeout_express;
    }

    public function setTotalAmount($total_amount)
    {
        $this->bizContentArr['total_amount'] = $total_amount;
    }

    public function setProduct_code($product_code = 'QUICK_MSECURITY_PAY')
    {
        $this->bizContentArr['product_code'] = $product_code;
    }

    /**
     * 关闭分期支付（关闭花呗）
     */
    public function shutPcedit()
    {
        $this->bizContentArr['goods_type'] = 1;
        $this->bizContentArr['disable_pay_channels'] = "pcreditpayInstallment";
    }

    /**
     * @param int $hb_fq_num
     * @param int $hb_fq_seller_percent
     * 只启用花呗支付
     */
    public function allowPcedit($hb_fq_num = 3, $hb_fq_seller_percent = 100)
    {
        $this->bizContentArr['enable_pay_channels'] = "pcreditpayInstallment";
        $this->bizContentArr['goods_type'] = 1;
        $this->bizContentArr['extend_params']['hb_fq_num'] = $hb_fq_num;
        $this->bizContentArr['extend_params']['hb_fq_seller_percent'] = $hb_fq_seller_percent;
    }

}