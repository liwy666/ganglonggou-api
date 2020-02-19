<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/6/10
 * Time: 9:18
 */

namespace app\api\controller\v1\common;


use app\api\model\GlAfterSale;
use app\api\model\GlOrder;
use app\api\service\Login\BaseLogin;
use app\api\service\SerAfterSale;
use app\api\validate\CurrencyValidate;
use app\lib\exception\CommonException;
use think\Db;

class AfterSale
{
    /**
     * @return bool
     * @throws \app\lib\exception\CommonException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 提交售后
     */
    public function submitAfterSale()
    {

        (new CurrencyValidate())->myGoCheck(['after_sale_type', 'after_sale_cause', 'order_sn', 'user_token'], 'require');

        //获取用户信息
        $user_token = request()->param("user_token");
        $user_desc = BaseLogin::getCurrentIdentity(['user_id', 'into_type', 'son_into_type'], $user_token);
        $user_id = $user_desc['user_id'];

        $after_sale_text = request()->param('after_sale_text');
        $order_sn = request()->param('order_sn');
        $after_sale_cause = request()->param('after_sale_cause');
        $after_sale_type = request()->param('after_sale_type');

        //检查长度
        if (mb_strlen($after_sale_text) > 300) {
            throw new CommonException(['msg' => '售后说明超出300字']);
        }

        $AfterSaleClass = new SerAfterSale();
        $AfterSaleClass->userId = $user_id;
        $AfterSaleClass->orderSn = $order_sn;
        $AfterSaleClass->afterSaleText = $after_sale_text;
        $AfterSaleClass->afterSaleType = $after_sale_type;
        $AfterSaleClass->afterSaleCause = $after_sale_cause;

        return $AfterSaleClass->userSubmitAfterSale();


    }

    /**
     * @return bool
     * @throws CommonException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 取消售后
     */
    public function callAfterSale()
    {
        (new CurrencyValidate())->myGoCheck(['order_sn', 'user_token'], 'require');

        //获取用户信息
        $user_token = request()->param("user_token");
        $user_desc = BaseLogin::getCurrentIdentity(['user_id', 'into_type', 'son_into_type'], $user_token);
        $user_id = $user_desc['user_id'];
        $order_sn = request()->param('order_sn');

        $order_info = Glorder::where([
            ['user_id', '=', $user_id],
            ['order_sn', '=', $order_sn],
            ['order_state', '=', 6],
            ['is_del', '=', 0]
        ])->find();

        if (!$order_info) {

            throw new CommonException(['msg' => '非有效订单']);

        }

        Db::transaction(function () use ($user_id,$order_sn,$order_info){
            /*改变订单表*/
            GlOrder::where([
                ['order_sn', '=', $order_sn],
            ])
                ->update([
                    'order_state' => $order_info['prev_order_state'],
                    'upd_time' => time(),
                ]);
            /*删除售后表*/
            GlAfterSale::where([
                ['order_sn', '=', $order_sn]
            ])
                ->update([
                    'is_del' => 1
                ]);
        });


        return true;
    }
}