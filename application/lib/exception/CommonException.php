<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/5/15
 * Time: 10:51
 */

namespace app\lib\exception;


class CommonException extends BaseException
{
    public $code = 200;//因为跨域问题，占时将错误码设置为200
    public $msg = '系统错误';
    public $errorCode = 60004;
}