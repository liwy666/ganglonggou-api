<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/5/23
 * Time: 15:04
 */

namespace app\api\controller\v1\common;


use app\api\service\SerCart;
use app\api\validate\CurrencyValidate;

class Cart
{
    public function userGetCart(){

        //验证必要
        (new CurrencyValidate())->myGoCheck(['carts'], 'require');

        $carts = request()->param("carts/a");

       /* foreach ( $carts as $k => $v){
            $carts[$k] = json_decode($v,true);
        }    */

        $result = [];

        $SerCart = new SerCart();

        foreach ($carts as $k => $v){
            $cart = $SerCart->checkCartInfo($v);
            array_push($result,$cart);
        }

        return $result;
    }
}