<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/6/3
 * Time: 9:31
 */

namespace app\api\service\OrderPayment;


use app\api\model\GlByStages;
use app\api\model\GlGoods;
use app\api\model\GlGoodsSku;
use app\api\model\GlMidOrder;
use app\api\model\GlOrder;
use app\api\model\GlPayType;
use app\api\model\GlUser;
use app\api\service\Login\BaseLogin;
use app\api\service\SerEmail;
use app\lib\exception\CommonException;
use think\Db;
use think\facade\Log;

class Payment
{
    public $orderSn;
    public $userToken;
    protected $orderInfo;
    protected $midOrderInfo;
    protected $userInfo;
    protected $intoType;
    protected $sonIntoType;
    protected $payInfo;
    protected $byStagesInfo;//分期方式信息
    public $successUrl;
    public $backUrl;
    public $payTime;

    public function __construct()
    {

    }


    /**
     * @return \think\response\View
     * @throws CommonException
     * @throws \WxPayException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 发起支付
     */
    public function orderPayment()
    {
        $user_desc = BaseLogin::getCurrentIdentity(['user_id', 'into_type', 'son_into_type'], $this->userToken);
        $user_id = $user_desc['user_id'];
        $this->intoType = $user_desc['into_type'];
        $this->sonIntoType = $user_desc['son_into_type'];
        $this->userInfo = GlUser::where([
            ['user_id', '=', $user_id]
            , ['is_del', '=', 0]])
            ->find();
        if (!$this->userInfo) {
            throw new CommonException(['msg' => '非正常用户状态']);
        }

        $this->orderInfo = GlOrder::where([
            ['order_sn', '=', $this->orderSn],
            ['user_id', '=', $user_id],
            ['order_state', '=', 1],
            ['is_del', '=', 0],
            ['invalid_pay_time', '>', time()]
        ])
            ->find();

        if (!$this->orderInfo) {
            throw new CommonException(['msg' => '无效订单']);
        }

        $this->midOrderInfo = GlMidOrder::where([
            ['order_sn', '=', $this->orderSn]
        ])
            ->select();

        if (count($this->midOrderInfo) < 1) {
            throw new CommonException(['msg' => '无效订单,未获取到有效子订单']);
        }

        /*检查商品*/
        foreach ($this->midOrderInfo as $k => $v) {

            $goods_info = GlGoods::where([
                ['goods_id', '=', $v['goods_id']],
                ['is_del', '=', 0],
                ['is_on_sale', '=', 1]
            ])
                ->find();
            if (!$goods_info) {
                throw new CommonException(['msg' => '无效商品，或已下架']);
            }

            $sku_info = GlGoodsSku::where([
                ['sku_id', '=', $v['sku_id']],
                ['sku_stock', '>=', $v['goods_number']],
            ])
                ->find();

            if (!$sku_info) {
                throw new CommonException(['msg' => '无效商品，或库存不足']);
            }


        }

        /*获取支付方式*/
        $this->payInfo = GlPayType::where([
            ['pay_id', '=', $this->orderInfo['pay_id']],
            ['is_del', '=', 0]
        ])
            ->find();

        if (!$this->payInfo) {
            throw new CommonException(['无效支付方式,该支付方式或已关闭']);
        }

        /*分期方式信息*/
        $this->byStagesInfo = GlByStages::where([
            ['bystages_id', '=', $this->orderInfo['bystages_id']],
            ['is_del', '=', 0]
        ])
            ->find();

        if (!$this->byStagesInfo) {
            throw new CommonException(['无效分期方式,该分期方式或已关闭']);
        }

        /*发起支付*/
        switch ($this->payInfo['pay_code']) {
            case 'AbcPayment':
                $PayClass = new AbcPayment();
                break;
            case 'AbcEPayment':
                $PayClass = new AbcEPayment();
                break;
            case 'PcAbcPayment':
                $PayClass = new PcAbcPayment();
                break;
            case 'WxJsApiPayment':
                $PayClass = new WxJsApiPayment();
                break;
            case 'WxAppApiPayment':
                $PayClass = new WxAppApiPayment();
                break;
            case 'PcAliPayment':
                $PayClass = new PcAliPayment();
                break;
            case 'AppAliPayment':
                $PayClass = new AppAliPayment();
                break;
            case 'TestPayment':
                $PayClass = new TestPayment();
                break;
            default:
                throw new CommonException(['无效支付方式,该支付方式或已关闭']);
        }
        return $PayClass->startPayment($this->orderInfo, $this->midOrderInfo, $this->successUrl, $this->backUrl);

    }


