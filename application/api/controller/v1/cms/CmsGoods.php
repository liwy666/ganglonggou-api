<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/5/15
 * Time: 18:51
 */

namespace app\api\controller\v1\cms;


use app\api\model\GlCategory;
use app\api\model\GlGoods;
use app\api\model\GlGoodsGallery;
use app\api\model\GlGoodsSku;
use app\api\model\GlSupplier;
use app\api\service\UserAuthority;
use app\api\validate\CurrencyValidate;
use app\lib\exception\CommonException;
use think\Db;

class CmsGoods
{
    /**
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \app\lib\exception\CommonException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 分页获取商品列表
     */
    public function giveGoodsListByPage()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['page', 'limit'], 'require');
        //验证正整数
        (new CurrencyValidate())->myGoCheck(['page', 'limit'], 'positiveInt');
        UserAuthority::checkAuthority(8);
        $data['page'] = request()->param('page');
        $data['limit'] = request()->param('limit');
        $where = [['is_del', '=', 0]];
        if (request()->param('goods_name') !== '') {
            array_push($where, ['goods_name', 'like', '%' . request()->param('goods_name') . '%']);
        }
        if (request()->param('cat_id') !== '') {
            array_push($where, ['cat_id', '=', request()->param('cat_id')]);
        }
        /*        $where['goods_name'] = request()->param('goods_name') !== '' ? request()->param('goods_name') : array('exp', Db::raw('is not null'));
                $where['cat_id'] = request()->param('cat_id') !== '' ? request()->param('cat_id') : array('exp', Db::raw('is not null'));*/
        $result['list'] = GlGoods::where($where)
            ->page($data['page'], $data['limit'])
            ->order('goods_id desc')
            ->select();

        $result['count'] = GlGoods::where($where)
            ->count();

        return $result;
    }

    /**
     * @return bool
     * @throws CommonException
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 添加商品
     */
    public function addGoods()
    {

        //验证必要
        (new CurrencyValidate())->myGoCheck(['cat_id', 'goods_name', 'promote_number', 'promote_start_date'
            , 'promote_end_date', 'goods_img', 'original_img', 'is_on_sale', 'click_type'
            , 'is_best', 'is_new', 'is_hot', 'is_promote', 'goods_sales_volume', 'evaluate_count', 'attribute', 'goods_gallery', 'goods_sku_array'], 'require');
        //验证正整数
        (new CurrencyValidate())->myGoCheck(['cat_id', 'supplier_id'], 'positiveInt');

        UserAuthority::checkAuthority(8);

        $data['cat_id'] = request()->param('cat_id');
        $data['goods_name'] = request()->param('goods_name');
        $data['goods_head_name'] = request()->param('goods_head_name');
        $data['click_count'] = 0;
        $data['click_type'] = request()->param('click_type');
        $data['promote_number'] = request()->param('promote_number');
        $data['promote_start_date'] = request()->param('promote_start_date');
        $data['promote_end_date'] = request()->param('promote_end_date');
        $data['keywords'] = request()->param('keywords');
        $data['goods_brief'] = request()->param('goods_brief');
        $data['goods_desc'] = removeImgUrl(request()->param('goods_desc'));
        $data['goods_img'] = removeImgUrl(request()->param('goods_img'));
        $data['original_img'] = removeImgUrl(request()->param('original_img'));
        $data['is_on_sale'] = request()->param('is_on_sale');
        $data['add_time'] = time();
        $data['sort_order'] = 99;
        $data['is_del'] = 0;
        $data['is_best'] = request()->param('is_best');
        $data['is_new'] = request()->param('is_new');
        $data['is_hot'] = request()->param('is_hot');
        $data['is_promote'] = request()->param('is_promote');
        $data['upd_time'] = time();
        $data['seller_note'] = '';
        $data['goods_sales_volume'] = request()->param('goods_sales_volume');
        $data['evaluate_count'] = request()->param('evaluate_count');
        $data['attribute'] = json_encode(request()->param('attribute/a'));

        $goods_gallery_array = request()->param('goods_gallery/a');
        $goods_sku_array = request()->param('goods_sku_array/a');

        $data['goods_stock'] = $this->countGoodsStock($goods_sku_array);
        $data['market_price'] = $this->mpMarketPrice($goods_sku_array);
        $data['shop_price'] = $this->mpShopPrice($goods_sku_array);

        //插入供应商名称
        $data['supplier_id'] = request()->param('supplier_id');
        $supplier_info = GlSupplier::where([
            'id' => request()->param('supplier_id'),
            'is_del' => 0
        ])
            ->find();

        if (!$supplier_info) {
            throw new CommonException(['msg' => '无效供应商']);
        }

        $data['supplier_name'] = $supplier_info['supplier_name'];

        //插入商品
        $goods_info = GlGoods::create($data);

        if ($goods_info) {
            //生成商品相册
            foreach ($goods_gallery_array as $k => $v) {
                $data_goods_gallery['goods_id'] = $goods_info->id;
                $data_goods_gallery['img_url'] = removeImgUrl($v['url']);
                $data_goods_gallery['img_original'] = removeImgUrl($v['original_url']);
                GlGoodsGallery::create($data_goods_gallery);
            }
            //生成sku
            foreach ($goods_sku_array as $k => $v) {
                $data_goods_sku['goods_id'] = $goods_info->id;
                $data_goods_sku['sku_desc'] = $v['sku_desc'];
                $data_goods_sku['sku_stock'] = $v['sku_stock'];
                $data_goods_sku['sku_shop_price'] = $v['sku_shop_price'];
                $data_goods_sku['sku_market_price'] = $v['sku_market_price'];
                $data_goods_sku['give_integral'] = $v['give_integral'];
                $data_goods_sku['integral'] = $v['integral'];
                $data_goods_sku['img_url'] = removeImgUrl($v['img_url']);
                $data_goods_sku['original_img_url'] = removeImgUrl($v['original_img_url']);
                GlGoodsSku::create($data_goods_sku);
            }
            //生成货号
            $this->addGoodsSn($goods_info->id);

        } else {
            throw new CommonException(['msg' => '保存失败']);
        }

        return true;

    }


    /**
     * @return bool
     * @throws CommonException
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * 通过顶级分类复制商品
     */
    public function copyGoodsByParentId()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['parent_id', 'to_parent_id'], 'require');
        //验证正整数
        (new CurrencyValidate())->myGoCheck(['parent_id', 'to_parent_id'], 'positiveInt');
        //权限
        UserAuthority::checkAuthority(8);

        $parent_id = request()->param('parent_id');
        $to_parent_id = request()->param('to_parent_id');

        $cat_array_ = GlCategory::where(['parent_id' => $parent_id])
            ->select()
            ->toArray();

        //取得cat_id,取得需要的分类信息
        if (count($cat_array_) > 0) {
            foreach ($cat_array_ as $k => $v) {
                $cat_add_data['cat_name'] = $v['cat_name'];
                $cat_add_data['parent_id'] = $to_parent_id;
                $cat_add_data['sort_order'] = $v['sort_order'];
                //复制分类
                $cat_info = GlCategory::create($cat_add_data);
                //辅助分类下商品
                $goods_array = GlGoods::where(['cat_id' => $v['cat_id'], 'is_del' => 0])
                    ->select()
                    ->toArray();
                if (count($goods_array) > 0) {
                    foreach ($goods_array as $k2 => $v2) {
                        //复制商品
                        $goods_id = $v2['goods_id'];
                        $attribute = json_encode($v2['attribute'], true);
                        $goods_info = $this->byKeyRemoveArrVal($v2, 'goods_id');
                        $goods_info = $this->byKeyRemoveArrVal($goods_info, 'cat_id');
                        $goods_info = $this->byKeyRemoveArrVal($goods_info, 'goods_img');
                        $goods_info = $this->byKeyRemoveArrVal($goods_info, 'evaluate_count');
                        $goods_info = $this->byKeyRemoveArrVal($goods_info, 'original_img');
                        $goods_info = $this->byKeyRemoveArrVal($goods_info, 'goods_desc');
                        $goods_info = $this->byKeyRemoveArrVal($goods_info, 'attribute');
                        $goods_info = $this->byKeyRemoveArrVal($goods_info, 'add_time');
                        $goods_info = $this->byKeyRemoveArrVal($goods_info, 'upd_time');
                        $goods_info['cat_id'] = $cat_info->id;
                        $goods_info['evaluate_count'] = 0;
                        $goods_info['attribute'] = $attribute;
                        $goods_info['add_time'] = time();
                        $goods_info['upd_time'] = time();
                        $goods_info['goods_img'] = removeImgUrl($v2['goods_img']);
                        $goods_info['original_img'] = removeImgUrl($v2['original_img']);
                        $goods_info['goods_desc'] = removeImgUrl($v2['goods_desc']);
                        $add_goods_info = GlGoods::create($goods_info);

                        //生成货号
                        $this->addGoodsSn($add_goods_info->id);

                        //复制商品sku
                        $sku_array = GlGoodsSku::where(['goods_id' => $goods_id])
                            ->select()
                            ->toArray();
                        if (count($sku_array) > 0) {
                            foreach ($sku_array as $sku_k => $sku_v) {
                                $sku_info = $this->byKeyRemoveArrVal($sku_v, 'goods_id');
                                $sku_info = $this->byKeyRemoveArrVal($sku_info, 'sku_id');
                                $sku_info = $this->byKeyRemoveArrVal($sku_info, 'img_url');
                                $sku_info = $this->byKeyRemoveArrVal($sku_info, 'original_img_url');
                                $sku_info['goods_id'] = $add_goods_info->id;
                                $sku_info['img_url'] = removeImgUrl($sku_v['img_url']);
                                $sku_info['original_img_url'] = removeImgUrl($sku_v['original_img_url']);
                                GlGoodsSku::create($sku_info);
                            }
                        }
                        //复制商品相册
                        $gallery_array = GlGoodsGallery::where(['goods_id' => $goods_id])
                            ->select()
                            ->toArray();
                        if (count($gallery_array) > 0) {
                            foreach ($gallery_array as $gallery_k => $gallery_v) {
                                $gallery_info = $this->byKeyRemoveArrVal($gallery_v, 'goods_id');
                                $gallery_info = $this->byKeyRemoveArrVal($gallery_info, 'goods_gallery_id');
                                $gallery_info = $this->byKeyRemoveArrVal($gallery_info, 'img_url');
                                $gallery_info = $this->byKeyRemoveArrVal($gallery_info, 'img_original');
                                $gallery_info['goods_id'] = $add_goods_info->id;
                                $gallery_info['img_url'] = removeImgUrl($gallery_v['img_url']);
                                $gallery_info['img_original'] = removeImgUrl($gallery_v['img_original']);
                                GlGoodsGallery::create($gallery_info);
                            }
                        }
                    }
                }
            }
        } else {
            throw new CommonException(['msg' => '无效的顶级分类']);
        }

        return true;

    }

    /**
     * @return array|\PDOStatement|string|\think\Model|null
     * @throws CommonException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 返回商品信息
     */
    public function giveGoodsInfo()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['goods_id'], 'require');
        //验证正整数
        (new CurrencyValidate())->myGoCheck(['goods_id'], 'positiveInt');

        UserAuthority::checkAuthority(8);

        $data['goods_id'] = request()->param('goods_id');

        $result = GlGoods::where($data)->find();
        if (!$result) {
            throw new CommonException(['无效商品']);
        }
        $result = $result->toArray();

        $result['goods_sku'] = GlGoodsSku::where($data)->select();
        $result['goods_gallery'] = GlGoodsGallery::where($data)->select();

        return $result;
    }

    /**
     * @return bool
     * @throws CommonException
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 更新商品
     */
    public function updGoods()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['cat_id', 'goods_name', 'promote_number', 'promote_start_date'
            , 'promote_end_date', 'goods_img', 'original_img', 'is_on_sale', 'click_type'
            , 'is_best', 'is_new', 'is_hot', 'is_promote', 'goods_sales_volume', 'evaluate_count', 'attribute', 'goods_gallery', 'goods_sku_array', 'goods_id'], 'require');
        //验证正整数
        (new CurrencyValidate())->myGoCheck(['cat_id', 'goods_id', 'supplier_id'], 'positiveInt');

        UserAuthority::checkAuthority(8);

        $data2['goods_id'] = request()->param('goods_id');

        $data['cat_id'] = request()->param('cat_id');
        $data['goods_name'] = request()->param('goods_name');
        $data['goods_head_name'] = request()->param('goods_head_name');
        $data['click_count'] = 0;
        $data['click_type'] = request()->param('click_type');
        $data['promote_number'] = request()->param('promote_number');
        $data['promote_start_date'] = request()->param('promote_start_date');
        $data['promote_end_date'] = request()->param('promote_end_date');
        $data['keywords'] = request()->param('keywords');
        $data['goods_brief'] = request()->param('goods_brief');
        $data['goods_desc'] = removeImgUrl(request()->param('goods_desc'));
        $data['goods_img'] = removeImgUrl(request()->param('goods_img'));
        $data['original_img'] = removeImgUrl(request()->param('original_img'));
        $data['is_on_sale'] = request()->param('is_on_sale');
        $data['sort_order'] = 99;
        $data['is_del'] = 0;
        $data['is_best'] = request()->param('is_best');
        $data['is_new'] = request()->param('is_new');
        $data['is_hot'] = request()->param('is_hot');
        $data['is_promote'] = request()->param('is_promote');
        $data['upd_time'] = time();
        $data['seller_note'] = '';
        $data['goods_sales_volume'] = request()->param('goods_sales_volume');
        $data['evaluate_count'] = request()->param('evaluate_count');
        $data['attribute'] = json_encode(request()->param('attribute/a'));

        $goods_gallery_array = request()->param('goods_gallery/a');
        $goods_sku_array = request()->param('goods_sku_array/a');

        $data['goods_stock'] = $this->countGoodsStock($goods_sku_array);
        $data['market_price'] = $this->mpMarketPrice($goods_sku_array);
        $data['shop_price'] = $this->mpShopPrice($goods_sku_array);

        //插入供应商名称
        $data['supplier_id'] = request()->param('supplier_id');
        $supplier_info = GlSupplier::where([
            'id' => request()->param('supplier_id'),
            'is_del' => 0
        ])
            ->find();

        if (!$supplier_info) {
            throw new CommonException(['msg' => '无效供应商']);
        }

        $data['supplier_name'] = $supplier_info['supplier_name'];

        //更新商品
        $upd_number = $goods_info = GlGoods::where($data2)->update($data);
        //删除商品附属信息
        GlGoodsGallery::where($data2)->delete();
        GlGoodsSku::where($data2)->delete();

        if ($upd_number > 0) {
            //生成商品相册
            foreach ($goods_gallery_array as $k => $v) {
                $data_goods_gallery['goods_id'] = $data2['goods_id'];
                $data_goods_gallery['img_url'] = removeImgUrl($v['url']);
                $data_goods_gallery['img_original'] = removeImgUrl($v['original_url']);
                GlGoodsGallery::create($data_goods_gallery);
            }
            //生成sku
            foreach ($goods_sku_array as $k => $v) {
                $data_goods_sku['goods_id'] = $data2['goods_id'];
                $data_goods_sku['sku_desc'] = $v['sku_desc'];
                $data_goods_sku['sku_stock'] = $v['sku_stock'];
                $data_goods_sku['sku_shop_price'] = $v['sku_shop_price'];
                $data_goods_sku['sku_market_price'] = $v['sku_market_price'];
                $data_goods_sku['give_integral'] = $v['give_integral'];
                $data_goods_sku['integral'] = $v['integral'];
                $data_goods_sku['img_url'] = removeImgUrl($v['img_url']);
                $data_goods_sku['original_img_url'] = removeImgUrl($v['original_img_url']);
                GlGoodsSku::create($data_goods_sku);
            }

        } else {
            throw new CommonException(['msg' => '保存失败']);
        }

        return true;
    }

    /**
     * @return array|\PDOStatement|string|\think\Collection
     * @throws CommonException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 搜索商品
     */
    public function searchGoods()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['goods_name'], 'require');
        UserAuthority::checkAuthority(8);
        $where = [];
        array_push($where, ['goods_name', 'like', '%' . request()->param('goods_name') . '%']);
        array_push($where, ['is_del', '=', 0]);
        array_push($where, ['is_on_sale', '=', 1]);
