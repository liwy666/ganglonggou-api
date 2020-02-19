<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/6/14
 * Time: 9:24
 */

namespace app\api\controller\v1\common;


use app\api\model\GlClassifyAd;
use app\api\validate\CurrencyValidate;

class ClassifyAd
{
    /**
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \app\lib\exception\CommonException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 返回分类列表
     */
    public function giveClassifyAdList()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['into_type'], 'require');
        $into_type = request()->param('into_type');

        return GlClassifyAd::giveClassifyAdListByIntoType($into_type);

    }
}