    /**
     * @return mixed
     * @throws CommonException
     * @throws \WxPayException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 订单查询
     */
    public function orderPayQuery()
    {
        $this->orderInfo = GlOrder::where([
            ['order_sn', '=', $this->orderSn]
        ])
            ->find();

        if (!$this->orderInfo) {
            throw new CommonException(['msg' => '无此订单']);
        }
        //发起查询
        switch ($this->orderInfo['pay_code']) {
            case 'AbcPayment':
                $PayClass = new AbcPayment();
                break;
            case 'AbcEPayment':
                $PayClass = new AbcPayment();
                break;
            case 'PcAbcPayment':
                $PayClass = new AbcPayment();
                break;
            case 'WxJsApiPayment':
                $PayClass = new WxJsApiPayment();
                break;
            case 'WxAppApiPayment':
                $PayClass = new WxAppApiPayment();
                break;
            case 'PcAliPayment':
                $PayClass = new PcAliPayment();
                break;
            case 'AppAliPayment':
                $PayClass = new PcAliPayment();
                break;
            default:
                throw new CommonException(["msg" => "该支付方式支付查询功能暂未开放"]);

        }
        return $PayClass->startQuery($this->orderInfo);

    }

    /**
     * @return bool
     * @throws CommonException
     * @throws \WxPayException
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * 订单退款
     */
    public function orderPayRefund()
    {

        $this->orderInfo = GlOrder::where([
            ['order_sn', '=', $this->orderSn]
        ])
            ->find();

        if (!$this->orderInfo) {
            throw new CommonException(['msg' => '无此订单']);
        }

        //发起退款
        switch ($this->orderInfo['pay_code']) {
            case 'AbcPayment':
                $PayClass = new AbcPayment();
                break;
            case 'AbcEPayment':
                $PayClass = new AbcPayment();
                break;
            case 'PcAbcPayment':
                $PayClass = new AbcPayment();
                break;
            case 'WxJsApiPayment':
                $PayClass = new WxJsApiPayment();
                break;
            case 'WxAppApiPayment':
                $PayClass = new WxAppApiPayment();
                break;
            case 'PcAliPayment':
                $PayClass = new PcAliPayment();
                break;
            case 'AppAliPayment':
                $PayClass = new PcAliPayment();
                break;
            default:
                throw new CommonException(["msg" => "该支付方式支付退款功能暂未开放"]);
        }

        return $PayClass->startRefund($this->orderInfo);

    }


