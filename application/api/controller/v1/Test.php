<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/5/15
 * Time: 11:18
 */

namespace app\api\controller\v1;


use app\api\controller\v1\common\Index;
use app\api\model\GlAfterSale;
use app\api\model\GlArticleSignUser;
use app\api\model\GlCategory;
use app\api\model\GlGoods;
use app\api\model\GlGoodsSku;
use app\api\model\GlIndexAd;
use app\api\model\GlIntoCount;
use app\api\model\GlOrder;
use app\api\model\Test1;
use app\api\model\Test2;
use app\api\service\DownloadImage;
use app\api\service\Login\BaseLogin;
use app\api\service\OrderPayment\IcbcTest;
use app\api\service\OrderPayment\PcAliPayment;
use app\api\service\SerAfterSale;
use app\api\service\SerEmail;
use app\api\validate\CurrencyValidate;
use app\lib\exception\CommonException;
use EasyWeChat\Factory;
use Naixiaoxin\ThinkWechat\Facade;
use Noodlehaus\Config;
use think\Controller;
use think\Db;

class Test extends Controller
{
    public function test()
    {
        $data = GlIndexAd::where(['into_type' => 'double_eleven', 'is_on_sale' => 1])
            ->order(['position_type', 'sort_order' => 'desc'])
            ->select()->toArray();
        for ($i = 0; $i < count($data); $i++) {
            $data[$i] = $this->byKeyrRemoveArrVal($data[$i], 'id');
            $data[$i]['into_type'] = 'double_eleven_burst';
            $data[$i]['click_count'] = 0;
            $data[$i]['ad_img'] = removeImgUrl($data[$i]['ad_img']);
        }
        GlIndexAd::insertAll($data);
        return $data;

    }


