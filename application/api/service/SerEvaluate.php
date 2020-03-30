<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/6/6
 * Time: 12:57
 */

namespace app\api\service;


use app\api\model\GlGoodsEvaluate;
use app\api\model\GlMidOrder;
use app\api\model\GlOrder;
use app\api\model\GlUser;
use app\lib\exception\CommonException;
use think\Db;

class SerEvaluate
{

    public $evaluateText;
    public $userId;
    public $midOrderId;
    public $rate;
    private $orderInfo;
    private $midOrderInfo;
    private $userInfo;

    /**
     * @return bool
     * @throws CommonException
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 用户提交评价
     */
    public function userInsEvaluate()
    {
        $this->midOrderInfo = GlMidOrder::where([
            ['id', '=', $this->midOrderId],
            ['is_evaluate', '=', 0]
        ])
            ->find();

        if (!$this->midOrderInfo) {
            throw new CommonException(['msg' => '无效子订单', 'error_code' => 30002]);
        }

        $this->orderInfo = GlOrder::where([
            ['order_sn', '=', $this->midOrderInfo['order_sn']],
            ['order_state', '=', 4],
            ['user_id', '=', $this->userId],
            ['is_del', '=', 0]
        ])
            ->find();
        if (!$this->orderInfo) {
            throw new CommonException(['msg' => '无效订单', 'error_code' => 30001]);
        }


        $this->userInfo = GlUser::where([
            ['user_id', '=', $this->userId],
            ['is_del', '=', 0]
        ])
            ->find();

        if (!$this->userInfo) {
            throw new CommonException(['msg' => '非合法用户', 'error_code' => 10004]);
        }

        Db::transaction(function () {
            /*插入评价表*/
            GlGoodsEvaluate::create([
                'create_time' => time(),
                'parent_id' => 0,
                'is_del' => 0,
                'is_allow' => 0,
                'goods_id' => $this->midOrderInfo['goods_id'],
                'sku_id' => $this->midOrderInfo['sku_id'],
                'user_id' => $this->userInfo['user_id'],
                'user_name' => $this->userInfo['user_name'],
                'user_img' => removeImgUrl($this->userInfo['user_img']),
                'goods_name' => $this->midOrderInfo['goods_name'],
                'sku_desc' => $this->midOrderInfo['sku_desc'],
                'rate' => $this->rate,
                'evaluate_text' => $this->evaluateText,
            ]);

            /*改为已评价*/
            GlMidOrder::where([
                ['id', '=', $this->midOrderId]
            ])
                ->update([
                    'is_evaluate' => 1
                ]);

            /*赠送积分*/
            if ($this->midOrderInfo['give_integral'] > 0) {
                GlUser::where([
                    ['user_id', '=', $this->userId]
                ])
                    ->setInc('integral', ($this->midOrderInfo['give_integral'] + 0));
            }
        });
        return true;
    }
}