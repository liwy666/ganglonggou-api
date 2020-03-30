<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/6/18
 * Time: 17:21
 */

namespace app\api\model;


use think\facade\Cache;

class GlArticle extends BaseModel
{
    public function getAddTimeAttr($value)
    {
        if ($value != null) {
            return date("Y-m-d H:i:s", $value);
        } else {
            return $value;
        }
    }

    public function getArticleBodyAttr($value, $data)
    {
        return $this->imgTagSpellOriginalImg($value, $data);
    }

    public static function giveArticleByArticleId($article_id)
    {
        $result = Cache::get($article_id . '_article');
        $debug = config('my_config.debug');
        if (!$result || $debug) {
            $result = self::where([
                ['id', '=', $article_id]
            ])
                ->field('click_count,is_del,allow_del', true)
                ->find();
            Cache::set($article_id . '_article', $result, config('my_config.sql_sel_cache_time'));
        }

        return $result;
    }
}