    /**
     * @return bool
     * @throws CommonException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     *创建zip
     */
    private function createZip()
    {
        /*获取数据*/
        $index_ad_date = GlIndexAd::giveIndexAdListByIntoType('tmt_mobile')->toArray();
        /*将图片添加到zip*/
        $zip = new \ZipArchive();
        if ($zip->open(config('my_config.public_file') . time() . rand(100, 999) . '.zip', \ZipArchive::CREATE) !== TRUE) {
            throw new CommonException(['msg' => '无效zip路径']);
        }
        if (is_array($index_ad_date)) {
            foreach ($index_ad_date as $key => $value) {
                $img_name = str_replace(config('my_config.img_url'), '', $value['ad_img']);
                $img_file = config('my_config.img_file') . $img_name;
                if (file_exists($img_file)) {
                    $zip->addFile($img_file, 'images/' . $img_name);
                    $index_ad_date[$key]['ad_img'] = './images/' . $img_name;
                }

            }
        }
        /*生成js文件*/
        $data_js_name = config('my_config.public_file') . "temp/data" . time() . rand(100, 999) . ".js";
        $data_js = fopen($data_js_name, "w");
        fwrite($data_js, "var data = '" . json_encode($index_ad_date, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "';");
        fclose($data_js);
        $zip->addFile($data_js_name, 'js/data.js');
        $zip->close();
        //删除临时js
        if (file_exists($data_js_name)) {
            unlink($data_js_name);
        }
        return true;
    }

    /**
     * @return string
     */
    private function sendEmailTest()
    {

        $head = '测试';
        $email_body = '用户支付成功:';
        $v['goods_name'] = 'asdasdsa';
        $v['goods_id'] = 'asdasdsa';
        $v['goods_number'] = 10;
        $v['sku_id'] = 'asdasdsa';
        $v['sku_desc'] = 'asdasdsa';
        $sku_info['sku_stock'] = 10;
        $email_body .= '
            (商品名称:' . $v['goods_name'] .
            ',商品id:' . $v['goods_id'] .
            ',购买数量:' . $v['goods_number'] .
            ',SkuId:' . $v['sku_id'] .
            ',属性详情:' . $v['sku_desc'] .
            ',剩余库存:' . ($sku_info['sku_stock'] - $v['goods_number']) .
            ',库存检测结果:库存充足)';

        $address_array = ['987303897@qq.com', '582870246@qq.com'];

        (new SerEmail())->sendEmail($head, $email_body, $address_array);

        return $email_body;

    }

    /**
     * @return string
     * 工行测试
     */
    public function ghTest()
    {
        $tranData = array(
            'interfaceName' => 'ICBC_WAPB_B2C',
            'interfaceVersion' => '1.0.0.6',
            'orderInfo' => array(
                'orderDate' => date('YmdHis', time()),
                'orderid' => 'orderid',
                'amount' => '1000',
                'installmentTimes' => '9000',
                'curType' => '001',
                'merID' => '1103EE20175012',//商户代码
                'merAcct' => '1103028809200994418',//商户账号
            ),
            'custom' => array(
                'verifyJoinFlag' => 0,
                'Language' => '',
            ),
            'message' => array(
                'goodsID' => '',
                'goodsName' => '商品名称',
                'goodsNum' => 1,
                'carriageAmt' => '',
                'merHint' => '',
                'remark1' => '',
                'remark2' => '',
                'merURL' => "回调地址",
                'merVAR' => 'test',
                'notifyType' => 'HS',
                'resultType' => '0',
            ),
        );
        $xml = self::coverParamsToXml($tranData);
        $htmlParams = array(
            'tranData' => 'tranData',
            'merSignMsg' => 'merSignMsg',
            'merCert' => 'merCert',
            'clientType' => 'clientType'
        );
        $xml = self::createHtml($htmlParams);
        return $xml;
    }

    /**
     * @param $params
     * @return string
     * 工行转xml
     */
    public static function coverParamsToXml($params)
    {
        $sign_str = '<?xml version="1.0" encoding="GBK" standalone="no"?>';
        $sign_str .= '<B2CReq>';
        foreach ($params as $key => $val) {
            if (!is_array($val)) {
                $sign_str .= sprintf('<%s>%s</%s>', $key, $val, $key);
            } else {
                $sign_str .= '<' . $key . '>';
                foreach ($val as $key2 => $val2) {
                    if (!is_array($val2)) {
                        $sign_str .= sprintf('<%s>%s</%s>', $key2, $val2, $key2);
                    } else {
                        $sign_str .= '<' . $key2 . '>';
                        if ($key2 == 'subOrderInfoList') {
                            foreach ($val2 as $key3 => $val3) {
                                if (is_array($val3) && is_numeric($key3)) {
                                    $sign_str .= '<' . 'subOrderInfo' . '>';
                                    foreach ($val3 as $key4 => $val4) {
                                        $sign_str .= sprintf('<%s>%s</%s>', $key4, $val4, $key4);
                                    }
                                    $sign_str .= '</' . 'subOrderInfo' . '>';
                                }
                            }
                        }
                        $sign_str .= '</' . $key2 . '>';
                    }
                }

                $sign_str .= '</' . $key . '>';
            }
        }
        $sign_str .= '</B2CReq>';
        return strval($sign_str);
    }

    /**
     * @param $params
     * @return string
     * 工行转HTML
     */
    public static function createHtml($params)
    {
        $init = array(
            'interfaceName' => 'ICBC_WAPB_B2C',
            'interfaceVersion' => '1.0.0.6'
        );
        $params = array_merge($init, $params);
        $action = 'https://mywap2.icbc.com.cn/ICBCWAPBank/servlet/ICBCWAPEBizServlet';

        $encodeType = isset ($params ['encoding']) ? $params ['encoding'] : 'UTF-8';
        $var = '';
        $html = <<<eot
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset={$encodeType}" />
</head>
<body  onload="javascript:document.pay_form.submit();">
    <form id="pay_form" name="pay_form" action="{$action}" method="post">
eot;
        foreach ($params as $key => $value) {
            $html .= "    <input type=\"hidden\" name=\"{$key}\" id=\"{$key}\" value=\"{$value}\" />\n";
            $var .= $key . ':' . $value . "\n";
        }
        $html .= <<<eot
    <input type="submit" value="稍等，支付跳转跳..." style="display: none;" >
    </form>
</body>
</html>
eot;
        file_put_contents('test.txt', $html);
        return $html;
    }

    protected function iniTest()
    {
        $conf = Config::load('C:\Users\administrator_liwy\Desktop\web\API\ganglonggou-api\main\application\api\controller\v1\test.ini');

        $conf['MerchantKeyStoreType'] = '1';

        return $conf->get('MerchantKeyStoreType');
    }

    private function returnHtmlTest()
    {

        $goods_list['g1'] = GlIndexAd::where([
            'into_type' => '3c618pc',
            'position_type' => '券享最惠，品悦好物-商品',
        ])
            ->order(['position_type', 'sort_order' => 'desc'])
            ->select();
        $goods_list['g2'] = GlIndexAd::where([
            'into_type' => '3c618pc',
            'position_type' => '手机数码专区',
            'position_type2' => '内容',
        ])
            ->order(['position_type', 'sort_order' => 'desc'])
            ->select();
        $goods_list['g3'] = GlIndexAd::where([
            'into_type' => '3c618pc',
            'position_type' => '电脑办公专区',
            'position_type2' => '内容',
        ])
            ->order(['position_type', 'sort_order' => 'desc'])
            ->select();

        $test = view('/3cPc')->assign('goods_list', $goods_list);

        return $test;

    }

    /**
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * 手动删除重复的sku
     */
    private function delSku()
    {
        $goods_array = GlGoods::where(['is_del' => 0])
            ->field('goods_id,market_price,shop_price,attribute')
            ->select()
            ->toArray();
        $del_suk_count = [];//需要删除的sku数组
        foreach ($goods_array as $index => $goods_info) {
            $goods_sku_info_array = GlGoodsSku::where(['goods_id' => $goods_info['goods_id']])->select()->toArray();
            if (count($this->get2DRepeat($goods_sku_info_array, ['sku_desc'])) > 0) {
                array_push($del_suk_count, ['goods_id' => $goods_info['goods_id'], $this->get2DRepeat($goods_sku_info_array, ['sku_desc'])]);
            }
        }

        //开始删除重复的sku
        foreach ($del_suk_count as $k => $v) {
            foreach ($v[0] as $k2 => $v2) {
                GlGoodsSku::where(['goods_id' => $v['goods_id'], 'sku_id' => $v2['sku_id']])
                    ->delete();
            }

        }
        return $del_suk_count;
    }

    /**
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 手动添加sku
     */
    private function addSku()
    {
        $goods_array = GlGoods::where(['is_del' => 0])
            ->field('goods_id,market_price,shop_price,attribute')
            ->select()
            ->toArray();
        $count = 0;
        foreach ($goods_array as $index => $goods_info) {
            $attribute = $goods_info['attribute'];
            $attribute_array = [];
            foreach ($attribute as $k => $v) {
                array_push($attribute_array, $v['attribute_value']);
            }

            $sku_array_ = $this->dikaer($attribute_array);

            $sku_info = GlGoodsSku::where(['goods_id' => $goods_info['goods_id']])
                ->order('sku_id')
                ->select()
                ->toArray();

            $sku_array = [];
            foreach ($sku_info as $k => $v) {
                array_push($sku_array, $v['sku_desc']);
            }

            $sku_desc_array = array_merge(array_diff($sku_array_, $sku_array), array_diff($sku_array, $sku_array_));


            if (count($sku_desc_array) > 0) {
                foreach ($sku_desc_array as $index2 => $value2) {
                    $data['goods_id'] = $goods_info['goods_id'];
                    $data['sku_stock'] = 0;
                    $data['sku_shop_price'] = $goods_info['shop_price'];
                    $data['sku_market_price'] = $goods_info['market_price'];
                    $data['goods_id'] = $goods_info['goods_id'];
                    $data['give_integral'] = 0;
                    $data['integral'] = 0;
                    $data['img_url'] = removeImgUrl($sku_info[0]['img_url']);
                    $data['original_img_url'] = removeImgUrl($sku_info[0]['original_img_url']);
                    $data['sku_desc'] = $value2;
                    GlGoodsSku::create($data);
                    $count++;
                }

            }

        }

        return $count;

    }

    /**
     * @param $arr
     * @return array|mixed
     * 笛卡乘积
     */
    private function dikaer($arr)
    {
        //$arr1 = array();
        $result = array_shift($arr);
        while ($arr2 = array_shift($arr)) {
            $arr1 = $result;
            $result = array();
            foreach ($arr1 as $v) {
                foreach ($arr2 as $v2) {
                    $result[] = $v . ',' . $v2;
                }
            }
        }
        return $result;
    }


    /**
     * @param $arr
     * @return array
     * 获取数组重复项
     */
    private function getRepeat($arr)
    {
        // 获取去掉重复数据的数组
        $unique_arr = array_unique($arr);
        // 获取重复数据的数组
        $repeat_arr = array_diff_assoc($arr, $unique_arr);

        return $repeat_arr;
    }

    /**
     * @param $arr
     * @param $keys
     * @return array
     * 2维数组重复项
     */
    private function get2DRepeat($arr, $keys)
    {
        $unique_arr = array();
        $repeat_arr = array();
        foreach ($arr as $k => $v) {
            $str = "";
            foreach ($keys as $a => $b) {
                $str .= "{$v[$b]},";
            }
            if (!in_array($str, $unique_arr)) {
                $unique_arr[] = $str;
            } else {
                $repeat_arr[] = $v;
            }
        }
        return $repeat_arr;
    }

    /**
     * @param $arr
     * @param $key
     * @return mixed
     * 根据键删除数组项
     */
    private function byKeyrRemoveArrVal($arr, $key)
    {
        if (!array_key_exists($key, $arr)) {
            return $arr;
        }
        $keys = array_keys($arr);
        $index = array_search($key, $keys);
        if ($index !== FALSE) {
            array_splice($arr, $index, 1);
        }
        return $arr;
    }

    /**
     * @return bool
     * 事务测试
     */
    private function testDb()
    {

        Db::transaction(function () {

            $data1['zd1'] = 1;
            $data1['zd2'] = 1;
            $data1['zd3'] = 1;
            $data1['zd4'] = 1;

            Test1::create($data1);

            $data2['zd1'] = 1;
            $data2['zd2'] = 1;
            $data2['zd3'] = 1;

            Test2::create($data2);

        });
        return true;

    }
}