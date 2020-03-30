<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/5/17
 * Time: 15:15
 */

namespace app\api\controller\v1\goods_make;


use app\api\validate\CurrencyValidate;

class MakeOrder
{

    /**
     * @return bool
     * @throws \app\lib\exception\CommonException
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 添加订单
     */
    public function addOrder(){
        //验证必要
        (new CurrencyValidate())->myGoCheck(['into_type', 'goods_id','sku_id','goods_number','make_name','make_phone'], 'require');
        //验证正整数
        (new CurrencyValidate())->myGoCheck(['goods_id', 'sku_id','goods_number'], 'positiveInt');

        $MakeOrder = new \app\api\service\Order\MakeOrder\MakeOrder();

        $MakeOrder->intoType = request()->param('into_type');
        $MakeOrder->goodsId = request()->param('goods_id');
        $MakeOrder->skuId = request()->param('sku_id');
        $MakeOrder->goodsNumber = request()->param('goods_number');
        $MakeOrder->makeName = request()->param('make_name');
        $MakeOrder->makePhone = request()->param('make_phone');
        $MakeOrder->makeAddress = request()->param('make_address');
        $MakeOrder->makeRemake = request()->param('make_remake');

        $MakeOrder->initMakeOrder();
        $MakeOrder->addMakeOrder();

        return true;

    }
}