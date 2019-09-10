<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/5/24
 * Time: 9:52
 */

namespace app\api\service;


use app\api\model\GlAddress;
use app\lib\exception\CommonException;

class SerAddress
{
    public $userId;
    public $addressId;
    public $name;
    public $tel;
    public $province;
    public $city;
    public $county;
    public $addressDetail;
    public $areaCode;

    /**
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * 用户添加地址
     */
    public function userAddAddress()
    {
        $data['user_id'] = $this->userId;
        $data['name'] = $this->name;
        $data['tel'] = $this->tel;
        $data['province'] = $this->province;
        $data['city'] = $this->city;
        $data['county'] = $this->county;
        $data['address_detail'] = $this->addressDetail;
        $data['area_code'] = $this->areaCode;
        $data['is_del'] = 0;
        $data['add_time'] = time();
        $data['upd_time'] = time();
        $data['is_default'] = 0;

        $address_info = GlAddress::create($data);

        //判断是否有默认地址
        $check_is_default = GlAddress::where([
            ['user_id', '=', $this->userId]
            , ['is_del', '=', 0]
            , ['is_default', '=', 1]
        ])
            ->find();
        if (!$check_is_default) {
            GlAddress::where([['address_id', '=', $address_info->id]])
                ->update(['is_default' => 1]);
        }

        return true;
    }

    /**
     * @return bool
     * @throws CommonException
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 用户修改地址
     */
    public function userUpdAddress()
    {

        $data['name'] = $this->name;
        $data['tel'] = $this->tel;
        $data['province'] = $this->province;
        $data['city'] = $this->city;
        $data['county'] = $this->county;
        $data['address_detail'] = $this->addressDetail;
        $data['area_code'] = $this->areaCode;
        $data['upd_time'] = time();


        $upd_number = GlAddress::where([['user_id', '=', $this->userId]
            , ['address_id', '=', $this->addressId]
            , ['is_del', '=', 0]])
            ->update($data);

        if ($upd_number < 1) {
            throw new CommonException(['msg' => '操作失败']);
        }

        return true;
    }
}