<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/9/4
 * Time: 9:01
 */

namespace app\api\controller\v1\article_sign;


use app\api\model\GlArticleSignActivityUrl;
use app\api\model\GlArticleSignUser;
use app\api\service\UserAuthority;
use app\api\validate\CurrencyValidate;

class Cms
{
    /**
     * @return mixed
     * @throws \app\lib\exception\CommonException
     * 返回用户表
     */
    public function giveUserList()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['page', 'limit'], 'require');
        UserAuthority::checkAuthority(7);
        $data['page'] = request()->param('page');
        $data['limit'] = request()->param('limit');
        $result['list'] = (new GlArticleSignUser)
            ->page($data['page'], $data['limit'])
            ->select();
        $result['count'] = (new GlArticleSignUser)->count();

        return $result;

    }

    public function createShareUrl()
    {

        $url = 'https://mate.ganglonggou.com/article_sign/';

        //验证必要
        (new CurrencyValidate())->myGoCheck(['start_date', 'article_name','admin_token'], 'require');
        //(new CurrencyValidate())->myGoCheck(['start_date'], 'positiveInt');
        UserAuthority::checkAuthority(7);

        $start_date = request()->param('start_date') / 1000;
        $end_date = $start_date + 86400;
        $article_name = request()->param('article_name');
        $url_code = self::generateToken();
        GlArticleSignActivityUrl::create([
            'url_code' => $url_code,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'is_del' => 0,
            'article_name' => $article_name,
        ]);
        $result = $url . '?url_code=' . $url_code;

        return $result;

    }

    /**
     * @return string
     * 生成令牌
     */
    private static function generateToken()
    {
        $randChar = getRandChar(32);
        $timestamp = $_SERVER['REQUEST_TIME_FLOAT'];//得到请求开始时的时间戳
        $tokenSalt = config('my_config.token_salt');
        return md5($randChar . $timestamp . $tokenSalt);
    }
}