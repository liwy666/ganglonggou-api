<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/5/17
 * Time: 18:06
 */

namespace app\api\controller\v1;


use app\api\service\JsSdk\JsSdk;
use app\api\validate\CurrencyValidate;

class WxShare
{
    public function giveWxShareInfo(){

        (new CurrencyValidate())->myGoCheck(['url'], 'require');

        $url = request()->param('url');

        return (new JsSdk())->giveSignature($url);
    }
}