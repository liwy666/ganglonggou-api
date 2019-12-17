<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/12/9
 * Time: 17:16
 */

namespace app\api\controller\v1;


use app\api\model\GlAppVersion;
use app\api\service\Upload\Upload;
use app\api\service\UserAuthority;
use app\api\validate\CurrencyValidate;


class AppVersion
{


    public function getAppVersion()
    {
        (new CurrencyValidate())->myGoCheck(['platform'], 'require');
        $platform = request()->param('platform');


        $result = GlAppVersion::where([
            ['platform', '=', $platform]
        ])
            ->order(['add_time' => 'desc'])
            ->find();
        if (!$result) {
            $result["result_code"] = "fail";
        } else {
            $result["result_code"] = "success";
        }

        return $result;
    }

    /**
     * @return bool
     * @throws \app\lib\exception\CommonException
     * 添加新版本应用
     */
    public function addNewVersionApp()
    {
        (new CurrencyValidate())->myGoCheck(['platform', 'app_version', 'describe'], 'require');
        UserAuthority::checkAuthority(8);
        $platform = request()->param('platform');
        $version = request()->param('app_version');
        $build_number = request()->param('build_number');
        $describe = request()->param('describe');
        if (request()->param('download_url')) {
            $download_url = removeImgUrl(request()->param('download_url'), config('my_config.api_url'));
            $file_size = request()->param('file_size');
        } else {
            $download_url = null;
            $file_size = null;
        }

        GlAppVersion::create([
            "platform" => $platform,
            "app_version" => $version,
            "build_number" => $build_number,
            "describe" => $describe,
            "download_url" => $download_url,
            "file_size" => $file_size,
            "add_time" => time(),
        ]);

        return true;
    }

    /**
     * @return mixed
     * @throws \app\lib\exception\CommonException
     * 上传应用
     */
    public function uploadApp()
    {
        UserAuthority::checkAuthority(8);
        return (new Upload())->appUpload();
    }
}