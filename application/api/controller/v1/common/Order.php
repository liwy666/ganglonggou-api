<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/5/28
 * Time: 9:44
 */

namespace app\api\controller\v1\common;


use app\api\model\GlMidOrder;
use app\api\model\GlOrder;
use app\api\model\GlOrderInvoice;
use app\api\service\Login\BaseLogin;
use app\api\service\Order\SerOrder;
use app\api\service\OrderPayment\Payment;
use app\api\validate\CurrencyValidate;
use app\lib\exception\CommonException;

class Order
{
    /**
     * @return mixed
     * @throws \app\lib\exception\CommonException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 提交订单
     */
    public function submitOrder()
    {

        /*检查提交选项*/
        //验证必要
        (new CurrencyValidate())->myGoCheck(['user_token', 'coupon_id', 'goods_list', 'invoice_info', 'pay_id', 'bystages_id', 'use_integral_number'], 'require');
        //验证正整数
        (new CurrencyValidate())->myGoCheck(['pay_id', 'bystages_id'], 'positiveInt');

        return (new SerOrder())->createOrder();

    }

    /**
     * @return array|\PDOStatement|string|\think\Model|null
     * @throws CommonException
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 返回单笔订单
     */
    public function giveOrderInfo()
    {

        //验证必要
        (new CurrencyValidate())->myGoCheck(['user_token', 'order_sn'], 'require');

        //获取用户信息
        $user_token = request()->param("user_token");
        $user_desc = BaseLogin::getCurrentIdentity(['user_id', 'into_type', 'son_into_type'], $user_token);
        $user_id = $user_desc['user_id'];
        $order_sn = request()->param('order_sn');

        /*将支付超时订单改为取消*/
        GlOrder::where([
            ['user_id', '=', $user_id],
            ['is_del', '=', 0],
            ['order_state', '=', 1],
            ['invalid_pay_time', '<', time()]
        ])
            ->update([
                'upd_time' => time(),
                'order_state' => 0,
                'prev_order_state' => 1,
                'order_visible_note' => '超出支付时间，订单自动取消'
            ]);

        /*获取订单信息*/
        $order_info = GlOrder::getScreenOrderInfoByOrderSnAndUserId($order_sn, $user_id);

        if (!$order_info) {
            throw new CommonException(['msg' => '无效订单']);
        }

        /*订单中间表*/
        $order_info['mid_order'] = GlMidOrder::getScreenMidOrderInfoByOrderSn($order_sn);

        /*发票信息*/
        $order_info['invoice'] = GlOrderInvoice::getScreenInvoiceInfoByOrderSn($order_sn);

        return $order_info;


    }

    /**
     * @return array|\PDOStatement|string|\think\Collection
     * @throws CommonException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 返回用户所有订单
     */
    public function giveAllOrderByUserId()
    {

        //验证必要
        (new CurrencyValidate())->myGoCheck(['user_token'], 'require');

        //获取用户信息
        $user_token = request()->param("user_token");
        $user_desc = BaseLogin::getCurrentIdentity(['user_id', 'into_type', 'son_into_type'], $user_token);
        $user_id = $user_desc['user_id'];

        $order_list = GlOrder::where([
            ['user_id', '=', $user_id],
            ['is_del', '=', 0]
        ])
            ->field('order_sn,order_state,upd_time')
            ->select();

        if (count($order_list) > 0) {
            foreach ($order_list as $k => $v) {
                $order_list[$k]['order_state_name'] = config('my_config.order_state_name')[$v['order_state']];
                $order_list[$k]['mid_order'] = GlMidOrder::where([
                    ['order_sn', '=', $v['order_sn']]
                ])
                    ->field('goods_name,sku_desc,goods_number,give_integral,is_evaluate,img_url,mid_order_price,id')
                    ->select();
            }
        }

        return $order_list;
    }

    /**
     * @return bool
     * @throws CommonException
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 订单签收
     */
    public function takeOrderByOrderSn()
    {

        //验证必要
        (new CurrencyValidate())->myGoCheck(['user_token', 'order_sn'], 'require');

        $order_sn = request()->param("order_sn");
        //获取用户信息
        $user_token = request()->param("user_token");
        $user_desc = BaseLogin::getCurrentIdentity(['user_id', 'into_type', 'son_into_type'], $user_token);
        $user_id = $user_desc['user_id'];

        $upd_number = GlOrder::where([
            ['order_sn', '=', $order_sn],
            ['user_id', '=', $user_id],
            ['order_state', '=', 3],
            ['is_del', '=', 0]
        ])->update([
            'order_state' => 4,
            'upd_time' => time(),
            'sign_goods_time' => time(),
            'prev_order_state' => 3,
        ]);

        if ($upd_number < 1) {
            throw new CommonException(['msg' => '签收失败']);
        }

        return true;
    }

    /**
     * @return bool
     * @throws CommonException
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 删除订单
     */
    public function delOrderByOrderSn()
    {

        //验证必要
        (new CurrencyValidate())->myGoCheck(['user_token', 'order_sn'], 'require');

        $order_sn = request()->param("order_sn");
        //获取用户信息
        $user_token = request()->param("user_token");
        $user_desc = BaseLogin::getCurrentIdentity(['user_id', 'into_type', 'son_into_type'], $user_token);
        $user_id = $user_desc['user_id'];

        $upd_number = GlOrder::where([
            ['order_sn', '=', $order_sn],
            ['user_id', '=', $user_id],
            ['order_state', '=', 0],
            ['is_del', '=', 0]
        ])->update([
            'is_del' => 1,
            'upd_time' => time(),
        ]);

        if ($upd_number < 1) {
            throw new CommonException(['msg' => '删除失败']);
        }

        return true;
    }

    /**
     * @return bool
     * @throws CommonException
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 取消订单
     */
    public function callOrderByOrderSn()
    {

        //验证必要
        (new CurrencyValidate())->myGoCheck(['user_token', 'order_sn'], 'require');

        $order_sn = request()->param("order_sn");
        //获取用户信息
        $user_token = request()->param("user_token");
        $user_desc = BaseLogin::getCurrentIdentity(['user_id', 'into_type', 'son_into_type'], $user_token);
        $user_id = $user_desc['user_id'];

        $upd_number = GlOrder::where([
            ['order_sn', '=', $order_sn],
            ['user_id', '=', $user_id],
            ['order_state', '=', 1],
            ['is_del', '=', 0]
        ])->update([
            'order_state' => 0,
            'upd_time' => time(),
            'prev_order_state' => 1,
        ]);

        if ($upd_number < 1) {
            throw new CommonException(['msg' => '取消失败']);
        }

        return true;
    }

    /**
     * @return mixed
     * @throws CommonException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 支付查询
     */
    public function queryOrderPayment()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['order_sn'], 'require');

        $order_sn = request()->param('order_sn');

        $PayClass = new Payment();
        $PayClass->orderSn = $order_sn;

        return $PayClass->orderPayQuery();

    }
}