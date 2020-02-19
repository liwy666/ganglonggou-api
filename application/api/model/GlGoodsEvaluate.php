<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/6/6
 * Time: 12:50
 */

namespace app\api\model;


class GlGoodsEvaluate extends BaseModel
{
    public function getUserImgAttr($value, $data)
    {
        return $this->spellOriginalImg($value, $data);
    }

    public function getCreateTimeAttr($value,$data){
        if($value!=null){
            return date("Y-m-d H:i:s",$value);
        }else{
            return $value;
        }
    }
}