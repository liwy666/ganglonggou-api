<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/6/18
 * Time: 17:18
 */

namespace app\api\controller\v1\cms;


use app\api\model\GlArticle;
use app\api\service\UserAuthority;
use app\api\validate\CurrencyValidate;
use app\lib\exception\CommonException;
use think\facade\Cache;

class CmsArticle
{
    /**
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \app\lib\exception\CommonException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 返回所以文章
     */
    public function giveAllArticle()
    {
        //权限
        UserAuthority::checkAuthority(8);

        return GlArticle::where([
            ['is_del', '=', 0]
        ])
            ->select();
    }

    /**
     * @return bool
     * @throws \app\lib\exception\CommonException
     * 添加文章
     */
    public function addArticle()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['article_name', 'article_body'], 'require');
        //权限
        UserAuthority::checkAuthority(8);

        GlArticle::create([
            'article_name' => request()->param('article_name'),
            'article_body' => removeImgUrl(request()->param('article_body')),
            'click_count' => 0,
            'add_time' => time(),
            'is_del' => 0,
            'allow_del' => 1,
        ]);

        return true;
    }

    /**
     * @return bool
     * @throws \app\lib\exception\CommonException
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 修改文章
     */
    public function updArticle()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['article_name', 'article_body', 'article_id'], 'require');
        //权限
        UserAuthority::checkAuthority(8);

        $article_id = request()->param('article_id');

        GlArticle::where([
            ['id', '=', $article_id]
        ])
            ->update([
                'article_name' => request()->param('article_name'),
                'article_body' => removeImgUrl(request()->param('article_body')),
            ]);

        /*更新缓存*/
        Cache::rm($article_id . '_article');

        return true;

    }

    /**
     * @throws CommonException
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 删除文章
     */
    public function delArticle()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['article_id'], 'require');
        //权限
        UserAuthority::checkAuthority(8);

        $upd_number = GlArticle::where([
            ['id', '=', request()->param('article_id')],
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