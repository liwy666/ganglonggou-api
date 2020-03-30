<?php

namespace app\api\model;
class GlFeedBack extends BaseModel
{
    public function getAddTimeAttr($value)
    {
        if ($value != null) {
            return date("Y-m-d H:i:s", $value);
        } else {
            return $value;
        }
    }

    public function getImgFilesUrlAttr($value)
    {
        if ($value != null) {
//            $before_url = config('my_config.img_url');
            $func=function ($item){
                $before_url = config('my_config.img_url');
                return $before_url.$item;
            };
            return array_map($func, array_filter(explode(',', $value)));
        } else {
            return $value;
        }

    }



}