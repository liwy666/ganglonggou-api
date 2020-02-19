<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/5/15
 * Time: 16:07
 */

namespace app\api\validate;


use app\lib\exception\CommonException;
use think\facade\Request;
use think\Validate;

class CurrencyValidate extends Validate
{

    /**
     * @param $array 要验证的数据的名称数组
     * @param $rule_ 规则
     * @return bool
     * @throws CommonException
     */
    public function myGoCheck($array, $rule_)
    {
        foreach ($array as $k => $v) {
            $data_name = $v;
            $data_rule = $rule_;
            $this->rule[$data_name] = $data_rule;
        }
        //实例化请求对象
        $requestObj = Request::instance();
        $data = $requestObj->param();

        if ($this->check($data)) {
            return true;
        } else {
            $e = new CommonException([
                'msg' => $this->error,
                'error_code' => 100001
            ]);
            throw $e;
        }

    }


    //系统会自动传入几个参数 第一个是 要验证的值，第二个是规则，自己可以规定规则内容或者不写，第三个是最初传入的data。其实不只这三个参数，想了解详细的可以看看文档
    protected function positiveInt($value, $rule = '', $data)
    {
        if (is_int(($value + 0)) && ($value + 0) > 0) {
            return true;
        } else {
            return '标识ID必须为正整数';
        }
    }
}