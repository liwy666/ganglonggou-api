<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/11/6
 * Time: 13:30
 */

namespace app\api\service;


use app\lib\exception\CommonException;

class DownloadImage
{

    private $code; // 状态码
    private $message; // 消息
    /**
     * 图片位置的根路径
     * @var string
     */
    private $base_path;

    /**
     * @param $url
     * @return bool|string
     * @throws CommonException
     */
    public function download($url)
    {
        $this->base_path = config('my_config.img_file') . date('Ymd') . '/'; // 将传递的路径，主动拼接上根图片目录
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // 对认证证书来源的检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); // 从证书中检查SSL加密算法是否存在

        $file = curl_exec($ch);
        curl_close($ch);
        if ($file == false) {
            //  图片下载失败
            $this->code = -1;
            $this->message = '图片下载失败';
            throw new CommonException(['msg' => $this->message]);
        }
        //  文件夹时需要添加 / 的
        if (substr($this->base_path, -1, 1) !== '/') {
            $this->base_path = $this->base_path . '/';
        }
        return $this->saveAsImage($url, $file);
    }


    /**
     * @param $url
     * @param $file
     * @return string
     * @throws CommonException
     *
     */
    private function saveAsImage($url, $file)
    {
        //获取图片真实后缀
        $info = @unpack("C2chars", $file);
        $code = intval($info['chars1'] . $info['chars2']);
        switch ($code) {
            case 255216:
                $extension = 'jpg';
                break;
            case 7173:
                $extension = 'gif';
                break;
            case 13780:
                $extension = 'png';
                break;
            case 6677:
                $extension = 'bmp';
                break;
            default:
                $extension = 'jpg';

        }
        // 为图片生成唯一文件名
        $filename = uniqid(microtime(true)) . '.' . $extension;

        //  如果文件夹不存在，则生成
        if (!file_exists($this->base_path)) {
            $make_path = mkdir($this->base_path, 0777, true);
            if (!$make_path) {
                $this->code = -2;
                $this->message = '保存图片时，创建文件夹失败';
                throw new CommonException(['msg' => $this->message]);
            }
        }

        $resource = fopen($this->base_path . $filename, 'a');
        fwrite($resource, $file);
        fclose($resource);


        return date('Ymd') . '/' . $filename;
    }

    /**
     * 获取 message
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * 获取正太吗
     * @return int
     */
    public function getCode()
    {
        return $this->code;
    }
}