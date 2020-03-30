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

        $result['list'] = GlCategory::page($data['page'], $data['limit'])
            ->order('cat_id desc')
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
            $cat_id_str = implode(',',$cat_id_array);
            //删除这些子分类
            GlCategory::where('cat_id','exp','IN('.$cat_id_str.')')
            ->delete();
            //删除这些子分类下的商品
            GlGoods::where('cat_id','exp','IN('.$cat_id_str.')')
                ->update(['is_del'=>1]);
        }
        //删除该分类
        GlCategory::where($data)
            ->delete();
        //删除改分类下商品
        GlGoods::where($data)
            ->update(['is_del'=>1]);

        return true;
    }

}