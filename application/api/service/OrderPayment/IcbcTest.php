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
        $cert = $this->getCert();
        $pass = '123456789';
        $notifyData = 'PD94bWwgIHZlcnNpb249IjEuMCIgZW5jb2Rpbmc9IkdCSyIgc3RhbmRhbG9uZT0ibm8iID8+PEIyQ1Jlcz48aW50ZXJmYWNlTmFtZT5JQ0JDX1dBUEJfQjJDPC9pbnRlcmZhY2VOYW1lPjxpbnRlcmZhY2VWZXJzaW9uPjEuMC4wLjY8L2ludGVyZmFjZVZlcnNpb24+PG9yZGVySW5mbz48b3JkZXJEYXRlPjIwMTkwNzIyMDkxNzU2PC9vcmRlckRhdGU+PG9yZGVyaWQ+MTU2Mzc1ODI3NFdMU0lHRTwvb3JkZXJpZD48YW1vdW50PjE8L2Ftb3VudD48aW5zdGFsbG1lbnRUaW1lcz4xPC9pbnN0YWxsbWVudFRpbWVzPjxtZXJBY2N0PjExMDMwMjg4MDkyMDA5OTQ0MTg8L21lckFjY3Q+PG1lcklEPjExMDNFRTIwMTc1MDEyPC9tZXJJRD48Y3VyVHlwZT4wMDE8L2N1clR5cGU+PHZlcmlmeUpvaW5GbGFnPjA8L3ZlcmlmeUpvaW5GbGFnPjxKb2luRmxhZz4wPC9Kb2luRmxhZz48VXNlck51bT48L1VzZXJOdW0+PC9vcmRlckluZm8+PGJhbms+PFRyYW5TZXJpYWxObz5IRVowMDAwMDg3Njc3OTU2NzY8L1RyYW5TZXJpYWxObz48bm90aWZ5RGF0ZT4yMDE5MDcyMjA5MjAzNzwvbm90aWZ5RGF0ZT48dHJhblN0YXQ+MTwvdHJhblN0YXQ+PGNvbW1lbnQ+vbvS17PJuaajrNLRx+XL46OhPC9jb21tZW50PjwvYmFuaz48L0IyQ1Jlcz4=';//明文
        $merVAR = '01';
        $signMsg = '+jrBfLXhkIiYmzNi8u6PSFzRTTaonynWaWZh77gmnt/AAYgDgZsKi1m55/8lBGRp29Mv4Q21aLBsm657aut1tf48yoCB7pRN3+kLkEiQd2hYFT+PVOzCE3AjSPRlSUW4xse8460T/IBYz9m79ouJoZF33XB8sR9wG+pZaV4N99E=';//签名

//        Log::write(base64_decode($notifyData), 'debug');
//        return true;

        return $this->sign(base64_decode($notifyData), $cert, $signMsg);
    }

    function sign($data, $cert, $sign)
    {
        $tSign = base64_decode($sign);
        $key = openssl_pkey_get_public($cert);
        $data = strval($data);

        return openssl_verify($data, $tSign, $key, OPENSSL_ALGO_SHA256);
    }

}

function coverParamsToString($params)
{
    $sign_str = '';
    ksort($params);
    foreach ($params as $key => $val) {
        if ($key == 'signature') {
            continue;
        }
        $sign_str .= sprintf("%s=%s&", $key, $val);
    }
    return substr($sign_str, 0, strlen($sign_str) - 1);
}

function der2pem($der_data)
{
    $pem = chunk_split(base64_encode($der_data), 64, "\n");
    $pem = "-----BEGIN CERTIFICATE-----\n" . $pem . "-----END CERTIFICATE-----\n";
    return $pem;
}

function getPrivateKey($cert_path)
{
    $pkcs12 = file_get_contents($cert_path);
    openssl_pkcs12_read($pkcs12, $certs, '123456789');
    return $certs ['pkey'];
}

function xmlToArray($xml)
{
    //禁止引用外部xml实体
    libxml_disable_entity_loader(true);
    $values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    return $values;
}

function sign(&$params)
{

    $params_str = coverParamsToString($params);


    $params_sha1x16 = sha1($params_str, FALSE);
    $cert_path = SDK_SIGN_CERT_PATH;
    $private_key = getPrivateKey($cert_path);
    $sign_falg = openssl_sign($params_sha1x16, $signature, $private_key, OPENSSL_ALGO_SHA1);
    if ($sign_falg) {
        $signature_base64 = base64_encode($signature);
        return $signature_base64;
    }
}