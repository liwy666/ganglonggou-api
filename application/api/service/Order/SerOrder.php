<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/5/28
 * Time: 13:01
 */

namespace app\api\service\Order;


use app\api\model\GlAddress;
use app\api\model\GlByStages;
use app\api\model\GlCoupon;
use app\api\model\GlGoods;
use app\api\model\GlGoodsSku;
use app\api\model\GlMidOrder;
use app\api\model\GlMidUserCoupon;
use app\api\model\GlOrder;
use app\api\model\GlOrderInvoice;
use app\api\model\GlPayType;
use app\api\model\GlUser;
use app\api\service\Login\BaseLogin;
use app\lib\exception\CommonException;
use think\Db;

class SerOrder
{

    protected $userInfo;//用户信息
    protected $intoType;//入口来源
    protected $sonIntoType;//子入口来源
    protected $couponInfo;//优惠券信息
    protected $submitGoodsArray;//用户提交商品数组
    protected $payTypeInfo;//支付方式信息
    protected $byStagesInfo;//分期方式信息
    protected $goodsInfoArray;//商品信息数组
    protected $skuInfoArray;//sku信息数组
    protected $allowIntegralNumber;//允许使用积分的数量
    protected $useIntegralNumber;//使用积分的数量
    protected $giveIntegral;//赠予积分的数量
    protected $originalOrderPrice;//订单原始价格
    protected $afterUsingCouponPrice;//使用优惠券后价格
    protected $afterUsingIntegralPrice;//使用积分后价格
    protected $afterUsingPayPrice;//支付方式打折之后价格
    protected $orderPrice;//订单最终价格
    protected $orderSn;//订单编号
    protected $addressInfo;//地址信息
    protected $invoiceInfo;//发票信息

