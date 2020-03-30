<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/5/17
 * Time: 14:52
 */

namespace app\api\model;


class GlMakeOrder extends BaseModel
{


    public function getMakeTimeAttr($value,$data){
        if($value!=null){
            return date("Y-m-d H:i:s",$value);
        }else{
            return $value;
        }
    }

    /**
     * @param $make_order_sn
     * @return array|\PDOStatement|string|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 通过订单号返回订单信息
     */
    public static function giveOrderInfoByOrderSn($make_order_sn){
        return self::where(['make_order_sn'=>$make_order_sn])
            ->find();
    }
}