<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/6/18
 * Time: 9:50
 */

namespace app\api\service;


use app\api\model\GlCategory;
use app\api\model\GlGoods;
use app\api\model\GlSupplier;

class SerSupplierPreview
{
    /**
     * @param $goods_id
     * @param $parent_id
     * @return array|\PDOStatement|string|\think\Model|null
     * @throws \app\lib\exception\CommonException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 供应商缩略
     */
    public function giveSupplierPreviewByGoodsId($goods_id, $parent_id)
    {
        $goods_info = GlGoods::where([
            ['goods_id', '=', $goods_id]
        ])
            ->find();
        $supplier_info = GlSupplier::where([
            ['id', '=', $goods_info['supplier_id']],
            ['is_del', '=', 0]
        ])
            ->field('allow_del,is_del', true)
            ->find();

        $supplier_info['goods_list'] = GlGoods::giveGoodsListBySupplierId($goods_info['supplier_id'], $parent_id, 6);

        return $supplier_info;
    }
}