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

    public static function getScreenMidOrderInfoByOrderSn($order_sn)
    {

        $mid_order_info = self::where([
            ['order_sn', '=', $order_sn]
        ])
            ->field(self::$ScreenMidOrder_true, true)
            ->select();

        return $mid_order_info;
    }

    public function glOrder()
    {
        return $this->hasOne('GlOrder', 'order_sn', 'order_sn');
    }

    public static function adminGetOrderList($where, $page, $limit)
    {
        $result = self::hasWhere('glOrder', function ($query, $page, $limit) {
            $query->where(['order_sn' => '1559097230TSDGLK'])
                ->page($page, $limit)
                ->order('create_time desc')
                ->select();
        });

        return $result->toArray();
    }
}