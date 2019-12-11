<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/5/17
 * Time: 14:09
 */

namespace app\api\service\Order\MakeOrder;


use app\api\model\GlGoods;
use app\api\model\GlGoodsSku;
use app\api\model\GlMakeOrder;
use app\api\service\Email;
use app\api\service\SerEmail;
use app\lib\exception\CommonException;

class MakeOrder
{
    public $goodsId;
    public $intoType;
    public $skuId;
    public $goodsInfo;
    public $skuInfo;
    public $goodsNumber;
    public $makeName;
    public $makePhone;
    public $makeAddress;
    public $makeRemake;
    public $makeOrderPrice;
    public $makeOrderSn;

    public function __construct()
    {

    }

    /**
     * @throws CommonException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function initMakeOrder(){
        $this->goodsInfo = GlGoods::where(['goods_id' => $this->goodsId, 'is_on_sale' => 1, 'is_del' => 0])
            ->find();
        if (!$this->goodsInfo) {
            throw new CommonException(['msg' => '无效商品']);
        }
        $this->goodsInfo = $this->goodsInfo->toArray();

        $sku_where = [];
        array_push($sku_where,['goods_id','=',$this->goodsId]);
        array_push($sku_where,['sku_id','=',$this->skuId]);
        array_push($sku_where,['sku_stock','>=',$this->goodsNumber]);
        $this->skuInfo = GlGoodsSku::where($sku_where)
            ->find();
        if (!$this->skuInfo) {
            throw new CommonException(['msg' => '无效sku']);
        }
        $this->skuInfo = $this->skuInfo->toArray();
    }


    /**
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 添加订单
     */
    public function addMakeOrder()
    {
        /*生成订单号*/
        $this->giveMakeOrderSn();
        /*计算订单价格*/
        $this->countMakePricer();
        /*生成订单*/
        $data['make_order_sn'] = $this->makeOrderSn;
        $data['make_state'] = 0;
        $data['into_type'] = $this->intoType;
        $data['goods_id'] = $this->goodsId;
        $data['goods_name'] = $this->goodsInfo['goods_name'];
        $data['sku_id'] = $this->skuId;
        $data['sku_desc'] = $this->skuInfo['sku_desc'];
        $data['goods_number'] = $this->goodsNumber;
        $data['make_order_price'] = $this->makeOrderPrice;
        $data['make_name'] = $this->makeName;
        $data['make_phone'] = $this->makePhone;
        $data['make_time'] = time();
        $data['make_address'] = $this->makeAddress;
        $data['make_remake'] = $this->makeRemake;
        $data['is_del'] = 0;

        GlMakeOrder::create($data);

        /*减去库存*/
        GlGoods::where(['goods_id'=>$this->goodsId])
            ->setDec('goods_stock',$this->goodsNumber);
        GlGoodsSku::where(['sku_id'=>$this->skuId])
            ->setDec('sku_stock',$this->goodsNumber);
        /*增加销量*/
        GlGoods::where(['goods_id'=>$this->goodsId])
            ->setInc('goods_sales_volume',$this->goodsNumber);
        /*发送邮件*/
        $head = '新预约提醒';
        $body = '  用户成功预约，预约入口：'.$this->intoType.'，预约单号：'.$this->makeOrderSn;
        if (!config('my_config.debug')) {
            //正式用
            $address_array = [
                '987303897@qq.com',
                '382506262@qq.com'
            ];
        } else {
            //测试用
            $address_array = ['987303897@qq.com'];
        }
        (new SerEmail())->sendEmail($head, $body, $address_array);

    }

    /**
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     *生成订单号
     */
    private function giveMakeOrderSn()
    {

        do {
            $make_order_sn = 'M' . time() . getRandCharD(5);
        } while (GlMakeOrder::giveOrderInfoByOrderSn($make_order_sn));

        $this->makeOrderSn = $make_order_sn;

    }

    /**
     * 计算订单价格
     */
    private function countMakePricer(){

        $this->makeOrderPrice = $this->skuInfo['sku_shop_price'] * $this->goodsNumber;

    }
}