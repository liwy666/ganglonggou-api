<?php


namespace app\api\controller\v1\cms;

use app\api\model\GlCoupon;
use app\api\model\GlIntegralCard;
use app\api\service\UserAuthority;
use app\api\validate\CurrencyValidate;

/**
 * Class CmsIntegralCard
 * @package app\api\controller\v1\cms
 * 积分卡管理
 */
class CmsIntegralCard
{
    public function getIntegralCardList()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['page', 'limit'], 'require');
        //验证正整数
        (new CurrencyValidate())->myGoCheck(['page', 'limit'], 'positiveInt');
        UserAuthority::checkAuthority(8);
        $data['page'] = request()->param('page');
        $data['limit'] = request()->param('limit');
        $where['isDelete'] = 0;

        $result['list'] = GlIntegralCard::where($where)
            ->page($data['page'], $data['limit'])
            ->select();

        $result['count'] = GlCoupon::where($where)
            ->count();

        return $result;

    }
}