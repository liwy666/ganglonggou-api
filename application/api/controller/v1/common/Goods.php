<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/5/21
 * Time: 13:32
 */

namespace app\api\controller\v1\common;


use app\api\model\GlGoods;
use app\api\model\GlGoodsEvaluate;
use app\api\model\GlGoodsGallery;
use app\api\model\GlGoodsSku;
use app\api\service\SerCoupon;
use app\api\service\SerSupplierPreview;
use app\api\validate\CurrencyValidate;
use app\lib\exception\CommonException;
use Exception;

class Goods
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
    public function giveExtraGoodsInfo()
    {

        //验证必要
        (new CurrencyValidate())->myGoCheck(['goods_id', 'into_type'], 'require');
        //验证正整数
        (new CurrencyValidate())->myGoCheck(['goods_id'], 'positiveInt');
        $data['goods_id'] = request()->param('goods_id');
        $into_type = request()->param('into_type');

        try {
            $parent_id = config('my_config.parentId_by_intoType')[$into_type];
        } catch (Exception $e) {
            throw new CommonException(['msg' => '无此入口']);
        };

        $result['goods_gallery'] = GlGoodsGallery::where($data)
            ->select();

        $result['goods_sku'] = GlGoodsSku::where($data)
            ->select();

        $result['coupon_list'] = (new SerCoupon())->giveUsableCouponByGoodsIdAndIntoType($data['goods_id'], $into_type);

        $result['supplier_preview_info'] = (new SerSupplierPreview())->giveSupplierPreviewByGoodsId($data['goods_id'], $parent_id);

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
     * 通过goodsId获取商品信息
     */
    public function giveGoodsInfoByGoodsId()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['goods_id'], 'require');
        //验证正整数
        (new CurrencyValidate())->myGoCheck(['goods_id'], 'positiveInt');

        $goods_id = request()->param('goods_id');

        $result = GlGoods::giveScreenGoodsInfo($goods_id);

        return $result;

    }

    /**
     * @throws \app\lib\exception\CommonException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取商品列表
     */
    public function giveGoodsList()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['into_type'], 'require');
        $into_type = request()->param('into_type');
        switch ($into_type) {
            case 'abc':
                $parent_id = 154;
                break;
            case 'wx':
                $parent_id = 168;
                break;
            case '3c618mobile':
                $parent_id = 0;
                break;
            case '3c618pc':
                $parent_id = 0;
                break;
            default:
                throw new CommonException(['msg' => '无此入口']);
        }

        $result = GlGoods::giveGoodsListByParentId($parent_id);

        return $result;
    }
}