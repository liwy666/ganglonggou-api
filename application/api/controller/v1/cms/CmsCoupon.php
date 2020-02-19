<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/5/22
 * Time: 9:06
 */

namespace app\api\controller\v1\cms;


use app\api\model\GlCoupon;
use app\api\service\UserAuthority;
use app\api\validate\CurrencyValidate;

class CmsCoupon
{
    /**
     * @return bool
     * @throws \app\lib\exception\CommonException
     * 添加优惠券
     */
    public function addCoupon()
    {
        UserAuthority::checkAuthority(8);
        //验证必要
        (new CurrencyValidate())->myGoCheck(['coupon_name', 'coupon_desc', 'start_grant_time', 'end_grant_time', 'start_use_time', 'end_use_time', 'found_sum', 'cut_sum', 'coupon_number', 'coupon_remainder_number', 'into_type', 'grant_type'], 'require');
        //验证正整数
        (new CurrencyValidate())->myGoCheck(['start_grant_time', 'end_grant_time', 'coupon_number'], 'positiveInt');

        $data["coupon_name"] = request()->param("coupon_name");
        $data["coupon_desc"] = request()->param("coupon_desc");
        $data["start_grant_time"] = request()->param("start_grant_time");
        $data["end_grant_time"] = request()->param("end_grant_time");
        $data["start_use_time"] = request()->param("start_use_time");
        $data["end_use_time"] = request()->param("end_use_time");
        $data["found_sum"] = request()->param("found_sum");
        $data["cut_sum"] = request()->param("cut_sum");
        $data["coupon_number"] = request()->param("coupon_number");
        $data["coupon_remainder_number"] = request()->param("coupon_remainder_number");
        $data["into_type"] = request()->param("into_type");
        $data["grant_type"] = request()->param("grant_type");
        $data["is_del"] = 0;

        //第二次验证
        switch (request()->param('grant_type')) {
            case 'solo':
                (new CurrencyValidate())->myGoCheck(['solo'], 'require');
                $data["solo"] = json_encode(request()->param("solo/a"),JSON_NUMERIC_CHECK);
                break;
            case 'classify':
                (new CurrencyValidate())->myGoCheck(['classify'], 'require');
                $data["classify"] = json_encode(request()->param("classify/a"),JSON_NUMERIC_CHECK);
                break;
        }

        GlCoupon::create($data);

        return true;
    }

    /**
     * @return bool
     * @throws \app\lib\exception\CommonException
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 编辑优惠券
     */
    public function updCoupon()
    {
        UserAuthority::checkAuthority(8);
        //验证必要
        (new CurrencyValidate())->myGoCheck(['coupon_name', 'coupon_desc', 'start_grant_time', 'end_grant_time', 'start_use_time', 'end_use_time', 'found_sum', 'cut_sum', 'coupon_number', 'coupon_remainder_number', 'into_type', 'grant_type'], 'require');
        //验证正整数
        (new CurrencyValidate())->myGoCheck(['start_grant_time', 'end_grant_time', 'coupon_number','coupon_id'], 'positiveInt');

        $coupon_id = request()->param("coupon_id");
        $data["coupon_name"] = request()->param("coupon_name");
        $data["coupon_desc"] = request()->param("coupon_desc");
        $data["start_grant_time"] = request()->param("start_grant_time");
        $data["end_grant_time"] = request()->param("end_grant_time");
        $data["start_use_time"] = request()->param("start_use_time");
        $data["end_use_time"] = request()->param("end_use_time");
        $data["found_sum"] = request()->param("found_sum");
        $data["cut_sum"] = request()->param("cut_sum");
        $data["coupon_number"] = request()->param("coupon_number");
        $data["coupon_remainder_number"] = request()->param("coupon_remainder_number");
        $data["into_type"] = request()->param("into_type");
        $data["grant_type"] = request()->param("grant_type");

        //第二次验证
        switch (request()->param('grant_type')) {
            case 'solo':
                (new CurrencyValidate())->myGoCheck(['solo'], 'require');
                $data["solo"] = json_encode(request()->param("solo/a"),JSON_NUMERIC_CHECK);
                break;
            case 'classify':
                (new CurrencyValidate())->myGoCheck(['classify'], 'require');
                $data["classify"] = json_encode(request()->param("classify/a"),JSON_NUMERIC_CHECK);
                break;
        }

        GlCoupon::where(['coupon_id'=> $coupon_id])
        ->update($data);

        return true;
    }


    /**
     * @return bool
     * @throws \app\lib\exception\CommonException
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 删除优惠券
     */
    public function delCoupon(){
        UserAuthority::checkAuthority(8);
        //验证必要
        (new CurrencyValidate())->myGoCheck(['coupon_id'], 'require');
        //验证正整数
        (new CurrencyValidate())->myGoCheck(['coupon_id'], 'positiveInt');

        $coupon_id = request()->param("coupon_id");

        GlCoupon::where(['coupon_id'=> $coupon_id])
            ->update(['is_del'=>1]);

        return true;


    }

    /**
     * @return mixed
     * @throws \app\lib\exception\CommonException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 分页获取优惠券列表
     */
    public function giveCouponListByPage(){
        //验证必要
        (new CurrencyValidate())->myGoCheck(['page', 'limit'], 'require');
        //验证正整数
        (new CurrencyValidate())->myGoCheck(['page', 'limit'], 'positiveInt');
        UserAuthority::checkAuthority(8);
        $data['page'] = request()->param('page');
        $data['limit'] = request()->param('limit');
        $where['is_del'] = 0;

        $result['list'] = GlCoupon::where($where)
            ->page($data['page'], $data['limit'])
            ->order('coupon_id desc')
            ->select();

        $result['count'] = GlCoupon::where($where)
            ->count();

        return $result;

    }
}