<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/5/15
 * Time: 15:39
 */

namespace app\api\model;


use think\Model;

class BaseModel extends Model
{
    //直接拼接图片
    protected function spellOriginalImg($value, $data)
    {
        if (!$value) {
            return $value;
        }
        return config('my_config.img_url') . $value;
    }

    //img标签拼接图片
    protected function imgTagSpellOriginalImg($value, $data)
    {
        if (!$value) {
            return $value;
        }
        $img_src = 'src="' . config('my_config.img_url');
        $str = str_replace('src="', $img_src, $value);
        //$str = str_replace('"','', $str);
        return $str;
    }
}