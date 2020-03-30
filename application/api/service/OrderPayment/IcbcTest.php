<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/7/22
 * Time: 11:05
 */

namespace app\api\service\OrderPayment;


use think\facade\Log;

class IcbcTest
{

    private $file;
    static $iMerchantCertificates;

    public function __construct()
    {
        $this->file = dirname(\think\facade\Env::get('root_path')) . '/cert/Icbc/';
    }

    public function getKey()
    {
        $keyfile = $this->file . '1.crt';
        if (strlen($keyfile) <= 0) {
            return false;
        }
        $fp = fopen($keyfile, "rb");
        if ($fp == NULL) {
            return false;
        }
        fseek($fp, 0, SEEK_END);
        $filelen = ftell($fp);
        fseek($fp, 0, SEEK_SET);
        $contents = fread($fp, $filelen);
        fclose($fp);
        $key = substr($contents, 2);
        return $key;
    }

    public function getCert()
    {
        $merCert_file = $this->file . 'TrustPay.cer';
//
        $cert = openssl_x509_read(der2pem(file_get_contents($merCert_file)));
        //$cert = openssl_pkcs12_read(der2pem(file_get_contents($merCert_file)));
        return $cert;
    }

    public function notifyProcess()
    {
        $pass = '123456789';
        $notifyData = 'PD94bWwgIHZlcnNpb249IjEuMCIgZW5jb2Rpbmc9IkdCSyIgc3RhbmRhbG9uZT0ibm8iID8+PEIyQ1Jlcz48aW50ZXJmYWNlTmFtZT5JQ0JDX1dBUEJfQjJDPC9pbnRlcmZhY2VOYW1lPjxpbnRlcmZhY2VWZXJzaW9uPjEuMC4wLjY8L2ludGVyZmFjZVZlcnNpb24+PG9yZGVySW5mbz48b3JkZXJEYXRlPjIwMTkwNzIyMDkxNzU2PC9vcmRlckRhdGU+PG9yZGVyaWQ+MTU2Mzc1ODI3NFdMU0lHRTwvb3JkZXJpZD48YW1vdW50PjE8L2Ftb3VudD48aW5zdGFsbG1lbnRUaW1lcz4xPC9pbnN0YWxsbWVudFRpbWVzPjxtZXJBY2N0PjExMDMwMjg4MDkyMDA5OTQ0MTg8L21lckFjY3Q+PG1lcklEPjExMDNFRTIwMTc1MDEyPC9tZXJJRD48Y3VyVHlwZT4wMDE8L2N1clR5cGU+PHZlcmlmeUpvaW5GbGFnPjA8L3ZlcmlmeUpvaW5GbGFnPjxKb2luRmxhZz4wPC9Kb2luRmxhZz48VXNlck51bT48L1VzZXJOdW0+PC9vcmRlckluZm8+PGJhbms+PFRyYW5TZXJpYWxObz5IRVowMDAwMDg3Njc3OTU2NzY8L1RyYW5TZXJpYWxObz48bm90aWZ5RGF0ZT4yMDE5MDcyMjA5MjAzNzwvbm90aWZ5RGF0ZT48dHJhblN0YXQ+MTwvdHJhblN0YXQ+PGNvbW1lbnQ+vbvS17PJuaajrNLRx+XL46OhPC9jb21tZW50PjwvYmFuaz48L0IyQ1Jlcz4=';
        $merVAR = '01';
        $signMsg = '+jrBfLXhkIiYmzNi8u6PSFzRTTaonynWaWZh77gmnt/AAYgDgZsKi1m55/8lBGRp29Mv4Q21aLBsm657aut1tf48yoCB7pRN3+kLkEiQd2hYFT+PVOzCE3AjSPRlSUW4xse8460T/IBYz9m79ouJoZF33XB8sR9wG+pZaV4N99E=';//签名

//        Log::write(base64_decode($notifyData), 'debug');
//        return true;

        $private_key = self::bindMerchantCertificateByFile();
        openssl_sign(base64_decode($notifyData), $signature, $private_key, OPENSSL_ALGO_SHA1);
        $signature = base64_encode($signature);
        return $signature;

    }

    private static function bindMerchantCertificateByFile()
    {
        $tMerchantCertFiles = 'C:\Users\administrator_liwy\Desktop\web\API\ganglonggou-api\cert\Icbc\2019_04_15.pfx';
        $tMerchantCertPasswords = '123456';

        $tMerchantCertFileArray = array_filter(array_map('trim', explode(',', $tMerchantCertFiles, 100)));
        $tMerchantCertPasswordArray = array_filter(array_map('trim', explode(',', $tMerchantCertPasswords, 100)));

        $iMerchantCertificates = array();
        $iMerchantKeys = array();
        for ($i = 0; $i <= 1; $i++) {
            //1、读取证书
            $tCertificate = array();
            if (openssl_pkcs12_read(file_get_contents($tMerchantCertFileArray[$i]), $tCertificate, $tMerchantCertPasswordArray[$i])) {
                //2、验证证书是否在有效期内
                $cer = openssl_x509_parse($tCertificate['cert']);
                $t = time();
                self:: $iMerchantCertificates[] = $tCertificate;
                //3、取得密钥
                $pkey = openssl_pkey_get_private($tCertificate['pkey']);
                if ($pkey) {
                    return $pkey;
                }
            }
        }
    }
}

