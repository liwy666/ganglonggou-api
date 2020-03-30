<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/5/16
 * Time: 18:40
 */

namespace app\lib\exception;


use think\exception\Handle;
use think\facade\Log;
use think\facade\Request;

class ExceptionHandler extends Handle
{
    private $code;
    private $msg;
    private $error_code;

    public function render(\Exception $e)
    {
        if($e instanceof BaseException){
            $this->code = $e->code;
            $this->msg = $e->msg;
            $this->error_code = $e->error_code;
        }else{
            if(config('app_debug')){
                return parent::render($e);
            }else{
                $this->code = '500';
                $this->msg = '服务器内部错误';
                $this->error_code ='999';
                $this->recordErrorLog($e);//记录日志
            }

        }

        $ruquest = Request::instance();

        $result = [
            'msg' => $this->msg,
            'error_code' => $this->error_code,
            'ruquest_url' => $ruquest->url()
        ];
        return json($result,$this->code);
    }

    //错误记录
    private function  recordErrorLog(\Exception $e){
        Log::info([
            'type' => 'File',
            'path' => config('my_config.log_file'),
            'level' =>['error']
        ]);
        Log::record($e->getMessage(),'error');
    }

}