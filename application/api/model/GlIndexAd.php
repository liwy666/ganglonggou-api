<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/5/17
 * Time: 9:09
 */

namespace app\api\model;


use think\facade\Cache;

class GlIndexAd extends BaseModel
{
    public function getAdImgAttr($value, $data)
    {
        return $this->spellOriginalImg($value, $data);
    }

    /**
     * @param $into_type
     * @return array|mixed|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 通过入口获取广告
     */
    public static function giveIndexAdListByIntoType($into_type)
    {
        $result = Cache::get($into_type . '_user_ad_index_list');
        $debug = config('my_config.debug');

        if (!$result || $debug) {
            $result = self::where(['into_type' => $into_type, 'is_on_sale' => 1])
                ->order(['position_type', 'sort_order' => 'desc'])
                ->select();

            //Cache::set($into_type . '_user_ad_index_list', $result, config('my_config.sql_sel_cache_time'));
            Cache::set($into_type . '_user_ad_index_list', $result, 0);
        }
        return $result;
    }
}