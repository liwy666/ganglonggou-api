<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/6/17
 * Time: 15:18
 */

namespace app\api\model;


class GlSupplier extends BaseModel
{
    public function getLogoImgAttr($value, $data)
    {
        return $this->spellOriginalImg($value, $data);
    }

    public function getHeadImgAttr($value, $data)
    {
        return $this->spellOriginalImg($value, $data);
    }

}