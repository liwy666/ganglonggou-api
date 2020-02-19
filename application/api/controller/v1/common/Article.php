<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/6/19
 * Time: 10:20
 */

namespace app\api\controller\v1\common;


use app\api\model\GlArticle;
use app\api\validate\CurrencyValidate;

class Article
{
    /**
     * @return array|mixed|\PDOStatement|string|\think\Model|null
     * @throws \app\lib\exception\CommonException
     * @throws \think\Exception
     * 返回文章
     */
    public function giveArticle()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['article_id'], 'require');
        //验证正整数
        (new CurrencyValidate())->myGoCheck(['article_id'], 'positiveInt');

        $article_id = request()->param('article_id');

        GlArticle::where([
            ['id', '=', $article_id]
        ])
            ->setInc('click_count');

        return GlArticle::giveArticleByArticleId($article_id);

    }
}