<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/5/17
 * Time: 9:04
 */

namespace app\api\controller\v1\cms;


use app\api\model\GlGoods;
use app\api\model\GlIndexAd;
use app\api\model\GlIntoCount;
use app\api\service\Upload\Upload;
use app\api\service\UserAuthority;
use app\api\validate\CurrencyValidate;
use app\lib\exception\CommonException;
use think\facade\Cache;

class CmsIndexAd
{
    public function giveIndexAdList()
    {

        //验证必要
        (new CurrencyValidate())->myGoCheck(['into_type', 'page', 'limit'], 'require');
        UserAuthority::checkAuthority(8);
        $where['into_type'] = request()->param('into_type');
        $data['page'] = request()->param('page');
        $data['limit'] = request()->param('limit');
        $result['list'] = GlIndexAd::where($where)
            ->page($data['page'], $data['limit'])
            ->order(['position_type', 'sort_order' => 'desc'])
            ->select();
        $result['count'] = GlIndexAd::where($where)->count();

        return $result;

    }

    public function giveAllIndexAdList()
    {

        //验证必要
        (new CurrencyValidate())->myGoCheck(['into_type'], 'require');
        UserAuthority::checkAuthority(8);
        $where['into_type'] = request()->param('into_type');

        $result = GlIndexAd::where($where)->order(['position_type', 'sort_order' => 'desc'])
            ->select();

        return $result;

    }

