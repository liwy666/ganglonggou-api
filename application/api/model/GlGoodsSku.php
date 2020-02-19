<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/5/16
 * Time: 16:42
 */

namespace app\api\model;


class GlGoodsSku extends BaseModel
{
    public function getImgUrlAttr($value, $data)
    {
        return $this->spellOriginalImg($value, $data);
    }

    public function getOriginalImgUrlAttr($value, $data)
    {
        return $this->spellOriginalImg($value, $data);
    }
}