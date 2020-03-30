<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/6/27
 * Time: 13:49
 */

namespace app\api\service\Login;


use think\facade\Cache;

class PcLogin extends BaseLogin
{

    /**
     * @param $code
     * @return bool
     * 返回token
     */
    public function giveTokenByWxOpenid($code)
    {

        $cache_data = Cache::get($code);

        if (!$cache_data || $cache_data['token'] === null) {
            return false;
        } else {
            return $cache_data['token'];
        }

    }

    /**
     * @param $code
     * @param $user_id
     * @return bool
     * @throws \app\lib\exception\CommonException
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 通过微信token更新缓存
     */
    public function writeTokenByWxToken($code, $user_id)
    {

        $cache_data = Cache::get($code);

        if ($cache_data && $cache_data['token'] === null) {
            /*保存用户信息*/
            self::recordUserLogin($user_id);
            /*生成token*/
            $result['user_id'] = $user_id;
            $result['into_type'] = 'wx';
            $result['son_into_type'] = 'pc';
            $token = self::saveToCache($result);
            /*清除缓存，重写*/
            Cache::rm($code);
            $data['token'] = $token;
            Cache::set($code, $data, 300);//设定时间为5分钟
        }

        return true;
    }
}