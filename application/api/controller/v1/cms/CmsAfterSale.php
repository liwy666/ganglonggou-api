<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/6/10
 * Time: 13:10
 */

namespace app\api\controller\v1\cms;



use app\api\model\GlAfterSale;
use app\api\service\SerAfterSale;
use app\api\service\UserAuthority;
use app\api\validate\CurrencyValidate;

class CmsAfterSale
{
    public function giveAfterSaleListByPage(){

        //验证必要
        (new CurrencyValidate())->myGoCheck(['page', 'limit'], 'require');
        //验证正整数
        (new CurrencyValidate())->myGoCheck(['page', 'limit'], 'positiveInt');
        UserAuthority::checkAuthority(8);
        $data['page'] = request()->param('page');
        $data['limit'] = request()->param('limit');
        $where['is_del'] = 0;

        $result['list'] = GlAfterSale::where($where)
            ->page($data['page'], $data['limit'])
            ->order('create_time desc')
            ->select();

        $result['count'] = GlAfterSale::where($where)
            ->count();

        return $result;

    }

    /**
     * @return bool
     * @throws \app\lib\exception\CommonException
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * 退款
     */
    public function OrderRefund(){
        //验证必要
        (new CurrencyValidate())->myGoCheck(['order_sn', 'after_sale_id'], 'require');
        //验证正整数
        (new CurrencyValidate())->myGoCheck(['id'], 'positiveInt');

        $after_sale_id = request()->param('after_sale_id');
        $order_sn = request()->param('order_sn');


        $AfterSaleClass = new SerAfterSale();

        $AfterSaleClass->orderSn = $order_sn;
        $AfterSaleClass->afterSaleId = $after_sale_id;

        return $AfterSaleClass->cmsOrderRefund();
    }
}