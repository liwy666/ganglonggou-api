<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/5/15
 * Time: 20:10
 */

namespace app\api\controller\v1\cms;


use app\api\model\GlCategory;
use app\api\model\GlGoods;
use app\api\service\UserAuthority;
use app\api\validate\CurrencyValidate;
use app\lib\exception\CommonException;

class CmsCat
{
    /**
     * @return mixed
     * @throws CommonException
     * 返回所以分类
     */
    public function giveAllCat()
    {
        UserAuthority::checkAuthority(8);
        $result = GlCategory::select();
        return $result;

    }

    /**
     * @return mixed
     * @throws CommonException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 分页获取分类
     */
    public function giveCatListByPage()
    {

        UserAuthority::checkAuthority(8);
        //验证必要
        (new CurrencyValidate())->myGoCheck(['page', 'limit'], 'require');
        //验证正整数
        (new CurrencyValidate())->myGoCheck(['page', 'limit'], 'positiveInt');

        $data['page'] = request()->param('page');
        $data['limit'] = request()->param('limit');

        $result['list'] = GlCategory::join('gl_category p_gl_category', 'gl_category.parent_id = p_gl_category.cat_id')
            ->page($data['page'], $data['limit'])
            ->order('gl_category.cat_id desc')
            ->field('gl_category.cat_id,gl_category.parent_id,p_gl_category.cat_name as parent_name,gl_category.cat_name')
            ->select();

        $result['count'] = GlCategory::count();

        return $result;
    }


    /**
     * @return bool
     * @throws CommonException
     * 添加分类
     */
    public function addCat()
    {
        UserAuthority::checkAuthority(8);
        (new CurrencyValidate())->myGoCheck(['cat_name', 'parent_id', 'sort_order'], 'require');
        (new CurrencyValidate())->myGoCheck(['sort_order'], 'positiveInt');

        $data['cat_name'] = request()->param('cat_name');
        $data['parent_id'] = request()->param('parent_id');
        $data['sort_order'] = request()->param('sort_order');

        GlCategory::create($data);

        return true;
    }

    /**
     * @return bool
     * @throws CommonException
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 编辑分类
     */
    public function updCat()
    {
        UserAuthority::checkAuthority(8);
        (new CurrencyValidate())->myGoCheck(['cat_name', 'parent_id', 'sort_order', 'cat_id'], 'require');
        (new CurrencyValidate())->myGoCheck(['sort_order', 'cat_id'], 'positiveInt');

        $where['cat_id'] = request()->param('cat_id');
        $data['cat_name'] = request()->param('cat_name');
        $data['parent_id'] = request()->param('parent_id');
        $data['sort_order'] = request()->param('sort_order');

        $upd_number = GlCategory::where($where)
            ->update($data);

        if ($upd_number > 0) {
            return true;
        } else {
            throw new CommonException(['msg' => '编辑分类失败']);
        }

    }

    /**
     * @return bool
     * @throws CommonException
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * 删除分类
     */
    public function delCat()
    {
        UserAuthority::checkAuthority(8);
        (new CurrencyValidate())->myGoCheck(['cat_id'], 'require');
        (new CurrencyValidate())->myGoCheck(['cat_id'], 'positiveInt');

        $data['cat_id'] = request()->param('cat_id');

        //获取子分类（如果有）
        $cat_id_array_ = GlCategory::where(['parent_id' => $data['cat_id']])
            ->select()
            ->toArray();
        if (count($cat_id_array_) > 0) {
            $cat_id_array = [];
            foreach ($cat_id_array_ as $k => $v) {
                array_push($cat_id_array, $v['cat_id']);
            }
            $cat_id_str = implode(',', $cat_id_array);
            //删除这些子分类
            GlCategory::where('cat_id', 'exp', 'IN(' . $cat_id_str . ')')
                ->delete();
            //删除这些子分类下的商品
            GlGoods::where('cat_id', 'exp', 'IN(' . $cat_id_str . ')')
                ->update(['is_del' => 1]);
        }
        //删除该分类
        GlCategory::where($data)
            ->delete();
        //删除改分类下商品
        GlGoods::where($data)
            ->update(['is_del' => 1]);

        return true;
    }

    /**
     * @return string
     * @throws CommonException
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * 转移商品
     */
    public function shearGoodsByCatId()
    {
        UserAuthority::checkAuthority(8);
        (new CurrencyValidate())->myGoCheck(['cat_id', 'aim_cat_id'], 'require');
        (new CurrencyValidate())->myGoCheck(['cat_id', 'aim_cat_id'], 'positiveInt');
        $catId = request()->param('cat_id');
        $aimCatId = request()->param('aim_cat_id');

        $oldCateGory = GlCategory::where([
            'cat_id' => $catId,
        ])->find();

        $aimCateGory = GlCategory::where([
            'cat_id' => $aimCatId,
        ])->find();

        //检查原分类和目标分类是否符合规范
        if (!$oldCateGory) {
            throw new CommonException(["msg" => "原分类不存在"]);
        }
        if ($oldCateGory['parent_id'] === 0) {
            throw new CommonException(['msg' => '顶级分类不支持商品转移']);
        }
        if (!$aimCateGory) {
            throw new CommonException(["msg" => "目标分类不存在"]);
        }
        if ($aimCateGory['parent_id'] === 0) {
            throw new CommonException(['msg' => '顶级分类不支持商品转移']);
        }
        //开始转移
        $number = GlGoods::where([
            "is_del" => 0,
            "cat_id" => $oldCateGory['cat_id']
        ])->update([
            "cat_id" => $aimCatId
        ]);

        return "成功转移$number 件商品";

    }

}