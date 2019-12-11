<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/5/17
 * Time: 12:55
 */

namespace app\api\controller\v1\goods_make;


use app\api\model\GlCategory;
use app\api\model\GlGoods;
use app\api\model\GlIndexAd;
use app\api\validate\CurrencyValidate;
use app\lib\exception\CommonException;

class GoodsMakeIndex
{
    /**
     * @return mixed
     * @throws CommonException
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
        switch ($into_type) {
            case 'redBullMake-hw':
                $parent_id = 140;
                break;
            case 'redBullMake-gl':
                $parent_id = 145;
                break;
            case 'mark-iphone':
                $parent_id = 195;
                break;
            default:
                throw new CommonException(['msg' => '无此入口']);
        }

        $result['ad_list'] = GlIndexAd::where(['into_type'=>$into_type])
            ->order(['position_type','sort_order'=>'desc'])
            ->select();

        $result['cat_list'] = GlCategory::where(['parent_id'=>$parent_id])
            ->field('cat_id,cat_name')
            ->select();

        $result['goods_list'] = GlGoods::giveGoodsListByParentId($parent_id);

        return $result;

    }
}