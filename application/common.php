<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件

// 应用公共文件


/**
 * @param array $data
 * @return bool|string
 * 获取农行app_openid
 */
function abaapp_curl_get($data = [])
{

    $arry = [
        "Authorization " => $data['code']
    ];
    $curl = curl_init(); // 启动一个CURL会话
    curl_setopt($curl, CURLOPT_URL, $data['url']);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $arry);
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);  // 从证书中检查SSL加密算法是否存在
    $tmpInfo = curl_exec($curl);     //返回api的json对象		    //关闭URL请求
    curl_close($curl);
    return $tmpInfo;    //返回json对象
}

/**
 * @param $url
 * @return bool|string
 * 获取微信open_id
 */
function wx_curl_get($url)
{
    $curl = curl_init(); // 启动一个CURL会话
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);  // 从证书中检查SSL加密算法是否存在
    $tmpInfo = curl_exec($curl);     //返回api的json对象		    //关闭URL请求
    curl_close($curl);
    return $tmpInfo;    //返回json对象
}

/*发送一个post请求*/
function send_post($url, $post_data)
{
    $postdata = http_build_query($post_data);
    $options = array(
        'http' => array(
            'method' => 'POST',
            'header' => 'Content-type:application/x-www-form-urlencoded',
            'content' => $postdata,
            'timeout' => 60 // 超时时间（单位:s）
        )
    );
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    \think\facade\Log::record($result);
    $result = json_decode($result, true);
    \think\facade\Log::record($result);
    return $result;
}

/*发送一个post请求并返回xml*/
function send_post_xml($url, $post_data)
{

    $postdata = http_build_query($post_data);
    $options = array(
        'http' => array(
            'header' => 'Connection: close\r\n',
            'method' => 'POST',
            'header' => array('Content-Type: application/x-www-form-urlencoded', 'Accept: */*'),
            'content' => $postdata,
            'timeout' => 600 // 超时时间（单位:s）
        )
    );
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);

    return $result;

}

function send_post_xml2($url, $data)
{

    $headers = array('Content-Type: application/x-www-form-urlencoded', 'Accept: */*');
    $curl = curl_init(); // 启动一个CURL会话
    curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0); // 从证书中检查SSL加密算法是否存在
    curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
    curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
    curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data)); // Post提交的数据包
    curl_setopt($curl, CURLOPT_TIMEOUT, 60); // 设置超时限制防止死循环
    curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    $result = curl_exec($curl); // 执行操作

    return $result;


}

/**
 * 生成随机字符串
 */
function getRandChar($length)
{
    $str = null;
    $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
    $max = strlen($strPol) - 1;

    for ($i = 0;
         $i < $length;
         $i++) {
        $str .= $strPol[rand(0, $max)];
    }

    return $str;
}

/**
 * 生成随机字符串全大写
 */
function getRandCharD($length)
{
    $str = null;
    $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $max = strlen($strPol) - 1;

    for ($i = 0;
         $i < $length;
         $i++) {
        $str .= $strPol[rand(0, $max)];
    }

    return $str;
}

/**
 * 生成随机字符串全大写,不包含数字
 */
function getRandCharDNoNumber($length)
{
    $str = null;
    $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $max = strlen($strPol) - 1;

    for ($i = 0;
         $i < $length;
         $i++) {
        $str .= $strPol[rand(0, $max)];
    }

    return $str;
}

/**
 * @param $arr
 * @param $key
 * @return array
 * 数组分组
 */
function array_group_by($arr, $key)
{
    $grouped = [];
    foreach ($arr as $value) {
        $grouped[$value[$key]][] = $value;
    }
    if (func_num_args() > 2) {
        $args = func_get_args();
        foreach ($grouped as $key => $value) {
            $parms = array_merge([$value], array_slice($args, 2, func_num_args()));
            $grouped[$key] = call_user_func_array('array_group_by', $parms);
        }
    }
    return $grouped;
}

/**
 * @param $arr
 * @param $key
 * @return mixed
 * 根据键删除数组项
 */
function byKeyRemoveArrVal($arr, $key)
{
    if (!array_key_exists($key, $arr)) {
        return $arr;
    }
    $keys = array_keys($arr);
    $index = array_search($key, $keys);
    if ($index !== FALSE) {
        array_splice($arr, $index, 1);
    }
    return $arr;
}

/**
 * @param $file
 * @return mixed
 *去除图片中的url
 */
function removeImgUrl($file)
{

    if (strpos($file, config('my_config.img_url')) >= 0) {

        return str_replace(config('my_config.img_url'), '', $file);

    } else {
        return $file;
    }

}

/**
 * @param $arr
 * @param $key
 * @return mixed
 * 根据值是否为NUll删除数组项
 */
function byValIsNullRemoveArrVal($arr)
{
    foreach ($arr as $k => $v) {
        if ($v == null) {
            byKeyRemoveArrVal($arr, $k);
        }
    }
}

