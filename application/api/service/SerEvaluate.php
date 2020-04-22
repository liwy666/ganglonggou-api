<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/6/6
 * Time: 12:57
 */

namespace app\api\service;


use app\api\model\GlGoods;
use app\api\model\GlGoodsEvaluate;
use app\api\model\GlMidOrder;
use app\api\model\GlOrder;
use app\api\model\GlUser;
use app\lib\exception\CommonException;
use think\Db;

class SerEvaluate
{

//    public $evaluateText;
//    public $userId;
//    public $midOrderId;
//    public $rate;
//    public $isAllow = 0;
//    public $goodsId = 0;
//    public $createTime;
//    private $orderInfo;
//    private $midOrderInfo;
//    private $userInfo;

    /**
     * @param string $evaluateText
     * @param int $userId
     * @param int $midOrderId
     * @param int $rate
     * @param int $isAllow
     * @param int $goodsId
     * @param int $createTime
     * @return bool
     * @throws CommonException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 用户提交评价
     */
    public function userInsEvaluate($evaluateText, $userId, $midOrderId, $rate, $isAllow, $goodsId, $createTime)
    {
        $midOrderInfo = GlMidOrder::where([
            ['id', '=', $midOrderId],
            ['is_evaluate', '=', 0]
        ])
            ->find();

        if (!$midOrderInfo) {
            throw new CommonException(['msg' => '提交评价失败，无效子订单', 'error_code' => 30002]);
        }

        $orderInfo = GlOrder::where([
            ['order_sn', '=', $midOrderInfo['order_sn']],
            ['order_state', '=', 4],
            ['user_id', '=', $userId],
            ['is_del', '=', 0]
        ])
            ->find();
        if (!$orderInfo) {
            throw new CommonException(['msg' => '提交评价失败，无效订单', 'error_code' => 30001]);
        }


        $userInfo = GlUser::where([
            ['user_id', '=', $userId],
            ['is_del', '=', 0]
        ])
            ->find();

        if (!$userInfo) {
            throw new CommonException(['msg' => '提交评价失败，非合法用户', 'error_code' => 10004]);
        }

        Db::transaction(function () use ($midOrderInfo, $userInfo, $midOrderId, $evaluateText, $rate, $createTime, $isAllow, $userId, $goodsId) {
            /*插入评价表*/
            GlGoodsEvaluate::create([
                'create_time' => $createTime,
                'parent_id' => 0,
                'is_del' => 0,
                'is_allow' => $isAllow,
                'goods_id' => $midOrderInfo['goods_id'],
                'sku_id' => $midOrderInfo['sku_id'],
                'user_id' => $userInfo['user_id'],
                'user_name' => $userInfo['user_name'],
                'user_img' => removeImgUrl($userInfo['user_img']),
                'goods_name' => $midOrderInfo['goods_name'],
                'sku_desc' => $midOrderInfo['sku_desc'],
                'rate' => $rate,
                'evaluate_text' => $evaluateText,
            ]);

            /*改为已评价*/
            GlMidOrder::where([
                ['id', '=', $midOrderId]
            ])
                ->update([
                    'is_evaluate' => 1
                ]);

            /*赠送积分*/
            if ($midOrderInfo['give_integral'] > 0) {
                GlUser::where([
                    ['user_id', '=', $userId]
                ])
                    ->setInc('integral', ($midOrderInfo['give_integral'] + 0));
            }
            /*如果默认允许状态，商品评价数加一*/
            if ($goodsId > 0 && $isAllow === 1) {
                GlGoods::where([
                    ['goods_id', '=', $goodsId]
                ])
                    ->setInc('evaluate_count');
            }
        });
        return true;
    }
}