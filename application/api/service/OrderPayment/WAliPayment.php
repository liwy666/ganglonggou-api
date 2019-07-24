<?php
/**
 * Created by PhpStorm.
 * User: zhl
 * Date: 2019-07-23
 * Time: 17:15
 */

namespace app\api\service\OrderPayment;

use think\Exception;
use think\facade\Log;
use think\facade\Request;
use think\response\Json;
class WAliPayment
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
        $this->file = dirname(\think\facade\Env::get('root_path')) . '/extend/WAliPay/';
        $this->notifyUrl = config('my_config.api_url') . 'api/v1/notify/Wali_pay_notify';
    }

}