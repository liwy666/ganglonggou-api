<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/5/17
 * Time: 17:21
 */

namespace app\api\service\JsSdk;


use think\facade\Cache;

class JsSdk
{

    public function getAccess()
    {
        if (!(Cache::get('access_token'))) {
            $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' .
                config('my_config.wx_app_id') .
                '&secret=' . config('my_config.wx_secret');
            $access_token = $this->get($url);
            $access_token = json_decode($access_token)->access_token;
            //保存缓存
            Cache::set('access_token', $access_token, config('my_config.wx_expire_in'));
            $result = $access_token;
        } else {
            $result = Cache::get('access_token');
        }
        return $result;
    }

    //获取js_api_ticket
    public function getJsApi()
    {
        if (!(Cache::get('js_api_ticket'))) {
            $access_token = $this->getAccess();
            $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$access_token";
            $js_api_ticket = $this->get($url);
            $js_api_ticket = json_decode($js_api_ticket)->ticket;
            Cache::set('js_api_ticket',$js_api_ticket,config('my_config.wx_expire_in'));
            $result = $js_api_ticket;
        } else {
            $result = Cache::get('js_api_ticket');
        }
        return $result;
    }

    public function giveSignature($url){
        $url = URLdecode($url);
        $app_id = config('my_config.wx_app_id');
        $js_api_ticket = $this->getJsApi();
        $noncestr = getRandCharD(13);
        $timestamp = time();
        $string='jsapi_ticket='.$js_api_ticket.'&noncestr='.$noncestr.'&timestamp='.$timestamp.'&url='.$url;
        $signature = sha1($string);

        $result['appId'] = $app_id;
        $result['timestamp'] = $timestamp;
        $result['nonceStr'] = $noncestr;
        $result['signature'] = $signature;

        return $result;
    }

    public function giveTestSignature($url){
        //$access_token = $this->getAccess();
        $app_id = config('my_config.wx_app_id');
        $js_api_ticket = $this->getJsApi();
        $noncestr = getRandCharD(13);
        $timestamp = time();
        $string='jsapi_ticket='.$js_api_ticket.'&noncestr='.$noncestr.'&timestamp='.$timestamp.'&url='.$url;
        $signature = sha1($string);

        $result['appId'] = $app_id;
        $result['timestamp'] = $timestamp;
        $result['js_api_ticket'] = $js_api_ticket;
        $result['string'] = $string;
        $result['nonceStr'] = $noncestr;
        $result['signature'] = $signature;

        return $result;
    }


    /**
     * @param $url
     * @return bool|string
     * 请求数据 access,jsapi;
     */
    private function get($url)
    {
        $ch = curl_init();
        //设置选项，包括URL
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);//绕过ssl验证
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        //执行并获取HTML文档内容
        $output = curl_exec($ch);
        //释放curl句柄
        curl_close($ch);
        return $output;
    }
}