<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/6/11
 * Time: 14:30
 */

namespace app\api\controller\v1;


use app\api\model\GlCategory;
use app\api\validate\CurrencyValidate;
use think\facade\Cache;

class CleanCache
{
    public function CleanUserGoodsListCache()
    {
        //先清除商品缓存
        $cat_array = GlCategory::where([
            ['parent_id', '<>', 0]
        ])
            ->select();
        foreach ($cat_array as $k => $v) {
            Cache::rm($v['parent_id'] . '_user_goods_list');
        }
        //再清除供应商预览缓存缓存
        Cache::rm($into_type . '_user_ad_index_list');

        return true;
    }

    public function CleanUserIndexAdListCache()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['into_type'], 'require');

        $into_type = request()->param('into_type');

        Cache::rm($into_type . '_user_ad_index_list');

        return true;

    }

    public function CleanUserCatListCache()
    {
        $cat_array = GlCategory::where([
            ['parent_id', '<>', 0]
        ])
            ->select();

        foreach ($cat_array as $k => $v) {
            Cache::rm($v['parent_id'] . '_user_cat_list');
        }

        return true;
    }

    public function CleanUserClassifyListCache()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['into_type'], 'require');

        $into_type = request()->param('into_type');

        Cache::rm($into_type . '_user_classify_ad_list');

        return true;
    }

    public function CleanUserArticleCache()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['article_id'], 'require');

        $article_id = request()->param('article_id');

        Cache::rm($article_id . '_article');

        return true;
    }
}