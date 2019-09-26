<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/5/15
 * Time: 18:49
 */

namespace app\api\model;


use app\lib\exception\CommonException;
use think\facade\Cache;

class GlGoods extends BaseModel
{

    static private $screenGoodsInfo = 'goods_id,cat_id,goods_sn,goods_name,goods_head_name,
            market_price,shop_price,keywords,goods_brief,goods_desc,goods_stock,click_count,
            goods_img,original_img,sort_order,goods_sales_volume,evaluate_count,
            attribute,is_promote,promote_number,promote_start_date,promote_end_date,
            supplier_id,supplier_name,add_time';//对外筛选后的商品信息

    public function getOriginalImgAttr($value, $data)
    {
        return $this->spellOriginalImg($value, $data);
    }

    public function getGoodsImgAttr($value, $data)
    {
        return $this->spellOriginalImg($value, $data);
    }

    public function getGoodsDescAttr($value, $data)
    {
        return $this->imgTagSpellOriginalImg($value, $data);
    }

    public function getAttributeAttr($value, $data)
    {
        return json_decode($value, true);
    }

    /**
     * @param $parent_id
     * @return mixed
     * @throws CommonException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 通过parent_id返回商品列表
     */
    public static function giveGoodsListByParentId($parent_id)
    {

        $result = Cache::get($parent_id . '_user_goods_list');
        $debug = config('my_config.debug');

        if (!$result || $debug) {
            $cat_id_array_ = GlCategory::where(['parent_id' => $parent_id])
                ->select()
                ->toArray();
            $cat_id_array = [];
            if (count($cat_id_array_) > 0) {
                foreach ($cat_id_array_ as $k => $v) {
                    array_push($cat_id_array, $v['cat_id']);
                }
            } else {
                throw new CommonException(['msg' => '无效的顶级分类']);
            }

            $where = [];
            array_push($where, ['cat_id', 'in', $cat_id_array]);
            array_push($where, ['is_on_sale', '=', 1]);
            array_push($where, ['is_del', '=', 0]);

            $result = self::where($where)
                ->field(self::$screenGoodsInfo)
                ->order(['sort_order' => 'desc'])
                ->select()
                ->toArray();

            foreach ($result as $k => $v) {
                foreach ($cat_id_array_ as $k2 => $v2) {
                    if ($v['cat_id'] === $v2['cat_id']) {
                        $result[$k]['cat_name'] = $v2['cat_name'];
                    }
                }
            }
            Cache::set($parent_id . '_user_goods_list', $result, config('my_config.sql_sel_cache_time'));
        }

        return $result;
    }

    /**
     * @param $supplier_id
     * @param $parent_id
     * @param $number
     * @return array|\PDOStatement|string|\think\Collection
     * @throws CommonException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 根据supplierID返回商品列表
     */
    public static function giveGoodsListBySupplierId($supplier_id, $parent_id, $number)
    {
        /*      return self::where([
                  ['supplier_id', '=', $supplier_id],
                  ['is_del', '=', 0],
                  ['is_on_sale', '=', 1],
              ])
                  ->order(['click_count' => 'desc'])
                  ->field(self::$screenGoodsInfo)
                  ->limit($number)
                  ->select();*/

        //先进行排序
        $goods_list = self::giveGoodsListByParentId($parent_id);
        for ($i = 0; $i < count($goods_list); $i++)
            // 第二层为从$i+1的地方循环到数组最后
            for ($j = $i + 1; $j < count($goods_list); $j++) {
                // 比较数组中两个相邻值的大小
                if ($goods_list[$i]["click_count"] < $goods_list[$j]["click_count"]) {
                    $tem = $goods_list[$i]; // 这里临时变量，存贮$i的值
                    $goods_list[$i] = $goods_list[$j]; // 第一次更换位置
                    $goods_list[$j] = $tem; // 完成位置互换
                }
            }
        $goods_list_ = [];
        //再进行筛选
        foreach ($goods_list as $k => $v) {
            if ($v["supplier_id"] == $supplier_id) {
                array_push($goods_list_, $v);
            }
        }
        $goods_list_ = array_slice($goods_list_, 0, $number);

        return $goods_list_;
    }


    /**
     * @param $goods_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 返回筛选过后商品信息
     */
    public
    static function giveScreenGoodsInfo($goods_id)
    {

        $where['goods_id'] = $goods_id;
        $where['is_on_sale'] = 1;
        $where['is_del'] = 0;

        $result = GlGoods::where($where)->field(self::$screenGoodsInfo)
            ->find();

        return $result;
    }
}