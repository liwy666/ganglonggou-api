<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/5/24
 * Time: 9:38
 */

namespace app\api\controller\v1\common;


use app\api\model\GlAddress;
use app\api\service\Login\BaseLogin;
use app\api\service\SerAddress;
use app\api\validate\CurrencyValidate;
use app\lib\exception\CommonException;

class Address
{
    /**
     * @return bool
     * @throws \app\lib\exception\CommonException
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * 添加地址
     */
    public function addAddress()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['user_token', 'name', 'tel', 'province', 'city', 'county', 'address_detail', 'area_code'], 'require');
        //验证正整数
        (new CurrencyValidate())->myGoCheck(['area_code'], 'positiveInt');

        $name = request()->param('name');
        $tel = request()->param('tel');
        $province = request()->param('province');
        $city = request()->param('city');
        $county = request()->param('county');
        $address_detail = request()->param('address_detail');
        $area_code = request()->param('area_code');

        //获取用户信息
        $user_token = request()->param("user_token");
        $user_desc = BaseLogin::getCurrentIdentity(['user_id'], $user_token);
        $user_id = $user_desc['user_id'];

        $SerAddress = new SerAddress();
        $SerAddress->userId = $user_id;
        $SerAddress->name = $name;
        $SerAddress->tel = $tel;
        $SerAddress->province = $province;
        $SerAddress->city = $city;
        $SerAddress->county = $county;
        $SerAddress->addressDetail = $address_detail;
        $SerAddress->areaCode = $area_code;

        return $SerAddress->userAddAddress();

    }

    /**
     * @return array|\PDOStatement|string|\think\Model|null
     * @throws \app\lib\exception\CommonException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 返回地址
     */
    public function giveAddress()
    {

        //验证必要
        (new CurrencyValidate())->myGoCheck(['user_token'], 'require');

        //获取用户信息
        $user_token = request()->param("user_token");
        $user_desc = BaseLogin::getCurrentIdentity(['user_id'], $user_token);
        $user_id = $user_desc['user_id'];

        $result = GlAddress::where([['user_id', '=', $user_id]
            , ['is_del', '=', 0]])
            ->field('is_del,add_time,upd_time', true)
            ->select();

        return $result;
    }

    /**
     * @return bool
     * @throws CommonException
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 切换默认收货地址
     */
    public function updDefaultAddress()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['address_id', 'user_token'], 'require');
        //验证正整数
        (new CurrencyValidate())->myGoCheck(['address_id'], 'positiveInt');

        $address_id = request()->param('address_id');

        //获取用户信息
        $user_token = request()->param("user_token");
        $user_desc = BaseLogin::getCurrentIdentity(['user_id'], $user_token);
        $user_id = $user_desc['user_id'];

        $address_info = GlAddress::where([['user_id', '=', $user_id]
            , ['address_id', '=', $address_id]
            , ['is_default', '=', 0]
            , ['is_del', '=', 0]])
            ->find();

        if (!$address_info) {
            throw new CommonException(['msg' => '切换默认地址失败']);
        }

        //取消所有默认地址
        GlAddress::where([['user_id', '=', $user_id]])
            ->update(['is_default' => 0]);

        //切换默认地址
        GlAddress::where([['address_id', '=', $address_id]])
            ->update(['is_default' => 1
                , 'upd_time' => time()]);

        return true;
    }

    /**
     * @return bool
     * @throws CommonException
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 删除地址
     */
    public function delAddress()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['address_id', 'user_token'], 'require');
        //验证正整数
        (new CurrencyValidate())->myGoCheck(['address_id'], 'positiveInt');

        $address_id = request()->param('address_id');

        //获取用户信息
        $user_token = request()->param("user_token");
        $user_desc = BaseLogin::getCurrentIdentity(['user_id'], $user_token);
        $user_id = $user_desc['user_id'];

        $upd_number = GlAddress::where([['user_id', '=', $user_id]
            , ['address_id', '=', $address_id]
            , ['is_del', '=', 0]])
            ->update(['is_del' => 1]);

        if ($upd_number < 1) {
            throw new CommonException(['msg' => '操作失败']);
        }

        //如果没有默认收货地址，就指定一条
        $is_default_number = GlAddress::where([
            ['user_id', '=', $user_id]
            , ['is_default', '=', 1]
            , ['is_del', '=', 0]
        ])->count();
        if ($is_default_number < 1) {
            GlAddress::where([['user_id', '=', $user_id]
                , ['is_default', '=', 0]
                , ['is_del', '=', 0]])
                ->limit(1)
                ->update(['is_default' => 1]);
        }


        return true;
    }


    /**
     * @return bool
     * @throws CommonException
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 修改地址
     */
    public function updAddress()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['user_token', 'address_id', 'name', 'tel', 'province', 'city', 'county', 'address_detail', 'area_code'], 'require');
        //验证正整数
        (new CurrencyValidate())->myGoCheck(['area_code', 'address_id'], 'positiveInt');

        $address_id = request()->param('address_id');
        $name = request()->param('name');
        $tel = request()->param('tel');
        $province = request()->param('province');
        $city = request()->param('city');
        $county = request()->param('county');
        $address_detail = request()->param('address_detail');
        $area_code = request()->param('area_code');

        //获取用户信息
        $user_token = request()->param("user_token");
        $user_desc = BaseLogin::getCurrentIdentity(['user_id'], $user_token);
        $user_id = $user_desc['user_id'];

        $SerAddress = new SerAddress();
        $SerAddress->userId = $user_id;
        $SerAddress->addressId = $address_id;
        $SerAddress->name = $name;
        $SerAddress->tel = $tel;
        $SerAddress->province = $province;
        $SerAddress->city = $city;
        $SerAddress->county = $county;
        $SerAddress->addressDetail = $address_detail;
        $SerAddress->areaCode = $area_code;

        return $SerAddress->userUpdAddress();
    }
}