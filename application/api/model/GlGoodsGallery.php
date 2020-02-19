<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/5/16
 * Time: 16:35
 */

namespace app\api\model;


class GlGoodsGallery extends BaseModel
{
    public function getImgUrlAttr($value, $data)
    {
        return $this->spellOriginalImg($value, $data);
    }

    public function getImgOriginalAttr($value, $data)
    {
        return $this->spellOriginalImg($value, $data);
    }
}