    /**
     * @return bool
     * @throws \app\lib\exception\CommonException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 添加广告
     */
    public function addIndexAd()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['into_type', 'position_type', 'ad_type', 'sort_order'], 'require');
        (new CurrencyValidate())->myGoCheck(['sort_order'], 'positiveInt');
        $data['into_type'] = request()->param('into_type');
        $data['position_type'] = request()->param('position_type');
        $data['ad_type'] = request()->param('ad_type');
        $data['sort_order'] = request()->param('sort_order');
        $data['ad_img'] = removeImgUrl(request()->param('ad_img'));
        $data['position_type_name'] = request()->param('position_type_name');
        $data['text'] = request()->param('text');
        $data['father_position_name'] = request()->param('father_position_name');
        $data['position_type2'] = request()->param('position_type2');
        $data['goods_name'] = request()->param('goods_name');
        $data['goods_price'] = request()->param('goods_price');
        $data['origin_goods_price'] = request()->param('origin_goods_price');
        $data['url'] = request()->param('url');
        $data['pc_url'] = request()->param('pc_url');
        $data['click_count'] = 0;
        $data['is_on_sale'] = 1;

        if (request()->param('is_fixed') == 0 || request()->param('is_fixed') == 1) {
            $data['is_fixed'] = request()->param('is_fixed');
        }

        //第二次验证
        if ($data['ad_type'] === '商品ID') {
            (new CurrencyValidate())->myGoCheck(['goods_id'], 'require');
            (new CurrencyValidate())->myGoCheck(['goods_id'], 'positiveInt');
            $data['goods_id'] = request()->param('goods_id');
            /*去查询出商品名称和商品价格*/
            $goods_info = GlGoods::where([
                ['goods_id', '=', $data['goods_id']]
            ])
                ->find();
            $data['goods_name'] = $goods_info['goods_name'];
            $data['goods_price'] = $goods_info['shop_price'];
            $data['origin_goods_price'] = $goods_info['market_price'];
        }
        if ($data['ad_type'] === '分类ID') {
            (new CurrencyValidate())->myGoCheck(['cat_id'], 'require');
            (new CurrencyValidate())->myGoCheck(['cat_id'], 'positiveInt');
            $data['cat_id'] = request()->param('cat_id');
        }

        GlIndexAd::create($data);

        return true;
    }

    /**
     * @return bool
     * @throws \app\lib\exception\CommonException
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 编辑广告
     */
    public function updIndexAd()
    {

        //验证必要
        (new CurrencyValidate())->myGoCheck(['into_type', 'position_type', 'ad_type', 'sort_order', 'id'], 'require');
        (new CurrencyValidate())->myGoCheck(['sort_order', 'id'], 'positiveInt');
        UserAuthority::checkAuthority(8);

        $where['id'] = request()->param('id');
        $data['into_type'] = request()->param('into_type');
        $data['position_type'] = request()->param('position_type');
        $data['ad_type'] = request()->param('ad_type');
        $data['sort_order'] = request()->param('sort_order');
        $data['ad_img'] = removeImgUrl(request()->param('ad_img'));
        $data['position_type_name'] = request()->param('position_type_name');
        $data['father_position_name'] = request()->param('father_position_name');
        $data['text'] = request()->param('text');
        $data['position_type2'] = request()->param('position_type2');
        $data['goods_name'] = request()->param('goods_name');
        $data['goods_price'] = request()->param('goods_price');
        $data['origin_goods_price'] = request()->param('origin_goods_price');
        $data['url'] = request()->param('url');
        $data['pc_url'] = request()->param('pc_url');


        if (request()->param('is_fixed') == 0 || request()->param('is_fixed') == 1) {
            $data['is_fixed'] = request()->param('is_fixed');
        }

        //第二次验证
        if ($data['ad_type'] === '商品ID') {
            (new CurrencyValidate())->myGoCheck(['goods_id'], 'require');
            (new CurrencyValidate())->myGoCheck(['goods_id'], 'positiveInt');
            $data['goods_id'] = request()->param('goods_id');
            /*去查询出商品名称和商品价格*/
            $goods_info = GlGoods::where([
                ['goods_id', '=', $data['goods_id']]
            ])
                ->find();
            $data['goods_name'] = $goods_info['goods_name'];
            $data['goods_price'] = $goods_info['shop_price'];
            $data['origin_goods_price'] = $goods_info['market_price'];
        }
        if ($data['ad_type'] === '分类ID') {
            (new CurrencyValidate())->myGoCheck(['cat_id'], 'require');
            (new CurrencyValidate())->myGoCheck(['cat_id'], 'positiveInt');
            $data['cat_id'] = request()->param('cat_id');
        }

        //更新
        GlIndexAd::where($where)->update($data);

        return true;


    }

    /**
     * @return bool
     * @throws \app\lib\exception\CommonException
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 删除广告
     */
    public function delIndexAd()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['id'], 'require');
        (new CurrencyValidate())->myGoCheck(['id'], 'positiveInt');
        UserAuthority::checkAuthority(8);

        $data['id'] = request()->param('id');

        GlIndexAd::where($data)->delete();

        return true;
    }

    /**
     * @return mixed
     * @throws \app\lib\exception\CommonException
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 修改广告图片
     */
    public function updImg()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['id'], 'require');
        (new CurrencyValidate())->myGoCheck(['id'], 'positiveInt');
        $id = request()->param('id');

        $img_info = (new Upload())->ImgUpload();

        GlIndexAd::where([
            ['id', '=', $id]
        ])
            ->update([
                'ad_img' => removeImgUrl($img_info['goods_img'])
            ]);

        return $img_info;
    }

    /**
     * @return bool
     * @throws \app\lib\exception\CommonException
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 简单修改
     */
    public function easeUpdIndexAd()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['goods_price', 'goods_name', 'sort_order', 'id', 'url'], 'require');
        (new CurrencyValidate())->myGoCheck(['sort_order', 'id'], 'positiveInt');
        UserAuthority::checkAuthority(8);

        $where['id'] = request()->param('id');
        $data['sort_order'] = request()->param('sort_order');
        $data['goods_name'] = request()->param('goods_name');
        $data['goods_price'] = request()->param('goods_price');
        if (request()->param('origin_goods_price')) {
            $data['origin_goods_price'] = request()->param('origin_goods_price');
        }
        if (request()->param('is_fixed') == 0 || request()->param('is_fixed') == 1) {
            $data['is_fixed'] = request()->param('is_fixed');
        }
        if (request()->param('pc_url')) {
            $data['pc_url'] = request()->param('pc_url');
        }
        $data['url'] = request()->param('url');

        //更新
        GlIndexAd::where($where)->update($data);

        return true;
    }


    public function giveIntoCountList()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['into_type_array', 'number'], 'require');
        (new CurrencyValidate())->myGoCheck(['number'], 'positiveInt');
        UserAuthority::checkAuthority(8);

        $into_type_array = request()->param('into_type_array/a');
        $number = request()->param('number');
        $data_array = [];

        foreach ($into_type_array as $k => $v) {
            $data_array[$k]['name'] = $v;
            $data_array[$k]['data'] = GlIntoCount::where([
                ['into_type', '=', $v]
            ])
                ->order('into_date desc')
                ->limit($number)
                ->select();

        }

        return $data_array;
    }

    /**
     * @return bool
     * @throws CommonException
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 下架商品
     */
    public function endOfSaleIndexAd()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['id'], 'require');
        //验证正整数
        (new CurrencyValidate())->myGoCheck(['id'], 'positiveInt');

        UserAuthority::checkAuthority(8);
        $data['id'] = request()->param('id');

        //根据商品id删除商品
        $upd_number = GlIndexAd::where($data)->update(['is_on_sale' => 0]);
        if ($upd_number < 1) {
            throw new CommonException(['msg' => '下架失败']);
        }

        return true;

    }

    /**
     * @return bool
     * @throws CommonException
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 上架商品
     */
    public function allowSaleIndexAd()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['id'], 'require');
        //验证正整数
        (new CurrencyValidate())->myGoCheck(['id'], 'positiveInt');

        UserAuthority::checkAuthority(8);
        $data['id'] = request()->param('id');

        //根据商品id删除商品
        $upd_number = GlIndexAd::where($data)->update(['is_on_sale' => 1]);
        if ($upd_number < 1) {
            throw new CommonException(['msg' => '上架失败']);
        }

        return true;

    }

    /**
     * @return string
     * @throws CommonException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取数据压缩包
     */
    public function getDataZipUrl()
    {

        //验证必要
        (new CurrencyValidate())->myGoCheck(['into_type'], 'require');
        //UserAuthority::checkAuthority(8);
        $into_type = request()->param('into_type');

        /*获取数据*/
        $index_ad_date = GlIndexAd::giveIndexAdListByIntoType($into_type)->toArray();
        /*将图片添加到zip*/
        $zip = new \ZipArchive();
        $zip_name = time() . rand(100, 999) . '.zip';
        $zip_file_name = config('my_config.public_file') . $zip_name;
        if ($zip->open($zip_file_name, \ZipArchive::CREATE) !== TRUE) {
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
            /*生成js文件*/
            $data_js_name = config('my_config.public_file') . "temp/data" . time() . rand(100, 999) . ".js";
            $data_js = fopen($data_js_name, "w");
            fwrite($data_js, "gl_data = '" . json_encode($index_ad_date, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "';");
            fclose($data_js);
            $zip->addFile($data_js_name, 'js/gl_data.js');
            $zip->close();
            //删除临时js
            if (file_exists($data_js_name)) {
                unlink($data_js_name);
            }
        }


        return config('my_config.api_url') . '/download/' . $zip_name;
    }
}
