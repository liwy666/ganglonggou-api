<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/5/15
 * Time: 10:51
 */

namespace app\lib\exception;


use think\Exception;

class BaseException extends Exception
{
    public $code = 400;
    public $msg = '未知错误';
    public  $error_code = 10000;

    public function __construct($exception_arr=[])
    {
        if (!is_array($exception_arr)){
            return;
        }else{
            if(array_key_exists('code',$exception_arr)){
                $this->code = $exception_arr['code'];
            }
            if(array_key_exists('msg',$exception_arr)){
                $this->msg = $exception_arr['msg'];
            }
            if(array_key_exists('error_code',$exception_arr)){
                $this->error_code = $exception_arr['error_code'];
            }
        }
    }
}