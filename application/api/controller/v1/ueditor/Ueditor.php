<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/5/16
 * Time: 13:28
 */

namespace app\api\controller\v1\ueditor;


class Ueditor
{
    public function ueditorFileUpload()
    {

        return (new \app\api\service\Ueditor\Ueditor())->handle();

    }
}