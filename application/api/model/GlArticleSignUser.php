<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/9/2
 * Time: 10:07
 */

namespace app\api\model;


class GlArticleSignUser extends BaseModel
{
    public function getCreateTimeAttr($value)
    {
        if ($value != null) {
            return date("Y-m-d H:i:s", $value);
        } else {
            return $value;
        }
    }
}