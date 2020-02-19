<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/5/17
 * Time: 20:16
 */

namespace app\api\controller\v1\cms;


use app\api\model\GlMakeOrder;
use app\api\service\UserAuthority;
use app\api\validate\CurrencyValidate;
use app\lib\exception\CommonException;

class CmsMakeOrder
{
    public function giveMakeOderList(){

        //验证必要
        (new CurrencyValidate())->myGoCheck(['page', 'limit'], 'require');
        //验证正整数
        (new CurrencyValidate())->myGoCheck(['page', 'limit'], 'positiveInt');
        UserAuthority::checkAuthority(8);
        $data['page'] = request()->param('page');
        $data['limit'] = request()->param('limit');
        $where['is_del'] = 0;
        $result['list'] = GlMakeOrder::where($where)
            ->page($data['page'], $data['limit'])
            ->select()
            ->toArray();

        $result['count'] = GlMakeOrder::where($where)
            ->count();
        return $result;

    }

    /**
     * @return bool
     * @throws CommonException
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 处理订单
     */
    public function handleMakeOrder(){

        (new CurrencyValidate())->myGoCheck(['make_order_sn'], 'require');

        $data['make_order_sn'] = request()->param('make_order_sn');

       $upd_number =  GlMakeOrder::where($data)
            ->update(['make_state' => 1]);

       if($upd_number < 1){
           throw new CommonException(['msg'=>'处理失败']);
       }

       return true;
    }

    /**
     * @return bool
     * @throws CommonException
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 删除订单
     */
    public function delMakeOrder(){

        (new CurrencyValidate())->myGoCheck(['make_order_sn'], 'require');

        $data['make_order_sn'] = request()->param('make_order_sn');

        $upd_number =  GlMakeOrder::where($data)
            ->update(['is_del' => 1]);

        if($upd_number < 1){
            throw new CommonException(['msg'=>'处理失败']);
        }

        return true;
    }

}