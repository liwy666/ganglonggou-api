<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/5/29
 * Time: 10:05
 */

namespace app\api\model;


class GlMidOrder extends BaseModel
{
    static private $ScreenMidOrder_true = 'order_sn,sku_id';

    public function getImgUrlAttr($value, $data)
    {
        return $this->spellOriginalImg($value, $data);
    }

    public static function getScreenMidOrderInfoByOrderSn($order_sn){

        $mid_order_info = self::where([
            ['order_sn','=',$order_sn]
        ])
            ->field(self::$ScreenMidOrder_true,true)
            ->select();

        return $mid_order_info;
    }
}