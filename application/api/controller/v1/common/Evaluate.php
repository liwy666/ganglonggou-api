<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/6/6
 * Time: 12:52
 */

namespace app\api\controller\v1\common;


use app\api\model\GlGoodsEvaluate;
use app\api\service\Login\BaseLogin;
use app\api\service\SerEvaluate;
use app\api\validate\CurrencyValidate;
use app\lib\exception\CommonException;

class Evaluate
{
    /**
     * @return bool
     * @throws CommonException
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 提交评价
     */
    public function insEvaluate()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['user_token', 'evaluate_text', 'rate', 'id'], 'require');
        //验证正整数
        (new CurrencyValidate())->myGoCheck(['id'], 'positiveInt');

        //获取用户信息
        $user_token = request()->param("user_token");
        $user_desc = BaseLogin::getCurrentIdentity(['user_id', 'into_type', 'son_into_type'], $user_token);
        $user_id = $user_desc['user_id'];

        $id = request()->param('id');
        $evaluate_text = request()->param('evaluate_text');
        $rate = request()->param('rate');

        //检查长度
        if (mb_strlen($evaluate_text) > 500) {
            throw new CommonException(['msg' => '评价内容超出500字']);
        }
        //检查评分
        if ($rate < 1 || $rate > 5) {
            throw new CommonException(['msg' => '评分不合法']);
        }

        $EvaluateClass = new SerEvaluate();
        $EvaluateClass->midOrderId = $id;
        $EvaluateClass->userId = $user_id;
        $EvaluateClass->evaluateText = $evaluate_text;
        $EvaluateClass->rate = $rate;

        return $EvaluateClass->userInsEvaluate();

    }

    public function giveEvaluateListByGoodsIdAndPage()
    {

        //验证必要
        (new CurrencyValidate())->myGoCheck(['page', 'limit', 'goods_id'], 'require');
        //验证正整数
        (new CurrencyValidate())->myGoCheck(['page', 'limit', 'goods_id'], 'positiveInt');
        $goods_id = request()->param('goods_id');
        $page = request()->param('page');
        $limit = request()->param('limit');

        $result = GlGoodsEvaluate::where([
            ['goods_id', '=', $goods_id],
            ['is_del', '=', 0],
            ['is_allow', '=', 1],
        ])
            ->page($page, $limit)
            ->field('create_time,parent_id,user_name,user_img,goods_name,sku_desc,rate,evaluate_text,id')
            ->select();

        return $result;

    }
}