//        $result = GlGoods::where($where)
//            ->field('goods_id,concat_ws(\'&\',goods_head_name,goods_name) as goods_name')
//            ->select();
        $category_array = GlCategory::select();
        $goods_list = GlGoods::where($where)
            ->field('goods_id,goods_name,cat_id')
            ->select();

        foreach ($goods_list as $k_g => $v_g) {
            foreach ($category_array as $k_c => $v_c) {
                if ($v_g['cat_id'] === $v_c['cat_id']) {
                    $goods_list[$k_g]['parent_id'] = $v_c['parent_id'];
                }
            }
        }
        foreach ($goods_list as $k_g => $v_g) {
            foreach ($category_array as $k_c => $v_c) {
                if ($v_g['parent_id'] === $v_c['cat_id']) {
                    $goods_list[$k_g]['goods_name'] = $v_c['cat_name'] . '&' . $v_g['goods_name'];
                }
            }
        }

        return $goods_list;

    }

    /**
     * @return array|\PDOStatement|string|\think\Collection
     * @throws CommonException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 查询商品2
     */
    public function giveGoodsByGoodsIdArray()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['goods_id_array'], 'require');
        UserAuthority::checkAuthority(8);

        $goods_id_array = request()->param('goods_id_array/a');
        $where = [];
        array_push($where, ['goods_id', 'in', $goods_id_array]);
        $result = GlGoods::where($where)
            ->field('goods_id,goods_name')
            ->select();

        return $result;
    }

    /**
     * @return bool
     * @throws CommonException
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 删除商品
     */
    public function delGoods()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['goods_id'], 'require');
        //验证正整数
        (new CurrencyValidate())->myGoCheck(['goods_id'], 'positiveInt');

        UserAuthority::checkAuthority(8);
        $data['goods_id'] = request()->param('goods_id');

        //根据商品id删除商品
        $upd_number = GlGoods::where($data)->update(['is_del' => 1]);
        if ($upd_number < 1) {
            throw new CommonException(['msg' => '删除失败']);
        }

        return true;

    }

    /**
     * @return bool
     * @throws CommonException
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 下架商品
     */
    public function endOfSaleGoods()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['goods_id'], 'require');
        //验证正整数
        (new CurrencyValidate())->myGoCheck(['goods_id'], 'positiveInt');

        UserAuthority::checkAuthority(8);
        $data['goods_id'] = request()->param('goods_id');

        //根据商品id删除商品
        $upd_number = GlGoods::where($data)->update(['is_on_sale' => 0]);
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
    public function allowSaleGoods()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['goods_id'], 'require');
        //验证正整数
        (new CurrencyValidate())->myGoCheck(['goods_id'], 'positiveInt');

        UserAuthority::checkAuthority(8);
        $data['goods_id'] = request()->param('goods_id');

        //根据商品id删除商品
        $upd_number = GlGoods::where($data)->update(['is_on_sale' => 1]);
        if ($upd_number < 1) {
            throw new CommonException(['msg' => '上架失败']);
        }

        return true;

    }

    /**
     * @return bool
     * @throws CommonException
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * 批量修改商品头
     */
    public function updGoodsNameHeadName()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['goods_head_name', 'parent_id'], 'require');
        //验证正整数
        (new CurrencyValidate())->myGoCheck(['parent_id'], 'positiveInt');

        UserAuthority::checkAuthority(8);

        $data['parent_id'] = request()->param('parent_id');
        $update['goods_head_name'] = request()->param('goods_head_name');

        $cat_id_array_ = GlCategory::where($data)
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

        GlGoods::where($where)
            ->update($update);

        return true;

    }


    /**
     * @param $goods_sku_array
     * @return int
     * 计算商品库存
     */
    private function countGoodsStock($goods_sku_array)
    {
        $goods_stock = 0;
        foreach ($goods_sku_array as $k => $v) {
            $goods_stock += $v['sku_stock'];
        }
        return $goods_stock;
    }

    /**
     * @param $arr
     * @return mixed
     * 冒泡排序market_price
     */
    private function mpMarketPrice($arr)
    {
        /* for ($i = 0; $i < count($arr); $i++) {
             for ($j = $i; $j < count($arr); $j++) {
                 if ($arr[$i]['sku_market_price'] + 0 > $arr[$j]['sku_market_price'] + 0) {
                     $temp = $arr[$i];
                     $arr[$i] = $arr[$j];
                     $arr[$j] = $temp;
                 }
             }
         }
         return $arr[0]['sku_market_price'];*/

        $min_array = [];
        foreach ($arr as $k => $v) {
            array_push($min_array, $v['sku_market_price']);
        }
        return min($min_array);

    }

    /**
     * @param $arr
     * @return mixed
     * 冒泡排序shop_price
     */
    private function mpShopPrice($arr)
    {
        /*for ($i = 0; $i < count($arr); $i++) {
            for ($j = $i; $j < count($arr); $j++) {
                if ($arr[$i]['sku_shop_price'] + 0 > $arr[$j]['sku_shop_price'] + 0) {
                    $temp = $arr[$i];
                    $arr[$i] = $arr[$j];
                    $arr[$j] = $temp;
                }
            }
        }*/
        $min_array = [];
        foreach ($arr as $k => $v) {
            array_push($min_array, $v['sku_shop_price']);
        }
        return min($min_array);
    }

    /**
     * @param $goods_id
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 生产货号
     */
    private function addGoodsSn($goods_id)
    {

        GlGoods::where(['goods_id' => $goods_id])
            ->update(['goods_sn' => 'GSN000' . $goods_id]);

    }

    /**
     * @param $arr
     * @param $key
     * @return mixed
     * 根据键删除数组项
     */
    private function byKeyRemoveArrVal($arr, $key)
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
}