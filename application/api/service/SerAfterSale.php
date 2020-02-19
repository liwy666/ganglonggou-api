<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/6/10
 * Time: 9:53
 */

namespace app\api\service;


use app\api\model\GlAfterSale;
use app\api\model\GlOrder;
use app\api\service\OrderPayment\Payment;
use app\lib\exception\CommonException;
use think\Db;

class SerAfterSale
{
    public $orderSn;
    public $userId;
    public $afterSaleType;
    public $afterSaleCause;
    public $afterSaleText;
    public $afterSaleId;
    private $orderInfo;
    private $afterSaleInfo;

    /**
     * @return bool
     * @throws CommonException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 用户提交售后
     */
    public function userSubmitAfterSale()
    {

        $this->orderInfo = GlOrder::where([
            ['order_sn', '=', $this->orderSn],
            ['user_id', '=', $this->userId],
            ['is_del', '=', 0]
        ])
            ->find();

        if (!$this->orderInfo) {
            throw new CommonException(['msg' => '无效订单']);
        }

        if ($this->orderInfo['order_state'] !== 2 && $this->orderInfo['order_state'] !== 4) {
            throw new CommonException(['msg' => '订单状态不满足']);
        }

        Db::transaction(function () {
            /*增加售后表*/
            GlAfterSale::create([
                'user_id' => $this->userId,
                'order_sn' => $this->orderSn,
                'after_sale_type' => $this->afterSaleType,
                'after_sale_cause' => $this->afterSaleCause,
                'create_time' => time(),
                'is_allow' => 0,
                'is_del' => 0,
                'after_sale_text' => $this->afterSaleText,
            ]);

            /*改变订单状态*/
            GlOrder::where([
                ['order_sn', '=', $this->orderSn]
            ])->update([
                'upd_time' => time(),
                'prev_order_state' => $this->orderInfo['order_state'],
                'order_state' => 6
            ]);
        });

        return true;
    }

    /**
     * @throws CommonException
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * 管理员处理退款
     */
    public function cmsOrderRefund()
    {
        $this->orderInfo = GlOrder::where([
            ['order_sn', '=', $this->orderSn],
            ['order_state', '=', 6],
            ['is_del', '=', 0]
        ])
            ->find();

        if (!$this->orderInfo) {
            throw new CommonException(['msg' => '订单状态不满足']);
        }

        $this->afterSaleInfo = GlAfterSale::where([
            ['id', '=', $this->afterSaleId],
            ['is_del', '=', 0],
            ['is_allow', '=', 0],
            ['order_sn', '=', $this->orderSn]
        ])
            ->find();

        if (!$this->orderInfo) {
            throw new CommonException(['msg' => '售后状态不满足']);
        }

        /*执行退款*/
        $PayClass = new Payment();
        $PayClass->orderSn = $this->orderSn;
        $refund_result = $PayClass->orderPayRefund();

        if ($refund_result) {

            Db::transaction(function () {
                /*处理订单表*/
                GlOrder::where([
                    ['order_sn', '=', $this->orderSn]
                ])
                    ->update([
                        'upd_time' => time(),
                        'order_state' => 8,
                    ]);
                /*处理售后表*/
                GlAfterSale::where([
                    ['id', '=', $this->afterSaleId]
                ])->update([
                    'allow_time'=> time(),
                    'is_allow'=> 1
                ]);
            });

            return true;

        } else {
            throw new CommonException(['msg' => '退款失败']);
        }


    }
}