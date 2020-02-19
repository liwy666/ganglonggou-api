<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/6/10
 * Time: 9:52
 */

namespace app\api\model;


class GlAfterSale extends BaseModel
{
    public function getCreateTimeAttr($value,$data){
        if($value!=null){
            return date("Y-m-d H:i:s",$value);
        }else{
            return $value;
        }
    }

    public function getAllowTimeAttr($value,$data){
        if($value!=null){
            return date("Y-m-d H:i:s",$value);
        }else{
            return $value;
        }
    }
}