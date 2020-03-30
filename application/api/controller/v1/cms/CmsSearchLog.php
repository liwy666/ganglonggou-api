<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/11/18
 * Time: 10:07
 */

namespace app\api\controller\v1\cms;


use app\api\model\GlSearchLog;
use app\api\service\UserAuthority;
use app\api\validate\CurrencyValidate;

class CmsSearchLog
{
    /**
     * @return mixed
     * @throws \app\lib\exception\CommonException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function giveSearchLogListByPage()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['page', 'limit'], 'require');
        //验证正整数
        (new CurrencyValidate())->myGoCheck(['page', 'limit'], 'positiveInt');
        UserAuthority::checkAuthority(8);
        $data['page'] = request()->param('page');
        $data['limit'] = request()->param('limit');
        $where = [['is_del', '=', 0]];

        $result['list'] = GlSearchLog::where($where)
            ->page($data['page'], $data['limit'])
            ->order('search_count desc')
            ->select();

        $result['count'] = GlSearchLog::where($where)
            ->count();

        return $result;

    }

    /**
     * @return bool
     * @throws \app\lib\exception\CommonException
     */
    public function addSearchLog()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['search_keyword', 'search_count'], 'require');
        //验证正整数
        (new CurrencyValidate())->myGoCheck(['search_count'], 'positiveInt');
        UserAuthority::checkAuthority(8);

        GlSearchLog::create([
            "search_keyword" => request()->param("search_keyword"),
            "search_count" => request()->param("search_count"),
            "into_type" => "wx",
            "is_verify" => 1,
            "is_del" => 0,
        ]);


        return true;
    }

    /**
     * @return bool
     * @throws \app\lib\exception\CommonException
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function delSearchLog()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['id'], 'require');
        //验证正整数
        (new CurrencyValidate())->myGoCheck(['id'], 'positiveInt');
        UserAuthority::checkAuthority(8);

        GlSearchLog::where([
            ['id', '=', request()->param('id')]
        ])
            ->update([
                'is_del' => 1
            ]);

        return true;
    }

    /**
     * @return bool
     * @throws \app\lib\exception\CommonException
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function verifySearchLog()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['id'], 'require');
        //验证正整数
        (new CurrencyValidate())->myGoCheck(['id'], 'positiveInt');
        UserAuthority::checkAuthority(8);

        GlSearchLog::where([
            ['id', '=', request()->param('id')]
        ])
            ->update([
                'is_verify' => 1
            ]);

        return true;
    }

    /**
     * @return bool
     * @throws \app\lib\exception\CommonException
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function cancelVerifySearchLog()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['id'], 'require');
        //验证正整数
        (new CurrencyValidate())->myGoCheck(['id'], 'positiveInt');
        UserAuthority::checkAuthority(8);
        GlSearchLog::where([
            ['id', '=', request()->param('id')]
        ])
            ->update([
                'is_verify' => 0
            ]);

        return true;
    }
}