<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/5/17
 * Time: 9:04
 */

namespace app\api\controller\v1\cms;


use app\api\model\GlIndexAd;
use app\api\service\UserAuthority;
use app\api\validate\CurrencyValidate;

class CmsIndexAd
{
    public function giveIndexAdList()
    {

        //验证必要
        (new CurrencyValidate())->myGoCheck(['into_type'], 'require');
        UserAuthority::checkAuthority(8);
        $where['into_type'] = request()->param('into_type');

        $result['list'] = GlIndexAd::where($where)->order(['position_type','sort_order'=>'desc'])->select();
        $result['count'] = GlIndexAd::where($where)->count();

        return $result;

    }

    /**
     * @return bool
     * @throws \app\lib\exception\CommonException
     * 添加广告
     */
    public function addIndexAd()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['into_type', 'position_type', 'ad_type','sort_order'], 'require');
        (new CurrencyValidate())->myGoCheck(['sort_order'], 'positiveInt');
        $data['into_type'] = request()->param('into_type');
        $data['position_type'] = request()->param('position_type');
        $data['ad_type'] = request()->param('ad_type');
        $data['sort_order'] = request()->param('sort_order');
        $data['ad_img'] = $this->removeImgUrl(request()->param('ad_img'));
        //第二次验证
        if ($data['ad_type'] === '商品ID') {
            (new CurrencyValidate())->myGoCheck(['goods_id'], 'require');
            (new CurrencyValidate())->myGoCheck(['goods_id'], 'positiveInt');
            $data['goods_id'] = request()->param('goods_id');
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
    public function updIndexAd(){

        //验证必要
        (new CurrencyValidate())->myGoCheck(['into_type', 'position_type', 'ad_type','sort_order','id'], 'require');
        (new CurrencyValidate())->myGoCheck(['sort_order','id'], 'positiveInt');
        UserAuthority::checkAuthority(8);

        $where['id'] = request()->param('id');
        $data['into_type'] = request()->param('into_type');
        $data['position_type'] = request()->param('position_type');
        $data['ad_type'] = request()->param('ad_type');
        $data['sort_order'] = request()->param('sort_order');
        $data['ad_img'] = $this->removeImgUrl(request()->param('ad_img'));
        //第二次验证
        if ($data['ad_type'] === '商品ID') {
            (new CurrencyValidate())->myGoCheck(['goods_id'], 'require');
            (new CurrencyValidate())->myGoCheck(['goods_id'], 'positiveInt');
            $data['goods_id'] = request()->param('goods_id');
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
    public function delIndexAd(){
        //验证必要
        (new CurrencyValidate())->myGoCheck(['id'], 'require');
        (new CurrencyValidate())->myGoCheck(['id'], 'positiveInt');
        UserAuthority::checkAuthority(8);

        $data['id'] = request()->param('id');

        GlIndexAd::where($data)->delete();

        return true;
    }

    /**
     * @param $file
     * @return mixed
     * 去除图片中的url
     */
    private function removeImgUrl($file)
    {

        if (strpos($file, config('my_config.img_url')) >= 0) {

            return str_replace(config('my_config.img_url'), '', $file);

        } else {
            return $file;
        }

    }
}