    /**
     * @return mixed
     * @throws CommonException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 生成订单
     */
    public function createOrder()
    {
        $user_token = request()->param("user_token");
        $user_desc = BaseLogin::getCurrentIdentity(['user_id', 'into_type', 'son_into_type'], $user_token);
        $user_id = $user_desc['user_id'];
        $this->intoType = $user_desc['into_type'];
        $this->sonIntoType = $user_desc['son_into_type'];
        $coupon_id = request()->param('coupon_id');
        $bystages_id = request()->param('bystages_id');
        $pay_id = request()->param('pay_id');
        $this->useIntegralNumber = request()->param('use_integral_number');

        /*检查积分使用数量*/
        if ($this->useIntegralNumber < 0) {

            throw new CommonException(['msg' => '无效积分']);
        }

        /*获取用户提交商品数组*/
        $this->submitGoodsArray = request()->param('goods_list/a');

        /*获取用户信息*/
        $this->userInfo = GlUser::where([
            ['user_id', '=', $user_id]
            , ['is_del', '=', 0]])
            ->find();
        if (!$this->userInfo) {
            throw new CommonException(['非正常用户状态']);
        }

        /*地址信息*/
        $this->addressInfo = GlAddress::where([
            ['user_id', '=', $user_id],
            ['is_del', '=', 0],
            ['is_default', '=', 1]
        ])
            ->find();

        if (!$this->addressInfo) {
            throw new CommonException(['msg' => '无效收件地址']);
        }

        /*发票信息*/
        $invoiceInfo = request()->param('invoice_info/a');
        $this->invoiceInfo['invoice_type'] = $invoiceInfo['invoice_type'] == '' ? null : $invoiceInfo['invoice_type'];
        $this->invoiceInfo['invoice_head'] = $invoiceInfo['invoice_head'] == '' ? null : $invoiceInfo['invoice_head'];
        $this->invoiceInfo['invoice_phone'] = $invoiceInfo['invoice_phone'] == '' ? null : $invoiceInfo['invoice_phone'];
        $this->invoiceInfo['invoice_qymc'] = $invoiceInfo['invoice_qymc'] == '' ? null : $invoiceInfo['invoice_qymc'];
        $this->invoiceInfo['invoice_nsrsbh'] = $invoiceInfo['invoice_nsrsbh'] == '' ? null : $invoiceInfo['invoice_nsrsbh'];
        $this->invoiceInfo['invoice_kpdz'] = $invoiceInfo['invoice_kpdz'] == '' ? null : $invoiceInfo['invoice_kpdz'];
        $this->invoiceInfo['invoice_zj'] = $invoiceInfo['invoice_zj'] == '' ? null : $invoiceInfo['invoice_zj'];
        $this->invoiceInfo['invoice_khh'] = $invoiceInfo['invoice_khh'] == '' ? null : $invoiceInfo['invoice_khh'];
        $this->invoiceInfo['invoice_yhzh'] = $invoiceInfo['invoice_yhzh'] == '' ? null : $invoiceInfo['invoice_yhzh'];


        /*获取优惠券信息*/
        if (($coupon_id + 0) > 0) {

            /*检查用户是否持有这张券*/
            $mid_user_coupon = GlMidUserCoupon::where([
                ['user_id', '=', $user_id],
                ['coupon_id', '=', $coupon_id],
                ['is_use', '=', 0]
            ])
                ->find();

            if (!$mid_user_coupon) {
                throw new CommonException(['msg'=>'无效优惠券']);
            }

            $this->couponInfo = GlCoupon::where([
                ['coupon_id', '=', $coupon_id]
                , ['is_del', '=', 0]
                , ['into_type', '=', $this->intoType]
                , ['start_use_time', '<', time()]
                , ['end_use_time', '>', time()]])
                ->find();
            if (!$this->couponInfo) {
                throw new CommonException(['无效优惠券']);
            }
        }

        /*获取支付方式信息*/
        $this->payTypeInfo = GlPayType::where([
            ['pay_id', '=', $pay_id]
            , ['into_type', '=', $this->intoType]
            , ['son_into_type', '=', $this->sonIntoType]
            , ['is_del', '=', 0]])
            ->find();
        if (!$this->payTypeInfo) {
            throw new CommonException(['msg' => '无效支付类型']);
        }

        /*获取分期方式信息*/
        $this->byStagesInfo = GlByStages::where([
            ['bystages_id', '=', $bystages_id],
            ['pay_code', '=', $this->payTypeInfo['pay_code']],
            ['is_del', '=', 0]])
            ->find();

        if (!$this->byStagesInfo) {
            throw new CommonException(['msg' => '无效分期方式']);
        }

        /*获取商品信息数组和sku信息数组*/
        foreach ($this->submitGoodsArray as $submitGoodsArray_k => $submitGoodsArray_v) {
            $goods_info = GlGoods::where([
                ['goods_id', '=', $submitGoodsArray_v['goods_id']],
                ['is_del', '=', 0],
                ['is_on_sale', '=', 1]
            ])
                ->find();
            if (!$goods_info) {
                throw new CommonException(['msg' => '包含无效商品']);
            } else {
                $this->goodsInfoArray[$submitGoodsArray_k] = $goods_info;
            }

            $sku_info = GlGoodsSku::where([
                ['sku_id', '=', $submitGoodsArray_v['sku_id']]
                , ['goods_id', '=', $submitGoodsArray_v['goods_id']]
                , ['sku_stock', '>=', $submitGoodsArray_v['goods_number']]
            ])
                ->find();

            if (!$sku_info) {
                throw new CommonException(['msg' => '包含无效sku']);
            } else {
                $this->skuInfoArray[$submitGoodsArray_k] = $sku_info;
            }

        }

        /*计算订单原始价格*/
        $this->countOriginalOrderPrice();
        /*计算订单使用优惠券后价格，并检查优惠券的合法性*/
        if ($this->couponInfo) {
            $this->countAfterUsingCouponPrice();
        } else {
            $this->afterUsingCouponPrice = $this->originalOrderPrice;
        }
        /*计算订单使用积分后的价格*/
        $this->countAfterUsingIntegralPrice();
        /*计算使用分期方式之后价格*/
        $this->countAfterUsingPayPrice();
        /*计算订单最终价格*/
        $this->countOrderPrice();
        /*生成订单编号*/
        $this->createOrderSn();

        /*保存数据库*/
        $this->orderInfoSaveDb();

        return $this->orderSn;
    }


