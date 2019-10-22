<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/5/23
 * Time: 15:09
 */

namespace app\api\service;


use app\api\model\GlGoods;
use app\api\model\GlGoodsSku;

class SerCart
{
    private $skuInfo;
    private $goodsInfo;

    /**
     * @param $cart_info
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 检查购物车
     */
    public function checkCartInfo($cart_info)
    {
        $result = [];
        $result["cart_is"] = true;//初始化购物车有效

        $this->skuInfo = GlGoodsSku::where([['sku_id', '=', $cart_info['sku_id']]
            , ['goods_id', '=', $cart_info['goods_id']]
            , ['sku_stock', '>=', $cart_info['goods_number']]])
            ->find();
        $this->goodsInfo = GlGoods::where([['goods_id', '=', $cart_info['goods_id']]
            , ['is_del', '=', 0]
            , ['is_on_sale', '=', 1]])
            ->find();
        if($this->skuInfo && $this->goodsInfo){
            $result["goods_id"] = $this->goodsInfo->goods_id;
            $result["goods_name"] = $this->goodsInfo->goods_name;
            $result["goods_head_name"] = $this->goodsInfo ->goods_head_name;//商品头
            $result["goods_number"] = $cart_info["goods_number"];//所选商品数量
            $result["goods_stock"] = $this->skuInfo ->sku_stock;
            $result["sku_id"] = $this->skuInfo ->sku_id;
            $result["attr_desc"] = $this->skuInfo ->sku_desc;
            $result["one_give_integral"] = $this->skuInfo ->give_integral;
            $result["one_integral"] = $this->skuInfo ->integral;
            $result["one_goods_price"] = $this->skuInfo ->sku_shop_price;
            $result["goods_price"] = sprintf("%01.2f",$this->skuInfo ->sku_shop_price * $cart_info["goods_number"]);
            $result["give_integral"] = $this->skuInfo ->give_integral * $cart_info["goods_number"];
            $result["integral"] =$this->skuInfo ->integral * $cart_info["goods_number"];
        }else{
            $result = $cart_info;
            $result["goods_attr"] = [];
            $result["cart_is"] = false;
        }

        return $result;

    }
}