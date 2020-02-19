<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/5/29
 * Time: 10:16
 */

namespace app\api\model;


class GlOrderInvoice extends BaseModel
{
    static private $screenInvoiceInfo_true = 'id,order_sn';

    public static function getScreenInvoiceInfoByOrderSn($order_sn){

        $invoice_info = self::where([
            ['order_sn','=',$order_sn]
        ])
            ->field(self::$screenInvoiceInfo_true,true)
            ->find();

        return $invoice_info;

    }
}