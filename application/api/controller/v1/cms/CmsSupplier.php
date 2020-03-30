<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/6/17
 * Time: 15:12
 */

namespace app\api\controller\v1\cms;


use app\api\model\GlGoods;
use app\api\model\GlSupplier;
use app\api\service\UserAuthority;
use app\api\validate\CurrencyValidate;
use app\lib\exception\CommonException;
use think\Db;

class CmsSupplier
{
    /**
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \app\lib\exception\CommonException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 返回所有供应商
     */
    public function giveAllSupplier()
    {
        //权限
        UserAuthority::checkAuthority(8);

        return GlSupplier::where([
            ['is_del', '=', 0]
        ])
            ->select();
    }

    /**
     * @return bool
     * @throws \app\lib\exception\CommonException
     * 添加供应商
     */
    public function addSupplier()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck([
            'supplier_name',
            'company_name',
            'colour',
            'service_tel',
            'after_sale_tel',
            'describe_rate',
            'service_rate',
            'logistics_rate',
            'follow_number',
        ], 'require');
        //验证正整数
        (new CurrencyValidate())->myGoCheck(['follow_number'], 'positiveInt');
        //权限
        UserAuthority::checkAuthority(8);

        GlSupplier::create([
            'supplier_name' => request()->param('supplier_name'),
            'company_name' => request()->param('company_name'),
            'colour' => request()->param('colour'),
            'service_tel' => request()->param('service_tel'),
            'after_sale_tel' => request()->param('after_sale_tel'),
            'describe_rate' => request()->param('describe_rate'),
            'service_rate' => request()->param('service_rate'),
            'logistics_rate' => request()->param('logistics_rate'),
            'follow_number' => request()->param('follow_number'),
            'logo_img' => removeImgUrl(request()->param('logo_img')),
            'head_img' => removeImgUrl(request()->param('head_img')),
            'is_del' => 0,
            'allow_del' => 1,
        ]);

        return true;
    }

    /**
     * @return bool
     * @throws \app\lib\exception\CommonException
     * 编辑供应商
     */
    public function updSupplier()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck([
            'id',
            'supplier_name',
            'company_name',
            'colour',
            'service_tel',
            'after_sale_tel',
            'describe_rate',
            'service_rate',
            'logistics_rate',
            'follow_number',
        ], 'require');
        //验证正整数
        (new CurrencyValidate())->myGoCheck(['follow_number', 'id'], 'positiveInt');
        //权限
        UserAuthority::checkAuthority(8);

        Db::transaction(function () {
            /*更新供应商表*/
            GlSupplier::update([
                'id' => request()->param('id'),
                'supplier_name' => request()->param('supplier_name'),
                'company_name' => request()->param('company_name'),
                'colour' => request()->param('colour'),
                'service_tel' => request()->param('service_tel'),
                'after_sale_tel' => request()->param('after_sale_tel'),
                'describe_rate' => request()->param('describe_rate'),
                'service_rate' => request()->param('service_rate'),
                'logistics_rate' => request()->param('logistics_rate'),
                'follow_number' => request()->param('follow_number'),
                'logo_img' => removeImgUrl(request()->param('logo_img')),
                'head_img' => removeImgUrl(request()->param('head_img')),
            ]);
            /*更新商品表*/
            GlGoods::where([
                'supplier_id' => request()->param('id')
            ])
                ->update([
                    'supplier_name' => request()->param('supplier_name')
                ]);
        });
        return true;
    }

    /**
     * @return bool
     * @throws CommonException
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 删除供应商
     */
    public function delSupplier()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['id'], 'require');
        //验证正整数
        (new CurrencyValidate())->myGoCheck(['id'], 'positiveInt');
        //权限
        UserAuthority::checkAuthority(8);

        /*先检查是否有商品属于这个供应商*/
        $goods_number = GlGoods::where([
            ['supplier_id', '=', request()->param('id')],
            ['is_del', '=', 0]
        ])
            ->select();

        if (count($goods_number) > 0) {
            throw new CommonException(['msg' => '删除失败,此供应商下还有商品']);
        }

        $upd_number = GlSupplier::where([
            ['is_del', '=', 0],
            ['allow_del', '=', 1]
        ])
            ->update([
                'is_del' => 1
            ]);

        if ($upd_number < 1) {
            throw new CommonException(['msg' => '删除失败']);
        }

        return true;


    }
}