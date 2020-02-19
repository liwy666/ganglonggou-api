<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/6/4
 * Time: 13:16
 */

namespace app\api\controller\v1;


use app\api\service\SerEmail;
use app\api\validate\CurrencyValidate;

class Email
{
    /**
     * @throws \app\lib\exception\CommonException
     * 发送邮件命名api
     */
    public function sandEmail()
    {

        //验证必要
        (new CurrencyValidate())->myGoCheck(['head', 'body', 'address_array'], 'require');

        $head = request()->param('head');
        $body = request()->param('body');
        $address_array =  json_decode(request()->param('address_array'),true) ;


        (new SerEmail())->setEmail($head,$body,$address_array);

    }
}