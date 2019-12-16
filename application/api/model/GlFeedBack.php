<?php
namespace app\api\model;
class GlFeedBack extends BaseModel
{
    public function getAddTimeAttr($value){
        if ($value != null) {
            return date("Y-m-d H:i:s", $value);
        } else {
            return $value;
        }
    }
    public function getImgFilesUrlAttr($value){
        if ($value != null) {
            return array_filter(explode(',',$value));
        } else {
            return $value;
        }
    }

}