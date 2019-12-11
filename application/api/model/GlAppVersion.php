<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/6/18
 * Time: 17:21
 */

namespace app\api\model;


class GlAppVersion extends BaseModel
{
    public function getAddTimeAttr($value)
    {
        if ($value != null) {
            return date("Y-m-d H:i:s", $value);
        } else {
            return $value;
        }
    }

    public function getDownloadUrlAttr($value)
    {
        if ($value != null) {
            return config('my_config.api_url').$value;
        } else {
            return $value;
        }
    }
}