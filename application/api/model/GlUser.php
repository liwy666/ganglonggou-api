<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/5/23
 * Time: 10:12
 */

namespace app\api\model;


class GlUser extends BaseModel
{
    public function getUserImgAttr($value, $data)
    {
        return $this->spellOriginalImg($value, $data);
    }
    public function getAddTimeAttr($value,$data){
        if($value!=null){
            return date("Y-m-d H:i:s",$value);
        }else{
            return $value;
        }
    }
    public function getLoginTimeAttr($value,$data){
        if($value!=null){
            return date("Y-m-d H:i:s",$value);
        }else{
            return $value;
        }
    }
}