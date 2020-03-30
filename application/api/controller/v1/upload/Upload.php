<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/5/16
 * Time: 14:57
 */

namespace app\api\controller\v1\upload;


class Upload
{
    /**
     * @return mixed
     * @throws \app\lib\exception\CommonException
     * 图片上传
     */
    public function ImgUpload(){
        return (new \app\api\service\Upload\Upload())->ImgUpload();
    }
}