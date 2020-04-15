<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/5/17
 * Time: 12:55
 */

namespace app\api\controller\v1\common;


use app\api\model\GlCategory;
use app\api\model\GlGoods;
use app\api\model\GlIndexAd;
use app\api\model\GlIntoCount;
use app\api\validate\CurrencyValidate;
use app\lib\exception\CommonException;
use Exception;


class Index
{
    /**
     * @return mixed
     * @throws CommonException
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取首页信息
     */
    public function giveIndexInfo()
    {

        //验证必要
        (new CurrencyValidate())->myGoCheck(['into_type'], 'require');
        $into_type = request()->param('into_type');
        try {
            $parent_id = config('my_config.parentId_by_intoType')[$into_type];
        } catch (Exception $e) {
            throw new CommonException(['msg' => '无此入口']);
        };


        $result['ad_list'] = GlIndexAd::giveIndexAdListByIntoType($into_type);

        $result['cat_list'] = $parent_id === -1 ? null : GlCategory::giveCatListByParentId($parent_id);

        $result['goods_list'] = $parent_id === -1 ? null : GlGoods::giveGoodsListByParentId($parent_id);

        /*流量统计*/
        $into_date = date("Y/m/d");

        if (GlIntoCount::where([
            ['into_type', '=', $into_type],
            ['into_date', '=', $into_date]
        ])->find()
        ) {
            GlIntoCount::where([
                ['into_type', '=', $into_type],
                ['into_date', '=', $into_date]
            ])->setInc('into_count');
        } else {
            GlIntoCount::create([
                'into_type' => $into_type,
                'into_date' => $into_date,
                'into_count' => 1,
            ]);
        }

        return $result;

    }

    /**
     * @return bool
     * @throws CommonException
     * @throws \think\Exception
     * 广告点击量统计
     */
    public function countIndexAdItem()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['id'], 'require');
        //验证正整数
        (new CurrencyValidate())->myGoCheck(['id'], 'positiveInt');
        $id = request()->param('id');
        GlIndexAd::where([
            ['id', '=', $id]
        ])
            ->setInc('click_count');

        return true;
    }
}