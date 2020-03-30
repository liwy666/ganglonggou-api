<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/5/17
 * Time: 13:43
 */

namespace app\api\controller\v1\goods_make;


use app\api\model\GlGoods;
use app\api\model\GlGoodsGallery;
use app\api\model\GlGoodsSku;
use app\api\validate\CurrencyValidate;

class GoodsInfo
{

    /**
     * @return mixed
     * @throws \app\lib\exception\CommonException
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取额外商品信息
     */
    public function giveExtraGoodsInfo(){

        //验证必要
        (new CurrencyValidate())->myGoCheck(['goods_id'], 'require');
        //验证正整数
        (new CurrencyValidate())->myGoCheck(['goods_id'], 'positiveInt');

        $data['goods_id'] = request()->param('goods_id');

        $result['goods_gallery'] = GlGoodsGallery::where($data)
            ->select();

        $result['goods_sku'] = GlGoodsSku::where($data)
            ->select();

        //增加点击量
        GlGoods::where($data)->setInc('click_count');

        return $result;

    }

    /**
     * @return array
     * @throws \app\lib\exception\CommonException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取商品信息
     */
    public function giveGoodsInfoByGoodsId(){

        //验证必要
        (new CurrencyValidate())->myGoCheck(['goods_id'], 'require');
        //验证正整数
        (new CurrencyValidate())->myGoCheck(['goods_id'], 'positiveInt');

        $goods_id = request()->param('goods_id');

        $result = GlGoods::giveScreenGoodsInfo($goods_id);

        return $result;
    }
}