<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/11/22
 * Time: 14:13
 */

namespace app\api\controller\v1\common;


use app\api\service\DES;

class AppConfig
{
    public function getAliPayMerchantPrivateKey()
    {
        $config = [];//可以不写，只是为了避免IDE报错
        require_once(dirname(\think\facade\Env::get('root_path')) . '/extend/PcAliPay/' . 'config.php');
        $key = config('my_config.des_key');
        $iv = config('my_config.des_vi');
        // DES CBC 加解密
        $des = new DES($key, 'DES-CBC', DES::OUTPUT_BASE64, $iv);
        $base64Sign = $des->encrypt($config['merchant_private_key']);
        return $base64Sign;
    }
}