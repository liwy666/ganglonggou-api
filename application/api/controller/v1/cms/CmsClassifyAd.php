<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/6/13
 * Time: 13:49
 */

namespace app\api\controller\v1\cms;


use app\api\model\GlClassifyAd;
use app\api\service\UserAuthority;
use app\api\validate\CurrencyValidate;

class CmsClassifyAd
{
    /**
     * @return mixed
     * @throws \app\lib\exception\CommonException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 分页数据
     */
    public function giveListByPage()
    {

        //验证必要
        (new CurrencyValidate())->myGoCheck(['into_type', 'page', 'limit'], 'require');
        UserAuthority::checkAuthority(8);
        $where['into_type'] = request()->param('into_type');
        $data['page'] = request()->param('page');
        $data['limit'] = request()->param('limit');
        $result['list'] = GlClassifyAd::where($where)
            ->page($data['page'], $data['limit'])
            ->order(['parent_id', 'sort_order' => 'desc'])
            ->select();
        $result['count'] = GlClassifyAd::where($where)->count();

        return $result;

    }

    /**
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \app\lib\exception\CommonException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 返回所有顶级分类
     */
    public function giveParentClassify()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['into_type'], 'require');
        UserAuthority::checkAuthority(8);

        return GlClassifyAd::where([
            ['parent_id', '=', 0],
            ['into_type', '=', request()->param('into_type')]
        ])
            ->select();

    }

    /**
     * @return bool
     * @throws \app\lib\exception\CommonException
     * 添加
     */
    public function addClassify()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['classify_name', 'into_type', 'parent_id', 'sort_order','click_type'], 'require');
        //验证正整数
        (new CurrencyValidate())->myGoCheck(['sort_order'], 'positiveInt');
        UserAuthority::checkAuthority(8);
        $data['classify_name'] = request()->param('classify_name');
        $data['into_type'] = request()->param('into_type');
        $data['click_type'] = request()->param('click_type');
        $data['parent_id'] = request()->param('parent_id');
        $data['sort_order'] = request()->param('sort_order');
        $data['logo_img'] = removeImgUrl(request()->param('logo_img'));
        $data['bar_img'] = removeImgUrl(request()->param('bar_img'));
        $data['sort_order'] = request()->param('sort_order');
        $data['key_word'] = request()->param('key_word');
        $data['goods_id'] = request()->param('goods_id');

        GlClassifyAd::create($data);

        return true;

    }

    /**
     * @return bool
     * @throws \app\lib\exception\CommonException
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 修改
     */
    public function updClassify()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['classify_name', 'into_type', 'parent_id', 'sort_order', 'id'], 'require');
        //验证正整数
        (new CurrencyValidate())->myGoCheck(['sort_order', 'id'], 'positiveInt');
        UserAuthority::checkAuthority(8);
        $data['classify_name'] = request()->param('classify_name');
        $data['into_type'] = request()->param('into_type');
        $data['click_type'] = request()->param('click_type');
        $data['parent_id'] = request()->param('parent_id');
        $data['sort_order'] = request()->param('sort_order');
        $data['logo_img'] = removeImgUrl(request()->param('logo_img'));
        $data['bar_img'] = removeImgUrl(request()->param('bar_img'));
        $data['sort_order'] = request()->param('sort_order');
        $data['key_word'] = request()->param('key_word');
        $data['goods_id'] = request()->param('goods_id');

        GlClassifyAd::where([
            ['id', '=', request()->param('id')]
        ])
            ->update($data);

        return true;
    }

    /**
     * @return bool
     * @throws \app\lib\exception\CommonException
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 删除
     */
    public function delClassify()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['id'], 'require');
        //验证正整数
        (new CurrencyValidate())->myGoCheck(['id'], 'positiveInt');

        UserAuthority::checkAuthority(8);

        $data['id'] = request()->param('id');

        GlClassifyAd::where($data)->delete();

        return true;
    }

    public function manualAddClassify()
    {


        $array_data = json_decode(request()->param('data'), true);

        foreach ($array_data as $k => $v) {
            GlClassifyAd::create($v);
        }

        return true;
    }
}