    /**
     * 计算订单原始价格
     */
    private function countOriginalOrderPrice()
    {

        $price = 0;

        foreach ($this->submitGoodsArray as $submitGoodsArray_k => $submitGoodsArray_v) {
            $price += $submitGoodsArray_v['goods_number'] * $this->skuInfoArray[$submitGoodsArray_k]['sku_shop_price'];
        }

        $this->originalOrderPrice = $price;

    }

    /**
     * @throws CommonException
     * 计算订单使用优惠券后的价格
     */
    private function countAfterUsingCouponPrice()
    {

        /*先检查订单总金额是否满足优惠券使用门槛*/
        if (($this->couponInfo['found_sum'] + 0) > ($this->originalOrderPrice + 0)) {
            throw new CommonException(['msg' => '无效优惠券']);
        }
        /*检查商品列表中商品是否满足优惠券使用条件*/
        foreach ($this->goodsInfoArray as $goodsInfoArray_k => $goodsInfoArray_v) {

            if ($this->couponInfo['grant_type'] === 'classify') {

                if (!in_array($goodsInfoArray_v['cat_id'], $this->couponInfo['classify'])) {
                    throw new CommonException(['msg' => '无效优惠券']);
                }

            } elseif ($this->couponInfo['grant_type'] === 'solo') {

                if (!in_array($goodsInfoArray_v['goods_id'], $this->couponInfo['solo'])) {
                    throw new CommonException(['msg' => '无效优惠券']);
                }

            }

        }

        /*开始计算价格*/
        $this->afterUsingCouponPrice = $this->originalOrderPrice - $this->couponInfo['cut_sum'];
    }

    /**
     * @throws CommonException
     * 计算使用积分后价格
     */
    private function countAfterUsingIntegralPrice()
    {
        /*计算单笔订单允许使用积分和赠予积分数量*/
        $allow_integral_number = 0;
        $give_integral = 0;

        foreach ($this->skuInfoArray as $skuInfoArray_k => $skuInfoArray_v) {
            $allow_integral_number += $skuInfoArray_v['integral'] * $this->submitGoodsArray[$skuInfoArray_k]['goods_number'];

            $give_integral += $skuInfoArray_v['give_integral'] * $this->submitGoodsArray[$skuInfoArray_k]['goods_number'];
        }

        if ($allow_integral_number < $this->useIntegralNumber) {
            throw new CommonException(['msg' => '积分使用不合法']);
        }

        if ($this->userInfo['integral'] < $this->useIntegralNumber) {
            throw new CommonException(['msg' => '积分使用不合法']);
        }

        $this->allowIntegralNumber = $allow_integral_number;
        $this->giveIntegral = $give_integral;

        /*计算价格*/
        $this->afterUsingIntegralPrice = $this->afterUsingCouponPrice - ($this->useIntegralNumber / 100);
    }

    /**
     * 计算订单支付折扣后价格
     */
    private function countAfterUsingPayPrice()
    {

        $this->afterUsingPayPrice = $this->afterUsingIntegralPrice * $this->byStagesInfo['bystages_fee'];

    }

    /**
     * 计算订单最终价格
     */
    private function countOrderPrice()
    {

        $this->orderPrice = $this->afterUsingPayPrice;

    }

    /**
     * 生成订单号
     */
    private function createOrderSn()
    {
        do {
            $date = time();
            $randChar = getRandCharDNoNumber(6);
            $order_sn = $date . $randChar;
        } while (GlOrder::getOrderInfoByOrderSn($order_sn));

        $this->orderSn = $order_sn;

    }

