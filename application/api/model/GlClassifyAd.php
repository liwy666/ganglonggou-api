<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/6/13
 * Time: 13:50
 */

namespace app\api\model;


use think\facade\Cache;

class GlClassifyAd extends BaseModel
{
    public function getLogoImgAttr($value, $data)
    {
        return $this->spellOriginalImg($value, $data);
    }

    public function getBarImgAttr($value, $data)
    {
        return $this->spellOriginalImg($value, $data);
    }

    /**
     * @param $into_type
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 返回分类列表通过入口方式
     */
    public static function giveClassifyAdListByIntoType($into_type)
    {
        $result = Cache::get($into_type . '_user_classify_ad_list');
        $debug = config('my_config.debug');
        if (!$result || $debug) {
            $result = self::where([
                ['into_type', '=', $into_type]
            ])
                ->order(['parent_id', 'sort_order' => 'desc'])
                ->select();

            Cache::set($into_type . '_user_classify_ad_list', $result, config('my_config.sql_sel_cache_time'));
        }


        return $result;
    }
}