    /**
     * @param $third_party_sn_array
     * @throws CommonException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 支付回调成功处理
     */
    public function OrderPaySuccess($third_party_sn_array)
    {

        $this->orderInfo = GlOrder::where([
            ['order_sn', '=', $this->orderSn],
            ['order_state', '=', 1]
        ])->find();

        if ($this->orderInfo) {
            $head = '新订单提醒，(入口：' . $this->orderInfo['son_into_type'] . ')';
            $email_body = '用户支付成功';
            $this->midOrderInfo = GlMidOrder::where([
                ['order_sn', '=', $this->orderSn]
            ])->select();

            Db::transaction(function () use (&$email_body, $third_party_sn_array) {
                /*循环遍历中间表，处理库存*/
                foreach ($this->midOrderInfo as $k => $v) {
                    $sku_info = GlGoodsSku::where([
                        ['sku_id', '=', $v['sku_id']],
                    ])->find();
                    if ($sku_info['sku_stock'] >= $v['goods_number']) {
                        /*库存满足*/
                        $email_body .= '
                        (商品名称:' . $v['goods_name'] .
                            ',商品id:' . $v['goods_id'] .
                            ',购买数量:' . $v['goods_number'] .
                            ',SkuId:' . $v['sku_id'] .
                            ',属性详情:' . $v['sku_desc'] .
                            ',剩余库存:' . ($sku_info['sku_stock'] - $v['goods_number']) .
                            ',库存检测结果:库存充足)';
                        /*减去对应库存*/
                        GlGoodsSku::where(['sku_id' => $v['sku_id']])->setDec('sku_stock', ($v['goods_number'] + 0));
                        GlGoods::where(['goods_id' => $v['goods_id']])->setDec('goods_stock', ($v['goods_number'] + 0));
                    } else {
                        /*库存不满足*/
                        $email_body .= '
                        (商品名称:' . $v['goods_name'] .
                            ',商品id:' . $v['goods_id'] .
                            ',购买数量:' . $v['goods_number'] .
                            ',SkuId:' . $v['sku_id'] .
                            ',属性详情:' . $v['sku_desc'] .
                            ',剩余库存:' . 0 .
                            ',库存检测结果:库存量不足，已将库存量清零)';

                        /*清空sku库存*/
                        GlGoodsSku::where(['sku_id' => $v['sku_id']])
                            ->update(['sku_stock' => 0]);

                        /*减去或者情况商品表库存*/
                        $upd_number = GlGoods::where([
                            ['goods_id', '=', $v['goods_id']],
                            ['goods_stock', '>=', $v['goods_number']],
                        ])
                            ->setDec('goods_stock', ($v['goods_number'] + 0));

                        if ($upd_number < 1) {
                            /*说明商品表库存不足*/
                            GlGoods::where([
                                ['goods_id', '=', $v['goods_id']],
                            ])
                                ->update(['goods_stock' => 0]);
                        }
                    }

                    /*增加销量*/
                    GlGoods::where([
                        ['goods_id', '=', $v['goods_id']]
                    ])
                        ->setInc('goods_sales_volume', ($v['goods_number'] + 0));
                }

                /*改变订单表*/
                $update = [
                    'pay_time' => time(),
                    'upd_time' => time(),
                    'order_state' => 2
                ];
                //获取第三方订单号
                if ($third_party_sn_array) {
                    foreach ($third_party_sn_array as $k => $v) {
                        $update[$k] = $v;
                    }
                }
                GlOrder::where([
                    ['order_sn', '=', $this->orderSn]
                ])
                    ->update($update);
            });

            /*发送邮件*/
            //测试用

            if (!config('my_config.debug')) {
                //正式用
                $address_array = config('my_config.payment_recipient_email_address');
            } else {
                //测试用
                $address_array = ['987303897@qq.com'];
            }
            (new SerEmail())->sendEmail($head, $email_body, $address_array);

        } else {
            Log::write("支付回调，检测订单状态时不通过,OrderSn:" . $this->orderSn, 'error');
            throw new CommonException(['msg' => '订单状态不合法']);
        }
    }

    /**
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 退款成功处理
     */
    public function OrderRefundSuccess()
    {
        GlOrder::where([
            ['order_sn', '=', $this->orderSn]
        ])
            ->update([
                'refund_time' => time(),
            ]);

        $head = '订单退款成功提醒';
        $body = '订单退款成功,订单号：' . $this->orderSn;
        if (!config('my_config.debug')) {
            //正式用
            $address_array = config('my_config.refund_recipient_email_address');
        } else {
            //测试用
            $address_array = ['987303897@qq.com'];
        }
        (new SerEmail())->sendEmail($head, $body, $address_array);
    }

    /**
     * @param $order_sn
     * @return string
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 生成退款单号
     */
    public static function createRefundSn($order_sn)
    {

        $refund_order_sn = "T" . $order_sn . getRandCharDNoNumber(6);

        GlOrder::where([
            ['order_sn', '=', $order_sn]
        ])
            ->update([
                'refund_order_sn' => $refund_order_sn
            ]);

        return $refund_order_sn;

    }


}