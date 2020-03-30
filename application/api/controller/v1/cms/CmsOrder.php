<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/6/4
 * Time: 16:33
 */

namespace app\api\controller\v1\cms;


use app\api\model\GlMidOrder;
use app\api\model\GlOrder;
use app\api\model\GlOrderInvoice;
use app\api\service\OrderPayment\Payment;
use app\api\service\UserAuthority;
use app\api\validate\CurrencyValidate;
use app\lib\exception\CommonException;

class CmsOrder
{
    /**
     * @return mixed
     * @throws CommonException
     * 分页获取订单
     */
    public function giveOrderListByPage()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['page', 'limit'], 'require');
        //验证正整数
        (new CurrencyValidate())->myGoCheck(['page', 'limit'], 'positiveInt');
        UserAuthority::checkAuthority(8);
        $data['page'] = request()->param('page');
        $data['limit'] = request()->param('limit');
        $where = [['gl_order.is_del', '=', 0]];
        /*订单号*/
        if (request()->param('order_sn') !== '') {
            array_push($where, ['order_sn', 'like', '%' . request()->param('order_sn') . '%']);
        }
        /*收件人*/
        if (request()->param('logistics_address_name') !== '') {
            array_push($where, ['logistics_name', 'like', '%' . request()->param('logistics_address_name') . '%']);
        }
        /*手机号*/
        if (request()->param('logistics_address_phone') !== '') {
            array_push($where, ['logistics_tel', 'like', '%' . request()->param('logistics_address_phone') . '%']);
        }
        /*订单状态*/
        if (request()->param('order_state') !== 'all') {
            /*
             * {value: 'all', label: '所有订单'},
             * {value: 'pending_pay', label: '待支付订单'},
             * {value: 'pending_delivery', label: '待发货'},
             *{value: 'pending_take', label: '待收货'},
             *{value: 'pending_service', label: '退换售后'},*/
            switch (request()->param("order_state")) {
                case 'pending_pay':
                    array_push($where, ['order_state', '=', 1]);
                    break;
                case 'pending_delivery':
                    array_push($where, ['order_state', '=', 2]);
                    break;
                case 'pending_take':
                    array_push($where, ['order_state', '=', 3]);
                    break;
                case 'pending_service':
                    array_push($where, ['order_state', '>=', 6]);
                    break;

            }

        }
        /*订单入口*/
        if (request()->param('son_into_type') !== 'all') {
            array_push($where, ['son_into_type', '=', request()->param('son_into_type')]);

        }
        /*分类*/
        if (request()->param('cat_id') !== 0 && request()->param('cat_id')) {
            array_push($where, ['g.cat_id', '=', request()->param('cat_id')]);
        }


        return GlOrder::adminGetOrderList($where, $data['page'], $data['limit']);


    }

    /**
     * @return mixed
     * @throws CommonException
     * 导出订单
     */
    public function importOrderList()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['start_time', 'end_time'], 'require');
        //验证正整数
        (new CurrencyValidate())->myGoCheck(['start_time', 'end_time'], 'positiveInt');


        UserAuthority::checkAuthority(8);
        $where = [['gl_order.is_del', '=', 0]];

        /*下单时间*/
        array_push($where, ['gl_order.create_time', '>=', request()->param('start_time')]);
        array_push($where, ['gl_order.create_time', '<=', request()->param('end_time')]);

        /*订单状态*/
        if (request()->param('order_state') !== 'all') {
            switch (request()->param("order_state")) {
                case 'pending_pay':
                    array_push($where, ['order_state', '=', 1]);
                    break;
                case 'pending_delivery':
                    array_push($where, ['order_state', '=', 2]);
                    break;
                case 'pending_take':
                    array_push($where, ['order_state', '=', 3]);
                    break;
                case 'pending_service':
                    array_push($where, ['order_state', '>=', 6]);
                    break;

            }
        }
        /*订单入口*/
        if (request()->param('son_into_type') !== 'all') {
            array_push($where, ['son_into_type', '=', request()->param('son_into_type')]);

        }
        /*分类*/
        if (request()->param('cat_id') !== 0 && request()->param('cat_id')) {
            array_push($where, ['g.cat_id', '=', request()->param('cat_id')]);
        }

        return GlOrder::adminGetOrderList($where, 1, 1000);


    }

    /**
     * @return mixed
     * @throws \app\lib\exception\CommonException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 返回订单额外信息
     */
    public function extraOrderInfoByOrderSn()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['order_sn'], 'require');
        UserAuthority::checkAuthority(8);

        $order_sn = request()->param('order_sn');

        $result['order_invoice'] = GlOrderInvoice::where([
            ['order_sn', '=', $order_sn]
        ])
            ->find();

        $result['mid_order'] = GlMidOrder::where([
            ['order_sn', '=', $order_sn]
        ])
            ->select();

        return $result;
    }

    /**
     * @return array|\PDOStatement|string|\think\Model|null
     * @throws CommonException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 返回订单信息
     */
    public function giveOrderInfo()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['order_sn'], 'require');
        UserAuthority::checkAuthority(8);

        $order_sn = request()->param('order_sn');

        $result = GlOrder::where([
            ['order_sn', '=', $order_sn],
            ['is_del', '=', 0]
        ])
            ->find();


        return $result;
    }

    /**
     * @return mixed
     * @throws \app\lib\exception\CommonException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 支付查询
     */
    public function OrderPaymentQuery()
    {

        //验证必要
        (new CurrencyValidate())->myGoCheck(['order_sn'], 'require');
        UserAuthority::checkAuthority(8);

        $order_sn = request()->param('order_sn');

        $PayClass = new Payment();
        $PayClass->orderSn = $order_sn;

        return $PayClass->orderPayQuery();

    }

    /**
     * @return bool
     * @throws CommonException
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 取消单笔订单
     */
    public function callOrderByOrderSn()
    {

        //验证必要
        (new CurrencyValidate())->myGoCheck(['order_sn'], 'require');
        UserAuthority::checkAuthority(8);

        $order_sn = request()->param('order_sn');

        //只有待支付订单可以取消
        $upd_number = GlOrder::where([
            ['order_sn', '=', $order_sn],
            ['order_state', '=', 1],
            ['is_del', '=', 0]
        ])
            ->update([
                'order_state' => 0,
                'upd_time' => time(),
                'prev_order_state' => 1
            ]);

        if ($upd_number < 1) {
            throw new CommonException(['msg' => '修改失败']);
        }

        return true;

    }

    /**
     * @return bool
     * @throws CommonException
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 删除单笔订单
     */
    public function delOrderByOrderSn()
    {

        //验证必要
        (new CurrencyValidate())->myGoCheck(['order_sn'], 'require');
        UserAuthority::checkAuthority(8);

        $order_sn = request()->param('order_sn');

        //只有取消订单可以删除
        $upd_number = GlOrder::where([
            ['order_sn', '=', $order_sn],
            ['order_state', '=', 0],
            ['is_del', '=', 0]
        ])
            ->update([
                'upd_time' => time(),
                'is_del' => 1
            ]);

        if ($upd_number < 1) {
            throw new CommonException(['msg' => '修改失败']);
        }

        return true;

    }

    /**
     * @return bool
     * @throws CommonException
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 编辑物流
     */
    public function updOrderLogisticsInfoByOrderSn()
    {

        //验证必要
        (new CurrencyValidate())->myGoCheck(['order_sn', 'logistics_code', 'logistics_name', 'logistics_tel', 'logistics_address'], 'require');
        UserAuthority::checkAuthority(8);

        $order_sn = request()->param('order_sn');
        $data['logistics_code'] = request()->param('logistics_code');
        $data['logistics_name'] = request()->param('logistics_name');
        $data['logistics_tel'] = request()->param('logistics_tel');
        $data['logistics_address'] = request()->param('logistics_address');
        $data['logistics_sn'] = request()->param('logistics_sn');

        GlOrder::where([
            ['order_sn', '=', $order_sn]
        ])
            ->update($data);


        return true;


    }


    /**
     * @return bool
     * @throws CommonException
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 订单发货
     */
    public function deliveryByOrderSn()
    {

        //验证必要
        (new CurrencyValidate())->myGoCheck(['order_sn', 'logistics_code', 'logistics_name', 'logistics_tel', 'logistics_address', 'logistics_sn'], 'require');
        UserAuthority::checkAuthority(8);

        $order_sn = request()->param('order_sn');
        $data['logistics_code'] = request()->param('logistics_code');
        $data['logistics_name'] = request()->param('logistics_name');
        $data['logistics_tel'] = request()->param('logistics_tel');
        $data['logistics_address'] = request()->param('logistics_address');
        $data['logistics_sn'] = request()->param('logistics_sn');

        $data['order_state'] = 3;
        $data['prev_order_state'] = 2;
        $data['deliver_goods_time'] = time();
        $data['upd_time'] = time();
        $data['invalid_sign_goods_time'] = time() + config('my_config.invalid_sign_goods_time');

        $upd_number = GlOrder::where([
            ['order_sn', '=', $order_sn],
            ['order_state', '=', 2],
            ['is_del', '=', 0]
        ])
            ->update($data);

        if ($upd_number < 1) {
            throw new CommonException(['msg' => '发货失败']);
        }

        return true;


    }
}