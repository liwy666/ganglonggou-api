<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/5/15
 * Time: 16:01
 */

namespace app\api\validate;





use app\lib\exception\CommonException;
use think\facade\Request;
use think\Validate;

class BaseValidate extends Validate
{
    /**
     * @param string $data
     * @return bool
     * @throws CommonException
     */
    public function goCheck($data=''){
        //实例化请求对象
        $requestObj = Request::instance();
        //如果传入为空则获取请求里的参数
        empty($data) && $data = $requestObj->param();
        if($this -> check($data)){
            return true;
        }else{
            $e = new CommonException([
                'msg' => $this->error,
                'error_code' => 100001
            ]);
            throw $e;
        }
    }

    /**
     * @param $value
     * @param string $rule
     * @param string $data
     * @param string $field
     * @return bool|string
     */
    protected function isNotEmpty($value, $rule='', $data='', $field='')
    {
        if (empty($value)) {
            return $field . '不允许为空';
        } else {
            return true;
        }
    }
}