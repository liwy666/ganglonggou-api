<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/11/18
 * Time: 11:29
 */

namespace app\api\controller\v1\common;


use app\api\model\GlSearchLog;
use app\api\validate\CurrencyValidate;

class SearchLog
{
    /**
     * @return array
     * @throws \app\lib\exception\CommonException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getSearchLog()
    {
        (new CurrencyValidate())->myGoCheck(['into_type'], 'require');
        $where = [
            ['into_type', '=', request()->param('into_type')],
            ['is_verify', '=', 1],
            ['is_del', '=', 0],
        ];

        $result = GlSearchLog::where($where)
            ->order(['search_count' => 'desc'])
            ->field("search_keyword")
            ->select()
            ->toArray();

        return $result;
    }

    /**
     * @return bool
     * @throws \app\lib\exception\CommonException
     * @throws \think\Exception
     * 新增关键词
     */
    public function addSearchLog()
    {
        (new CurrencyValidate())->myGoCheck(['into_type', 'son_into_type', 'search_keyword'], 'require');
        $into_type = request()->param('into_type');
        $son_into_type = request()->param('son_into_type');
        $search_keyword = request()->param('search_keyword');

        if (strlen($search_keyword) > 30) return false;

        $set_inc_count = GlSearchLog::where([
            ['search_keyword', '=', $search_keyword]
        ])
            ->setInc('search_count');

        if ($set_inc_count === 0) {
            GlSearchLog::create([
                "search_keyword" => $search_keyword,
                "search_count" => 1,
                "into_type" => $into_type,
                "son_into_type" => $son_into_type,
                "is_verify" => 0,
                "is_del" => 0,
            ]);
        }

        return true;
    }
}