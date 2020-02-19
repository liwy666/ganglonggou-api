<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/5/22
 * Time: 13:03
 */

namespace app\api\model;


class GlCoupon extends BaseModel
{
    public function getSoloAttr($value, $data)
    {
        if($value !== null){
            return json_decode($value, true);
        }else{
            return [];
        }
    }
    public function getClassifyAttr($value, $data)
    {
        if($value !== null){
            return json_decode($value, true);
        }else{
            return [];
        }
    }
    public function getStartGrantTimeAttr($value,$data){
        if($value!=null){
            return date("Y-m-d H:i:s",$value);
        }else{
            return $value;
        }
    }
    public function getEndGrantTimeAttr($value,$data){
        if($value!=null){
            return date("Y-m-d H:i:s",$value);
        }else{
            return $value;
        }
    }
    public function getStartUseTimeAttr($value,$data){
        if($value!=null){
            return date("Y-m-d H:i:s",$value);
        }else{
            return $value;
        }
    }
    public function getEndUseTimeAttr($value,$data){
        if($value!=null){
            return date("Y-m-d H:i:s",$value);
        }else{
            return $value;
        }
    }
}