<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/5/16
 * Time: 9:18
 */

namespace app\api\model;


use think\facade\Cache;

class GlCategory extends BaseModel
{

    public static function giveCatListByParentId($parent_id)
    {
        $result = Cache::get($parent_id . '_user_cat_list');
        $debug = config('my_config.debug');

        if (!$result || $debug) {
            $result = self::where(['parent_id' => $parent_id])
                ->order(['sort_order' => 'desc'])
                ->field('cat_id,cat_name,sort_order')
                ->select();

            Cache::set($parent_id . '_user_cat_list', $result, config('my_config.sql_sel_cache_time'));
        }
        return $result;
    }

}