    /**
     * 保存数据库
     */
    private function orderInfoSaveDb()
    {
        //删除数组中为null的项
        byValIsNullRemoveArrVal($this->invoiceInfo);

        Db::transaction(function () {
            /*保存订单主表*/
            GlOrder::create([
                'order_sn' => $this->orderSn,
                'user_id' => $this->userInfo['user_id'],
                'user_name' => $this->userInfo['user_name'],
                'order_state' => 1,
                'prev_order_state' => 1,
                'into_type' => $this->intoType,
                'son_into_type' => $this->sonIntoType,
                'son_into_type_name' => config('my_config.son_into_type_name')[$this->sonIntoType],
                'original_order_price' => $this->originalOrderPrice,
                'after_using_coupon_price' => $this->afterUsingCouponPrice,
                'after_using_integral_price' => $this->afterUsingIntegralPrice,
                'after_using_pay_price' => $this->afterUsingPayPrice,
                'order_price' => $this->orderPrice,
                'give_integral' => $this->giveIntegral,
                'bystages_id' => $this->byStagesInfo['bystages_id'],
                'pay_id' => $this->payTypeInfo['pay_id'],
                'pay_code' => $this->payTypeInfo['pay_code'],
                'pay_name' => $this->payTypeInfo['pay_name'],
                'bystages_val' => $this->byStagesInfo['bystages_val'],
                'create_time' => time(),//创建日期
                'upd_time' => time(),//修改日期
                'invalid_pay_time' => time() + config('my_config.invalid_pay_time'),//订单支付超时时间
                'is_del' => 0,
                'logistics_name' => $this->addressInfo['name'],
                'logistics_tel' => $this->addressInfo['tel'],
                'logistics_address' => $this->addressInfo['province'] . $this->addressInfo['city'] . $this->addressInfo['county'] . $this->addressInfo['address_detail'],
                'logistics_code' => 'shunfeng',//快递方式代码，暂时写死
            ]);

            /*保存订单中间表*/
            foreach ($this->submitGoodsArray as $submitGoodsArray_k => $submitGoodsArray_v) {
                GlMidOrder::create([
                    'order_sn' => $this->orderSn,
                    'goods_id' => $this->goodsInfoArray[$submitGoodsArray_k]['goods_id'],
                    'goods_name' => $this->goodsInfoArray[$submitGoodsArray_k]['goods_name'],
                    'sku_id' => $this->skuInfoArray[$submitGoodsArray_k]['sku_id'],
                    'sku_desc' => $this->skuInfoArray[$submitGoodsArray_k]['sku_desc'],
                    'goods_number' => $submitGoodsArray_v['goods_number'],
                    'mid_order_price' => $this->skuInfoArray[$submitGoodsArray_k]['sku_shop_price'] * $submitGoodsArray_v['goods_number'],
                    'give_integral' => $this->skuInfoArray[$submitGoodsArray_k]['give_integral'] * $submitGoodsArray_v['goods_number'],
                    'is_evaluate' => 0,
                    'img_url' => removeImgUrl($this->skuInfoArray[$submitGoodsArray_k]['img_url']),
                ]);
            };

            /*保存发票表*/
            $this->invoiceInfo['order_sn'] = $this->orderSn;
            GlOrderInvoice::create($this->invoiceInfo);

            /*如果使用了优惠券,就改为已使用*/
            if ($this->couponInfo) {
                GlMidUserCoupon::where([
                    ['user_id', '=', $this->userInfo['user_id']],
                    ['coupon_id', '=', $this->couponInfo['coupon_id']]
                ])
                    ->update([
                        'is_use' => 1,
                        'use_time' => time()
                    ]);
            }

            /*扣除积分*/
            GlUser::where([
                ['user_id', '=', $this->userInfo['user_id']]
            ])
                ->setDec('integral', ($this->useIntegralNumber + 0));

        });
    }
}