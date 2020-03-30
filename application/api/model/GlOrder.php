<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/5/28
 * Time: 15:55
 */

namespace app\api\model;


class GlOrder extends BaseModel
{

    static private $screenOrderInfo = 'order_sn,user_id,user_name,order_state,original_order_price,
    after_using_coupon_price,after_using_integral_price,after_using_pay_price,order_price,
    give_integral,pay_name,pay_code,bystages_val,create_time,upd_time,invalid_pay_time,logistics_name,
    logistics_tel,logistics_address,logistics_code,logistics_sn,pay_time,deliver_goods_time,
    sign_goods_time,invalid_sign_goods_time,order_visible_note,son_into_type';

    public function getCreateTimeAttr($value, $data)
    {
        if ($value != null) {
            return date("Y-m-d H:i:s", $value);
        } else {
            return $value;
        }
    }

    public function getUpdTimeAttr($value, $data)
    {
        if ($value != null) {
            return date("Y-m-d H:i:s", $value);
        } else {
            return $value;
        }
    }

    public function getPayTimeAttr($value, $data)
    {
        if ($value != null) {
            return date("Y-m-d H:i:s", $value);
        } else {
            return $value;
        }
    }

    public function getDeliverGoodsTimeAttr($value, $data)
    {
        if ($value != null) {
            return date("Y-m-d H:i:s", $value);
        } else {
            return $value;
        }
    }

    public function getInvalidSignGoodsTimeAttr($value, $data)
    {
        if ($value != null) {
            return date("Y-m-d H:i:s", $value);
        } else {
            return $value;
        }
    }

    public function getSignGoodsTimeAttr($value, $data)
    {
        if ($value != null) {
            return date("Y-m-d H:i:s", $value);
        } else {
            return $value;
        }
    }

    public function getRefundTimeAttr($value, $data)
    {
        if ($value != null) {
            return date("Y-m-d H:i:s", $value);
        } else {
            return $value;
        }
    }

    public function glMidOrder()
    {
        return $this->hasMany('GlMidOrder', 'order_sn', 'order_sn');
    }

    public static function getOrderInfoByOrderSn($order_sn)
    {
        $result = self::where([['order_sn', '=', $order_sn]])
            ->find();

        return $result;
    }


    public static function getScreenOrderInfoByOrderSnAndUserId($order_sn, $user_id)
    {

        $order_info = self::where([
            ['order_sn', '=', $order_sn],
            ['user_id', '=', $user_id],
            ['is_del', '=', 0]
        ])
            ->field(self::$screenOrderInfo)
            ->find();
        if ($order_info) {
            $order_info['order_state_name'] = config('my_config.order_state_name')[$order_info['order_state']];
            $order_info['logistics_code_name'] = config('my_config.logistics_code_name')[$order_info['logistics_code']];
        }
        return $order_info;

    }

    public static function adminGetOrderList($where, $page, $limit)
    {
        $result['list'] = self::join('gl_mid_order mo', 'gl_order.order_sn = mo.order_sn')
            ->join('gl_goods g', 'mo.goods_id = g.goods_id')
            ->join('gl_category c', 'g.cat_id = c.cat_id')
            ->where($where)
            ->page($page, $limit)
            ->order('gl_order.create_time desc')
            ->field('gl_order.order_sn,gl_order.order_state,gl_order.create_time,gl_order.pay_time,
                            gl_order.pay_name,gl_order.bystages_val,gl_order.original_order_price,gl_order.after_using_coupon_price,
                            gl_order.order_price,gl_order.logistics_name,gl_order.logistics_tel,gl_order.logistics_address,
                            c.cat_name,gl_order.son_into_type_name,mo.goods_name')
            ->select();

        $result['count'] = self::join('gl_mid_order mo', 'gl_order.order_sn = mo.order_sn')
            ->join('gl_goods g', 'mo.goods_id = g.goods_id')
            ->join('gl_category c', 'g.cat_id = c.cat_id')
            ->where($where)
            ->count();

        return $result;


    }
}