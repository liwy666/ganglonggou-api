<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
//use think\facade\Route;
use think\facade\Route;

/*测试*/
Route::any('api/:version/test$', 'api/:version.Test/test');

/*发送邮件*/
Route::post('api/:version/send_email$', 'api/:version.Email/sandEmail');

/*微信分享*/
Route::get('api/:version/goods_make/get_WxJsSdk', 'api/:version.WxShare/giveWxShareInfo');


/*支付回调*/
//微信公众号支付回调
Route::any('api/:version/notify/wx_js_api_notify$', 'api/:version.notify.PayNotify/wxJsApiNotify');
//微信App支付回调
Route::any('api/:version/notify/wx_app_api_notify$', 'api/:version.notify.PayNotify/wxAppApiNotify');
//农行支付回调
Route::any('api/:version/notify/abc_notify$', 'api/:version.notify.PayNotify/abcPayNotify');
//中行支付回调
Route::any('api/:version/notify/boc_notify$', 'api/:version.notify.PayNotify/bocPayNotify');
//支付宝支付回调
Route::any('api/:version/notify/ali_pay_notify$', 'api/:version.notify.PayNotify/aliPayNotify');


/*百度ueditor*/
Route::get('api/:version/ueditor_file_upload$', 'api/:version.ueditor.Ueditor/ueditorFileUpload');
Route::post('api/:version/ueditor_file_upload$', 'api/:version.ueditor.Ueditor/ueditorFileUpload');
Route::rule('api/:version/ueditor_file_upload$', 'api/:version.Option/returnTrue', 'OPTIONS');

/*cms*/
//登录
Route::post('api/:version/cms/login$', 'api/:version.cms.CmsAdmin/adminLogin');
//获取商品列表
Route::get('api/:version/cms/cms_get_goods_list$', 'api/:version.cms.CmsGoods/giveGoodsListByPage');
//获取单个商品信息
Route::get('api/:version/cms/cms_get_goods_info$', 'api/:version.cms.CmsGoods/giveGoodsInfo');
//搜索商品
Route::get('api/:version/cms/cms_search_goods$', 'api/:version.cms.CmsGoods/searchGoods');
//搜索商品
Route::get('api/:version/cms/cms_get_goods_by_goods_id_array', 'api/:version.cms.CmsGoods/giveGoodsByGoodsIdArray');
//添加商品
Route::post('api/:version/cms/cms_add_goods$', 'api/:version.cms.CmsGoods/addGoods');
//更新商品
Route::post('api/:version/cms/cms_upd_goods$', 'api/:version.cms.CmsGoods/updGoods');
//删除商品
Route::post('api/:version/cms/cms_del_goods$', 'api/:version.cms.CmsGoods/delGoods');
//下架商品
Route::post('api/:version/cms/cms_end_of_sale_goods$', 'api/:version.cms.CmsGoods/endOfSaleGoods');
//上架商品
Route::post('api/:version/cms/cms_allow_sale_goods$', 'api/:version.cms.CmsGoods/allowSaleGoods');
//批量修改商品头
Route::post('api/:version/cms/cms_batch_upd_goods_head_name$', 'api/:version.cms.CmsGoods/updGoodsNameHeadName');
//批量修改商品头
Route::post('api/:version/cms/cms_copy_goods_by_parent_id$', 'api/:version.cms.CmsGoods/copyGoodsByParentId');
//获取分类
Route::get('api/:version/cms/cms_get_cat_list$', 'api/:version.cms.CmsCat/giveAllCat');
//获取分类列表
Route::get('api/:version/cms/cms_get_cat_list_by_page$', 'api/:version.cms.CmsCat/giveCatListByPage');
//添加分类
Route::post('api/:version/cms/cms_add_cat$', 'api/:version.cms.CmsCat/addCat');
//编辑分类
Route::post('api/:version/cms/cms_upd_cat$', 'api/:version.cms.CmsCat/updCat');
//删除分类
Route::post('api/:version/cms/cms_del_cat$', 'api/:version.cms.CmsCat/delCat');
//图片上传
Route::post('api/:version/cms/cms_upload_goods_img$', 'api/:version.upload.Upload/ImgUpload');
Route::rule('api/:version/cms/cms_upload_goods_img$', 'api/:version.Option/returnTrue', 'OPTIONS');
//获取广告列表
Route::get('api/:version/cms/cms_get_index_ad_list$', 'api/:version.cms.CmsIndexAd/giveIndexAdList');
Route::get('api/:version/cms/cms_get_all_ad$', 'api/:version.cms.CmsIndexAd/giveAllIndexAdList');
//编辑广告
Route::post('api/:version/cms/cms_add_index_ad$', 'api/:version.cms.CmsIndexAd/addIndexAd');
Route::post('api/:version/cms/cms_upd_index_ad$', 'api/:version.cms.CmsIndexAd/updIndexAd');
Route::post('api/:version/cms/cms_del_index_ad$', 'api/:version.cms.CmsIndexAd/delIndexAd');
Route::post('api/:version/cms/cms_upd_ad_img$', 'api/:version.cms.CmsIndexAd/updImg');
Route::rule('api/:version/cms/cms_upd_ad_img$', 'api/:version.Option/returnTrue', 'OPTIONS');
Route::post('api/:version/cms/cms_ease_upd_index_ad$', 'api/:version.cms.CmsIndexAd/easeUpdIndexAd');
Route::get('api/:version/cms/cms_get_into_count$', 'api/:version.cms.CmsIndexAd/giveIntoCountList');
Route::get('api/:version/cms/cms_get_data_zip_url$', 'api/:version.cms.CmsIndexAd/getDataZipUrl');
//下架广告
Route::post('api/:version/cms/cms_end_of_index_ad$', 'api/:version.cms.CmsIndexAd/endOfSaleIndexAd');
//上架广告
Route::post('api/:version/cms/cms_allow_index_ad$', 'api/:version.cms.CmsIndexAd/allowSaleIndexAd');
//预约订单
Route::get('api/:version/cms/cms_get_make_order_list$', 'api/:version.cms.CmsMakeOrder/giveMakeOderList');
Route::post('api/:version/cms/cms_handle_make_order$', 'api/:version.cms.CmsMakeOrder/handleMakeOrder');
Route::post('api/:version/cms/cms_del_make_order$', 'api/:version.cms.CmsMakeOrder/delMakeOrder');
//优惠券
Route::get('api/:version/cms/cms_get_coupon_list$', 'api/:version.cms.CmsCoupon/giveCouponListByPage');
Route::post('api/:version/cms/cms_add_coupon$', 'api/:version.cms.CmsCoupon/addCoupon');
Route::post('api/:version/cms/cms_upd_coupon$', 'api/:version.cms.CmsCoupon/updCoupon');
Route::post('api/:version/cms/cms_del_coupon$', 'api/:version.cms.CmsCoupon/delCoupon');
//订单
Route::get('api/:version/cms/cms_get_order_list$', 'api/:version.cms.CmsOrder/giveOrderListByPage');
Route::get('api/:version/cms/cms_import_order_list$', 'api/:version.cms.CmsOrder/importOrderList');
Route::get('api/:version/cms/cms_get_order_info$', 'api/:version.cms.CmsOrder/giveOrderInfo');
Route::get('api/:version/cms/cms_get_extra_order_info$', 'api/:version.cms.CmsOrder/extraOrderInfoByOrderSn');
Route::get('api/:version/cms/cms_pay_query$', 'api/:version.cms.CmsOrder/OrderPaymentQuery');
Route::post('api/:version/cms/cms_call_order$', 'api/:version.cms.CmsOrder/callOrderByOrderSn');
Route::post('api/:version/cms/cms_del_order$', 'api/:version.cms.CmsOrder/delOrderByOrderSn');
Route::post('api/:version/cms/cms_change_order_state_after_sale$', 'api/:version.cms.CmsOrder/changeOrderStateAfterSale');
//物流
Route::post('api/:version/cms/cms_upd_logistics$', 'api/:version.cms.CmsOrder/updOrderLogisticsInfoByOrderSn');
Route::post('api/:version/cms/cms_delivery_order$', 'api/:version.cms.CmsOrder/deliveryByOrderSn');
/*售后*/
Route::get('api/:version/cms/cms_get_after_sale_list$', 'api/:version.cms.CmsAfterSale/giveAfterSaleListByPage');
Route::post('api/:version/cms/cms_order_refund$', 'api/:version.cms.CmsAfterSale/OrderRefund');
/*评价*/
Route::get('api/:version/cms/cms_get_evaluate_list$', 'api/:version.cms.CmsEvaluate/giveEvaluateList');
Route::post('api/:version/cms/cms_allow_evaluate$', 'api/:version.cms.CmsEvaluate/allowEvaluate');
Route::post('api/:version/cms/cms_del_evaluate$', 'api/:version.cms.CmsEvaluate/delEvaluate');
Route::post('api/:version/cms/cms_add_evaluate$', 'api/:version.cms.CmsEvaluate/addEvaluate');
/*用户*/
Route::get('api/:version/cms/cms_get_user_list$', 'api/:version.cms.CmsUser/giveUserListByPage');
/*清除缓存*/
Route::post('api/:version/cms/clean_user_goods_list_cache$', 'api/:version.CleanCache/CleanUserGoodsListCache');
Route::post('api/:version/cms/clean_user_index_ad_list_cache$', 'api/:version.CleanCache/CleanUserIndexAdListCache');
Route::post('api/:version/cms/clean_user_cat_list_cache$', 'api/:version.CleanCache/CleanUserCatListCache');
Route::post('api/:version/cms/clean_user_classify_cache$', 'api/:version.CleanCache/CleanUserClassifyListCache');
Route::post('api/:version/cms/clean_user_article_cache$', 'api/:version.CleanCache/CleanUserArticleCache');
/*分类展示*/
Route::get('api/:version/cms/cms_get_classify_ad_list$', 'api/:version.cms.CmsClassifyAd/giveListByPage');
Route::get('api/:version/cms/cms_get_parent_classify_ad_list$', 'api/:version.cms.CmsClassifyAd/giveParentClassify');
Route::post('api/:version/cms/cms_add_classify_ad$', 'api/:version.cms.CmsClassifyAd/addClassify');
Route::post('api/:version/cms/cms_upd_classify_ad$', 'api/:version.cms.CmsClassifyAd/updClassify');
Route::post('api/:version/cms/cms_del_classify_ad$', 'api/:version.cms.CmsClassifyAd/delClassify');
Route::post('api/:version/cms/cms_manual_add_classify_ad$', 'api/:version.cms.CmsClassifyAd/manualAddClassify');
/*供应商*/
Route::post('api/:version/cms/cms_add_supplier$', 'api/:version.cms.CmsSupplier/addSupplier');
Route::post('api/:version/cms/cms_upd_supplier$', 'api/:version.cms.CmsSupplier/updSupplier');
Route::post('api/:version/cms/cms_del_supplier$', 'api/:version.cms.CmsSupplier/delSupplier');
Route::get('api/:version/cms/cms_get_all_supplier$', 'api/:version.cms.CmsSupplier/giveAllSupplier');
/*文章*/
Route::post('api/:version/cms/cms_add_article$', 'api/:version.cms.CmsArticle/addArticle');
Route::post('api/:version/cms/cms_upd_article$', 'api/:version.cms.CmsArticle/updArticle');
Route::post('api/:version/cms/cms_del_article$', 'api/:version.cms.CmsArticle/delArticle');
Route::get('api/:version/cms/cms_get_all_article$', 'api/:version.cms.CmsArticle/giveAllArticle');
/*搜索历史*/
Route::get('api/:version/cms/cms_get_all_search_log$', 'api/:version.cms.CmsSearchLog/giveSearchLogListByPage');
Route::post('api/:version/cms/cms_add_search_log$', 'api/:version.cms.CmsSearchLog/addSearchLog');
Route::post('api/:version/cms/cms_del_search_log$', 'api/:version.cms.CmsSearchLog/delSearchLog');
Route::post('api/:version/cms/cms_verify_search_log$', 'api/:version.cms.CmsSearchLog/verifySearchLog');
Route::post('api/:version/cms/cms_cancel_verify_search_log$', 'api/:version.cms.CmsSearchLog/cancelVerifySearchLog');
/*APP更新*/
Route::post('api/:version/cms/cms_upload_app$', 'api/:version.AppVersion/uploadApp');
Route::rule('api/:version/cms/cms_upload_app$', 'api/:version.Option/returnTrue', 'OPTIONS');
Route::post('api/:version/cms/cms_add_new_version_app$', 'api/:version.AppVersion/addNewVersionApp');
Route::get('api/:version/cms/cms_get_app_version_info$', 'api/:version.AppVersion/getAppVersion');

/*goods_make*/
//获取首页信息
Route::get('api/:version/goods_make/get_index_info$', 'api/:version.goods_make.GoodsMakeIndex/giveIndexInfo');
//获取商品额外信息
Route::get('api/:version/goods_make/get_extra_goods_info$', 'api/:version.goods_make.GoodsInfo/giveExtraGoodsInfo');
//获取商品信息
Route::get('api/:version/goods_make/get_goods_info$', 'api/:version.goods_make.GoodsInfo/giveGoodsInfoByGoodsId');
//提交预约订单
Route::post('api/:version/goods_make/add_make_order$', 'api/:version.goods_make.MakeOrder/addOrder');


/*普通*/
/*微信分享*/
Route::get('api/:version/get_WxJsSdk$', 'api/:version.WxShare/giveWxShareInfo');
//获取首页信息
Route::get('api/:version/get_index_info$', 'api/:version.common.Index/giveIndexInfo');
//获取首页信息
Route::post('api/:version/ad_item_count$', 'api/:version.common.Index/countIndexAdItem');
//获取商品列表
Route::get('api/:version/user_get_goods_list$', 'api/:version.common.Goods/giveGoodsList');
//获取商品信息
Route::get('api/:version/get_goods_info$', 'api/:version.common.Goods/giveGoodsInfoByGoodsId');
//商品额外信息
Route::get('api/:version/get_extra_goods_info$', 'api/:version.common.Goods/giveExtraGoodsInfo');
//商品评价
Route::get('api/:version/user_get_evaluate_by_goods_id_and_page$', 'api/:version.common.Evaluate/giveEvaluateListByGoodsIdAndPage');
//领取优惠券
Route::post('api/:version/user_get_coupon$', 'api/:version.common.Coupon/userGetCoupon');
//用户优惠券
Route::get('api/:version/user_get_coupon_list$', 'api/:version.common.Coupon/giveCouponListByUserId');
//用户优惠券
Route::get('api/:version/user_get_all_coupon_list$', 'api/:version.common.Coupon/giveCouponListByIntoType');
//用户获取购物车
Route::post('api/:version/user_get_cart$', 'api/:version.common.Cart/userGetCart');
//添加地址
Route::post('api/:version/user_add_address$', 'api/:version.common.Address/addAddress');
//添加地址
Route::post('api/:version/user_upd_address$', 'api/:version.common.Address/updAddress');
//获取收货地址
Route::get('api/:version/user_get_address$', 'api/:version.common.Address/giveAddress');
//切换默认收货地址
Route::post('api/:version/user_upd_default_address$', 'api/:version.common.Address/updDefaultAddress');
//删除收货地址
Route::post('api/:version/user_del_address$', 'api/:version.common.Address/delAddress');
//获取用户信息
Route::get('api/:version/user_get_user_info$', 'api/:version.common.User/giveUserInfoByUserToken');
//更换头像
Route::post('api/:version/user_upd_portrait$', 'api/:version.common.User/userUpdPortrait');
Route::rule('api/:version/cms/user_upd_portrait$', 'api/:version.Option/returnTrue', 'OPTIONS');
//修改用户信息
Route::post('api/:version/user_upd_info$', 'api/:version.common.User/updUserInfoByUserId');
//获取支付信息
Route::get('api/:version/user_get_pay_list$', 'api/:version.common.Pay/givePayList');
//提交订单
Route::post('api/:version/user_submit_order$', 'api/:version.common.Order/submitOrder');
//获取订单信息
Route::get('api/:version/user_get_one_order_info$', 'api/:version.common.Order/giveOrderInfo');
//订单支付
Route::get('api/:version/payment/user_order_payment$', 'api/:version.common.Pay/OrderPayment');
//获取所有订单
Route::get('api/:version/user_get_all_order$', 'api/:version.common.Order/giveAllOrderByUserId');
//取消订单
Route::post('api/:version/user_call_order$', 'api/:version.common.Order/callOrderByOrderSn');
//删除订单
Route::post('api/:version/user_del_order$', 'api/:version.common.Order/delOrderByOrderSn');
//签收订单
Route::post('api/:version/user_take_order$', 'api/:version.common.Order/takeOrderByOrderSn');
//订单支付查询
Route::get('api/:version/user_query_order_payment$', 'api/:version.common.Order/queryOrderPayment');
//提交评价
Route::post('api/:version/user_ins_evaluate$', 'api/:version.common.Evaluate/insEvaluate');
//提交售后
Route::post('api/:version/user_submit_after_sale$', 'api/:version.common.AfterSale/submitAfterSale');
//取消售后
Route::post('api/:version/user_call_after_sale$', 'api/:version.common.AfterSale/callAfterSale');
//获取分类列表
Route::get('api/:version/user_get_classify_ad_list$', 'api/:version.common.ClassifyAd/giveClassifyAdList');
//获取文章
Route::get('api/:version/user_get_article$', 'api/:version.common.Article/giveArticle');
//获取搜索关键词
Route::get('api/:version/user_get_search_log$', 'api/:version.common.SearchLog/getSearchLog');
Route::post('api/:version/user_add_search_log$', 'api/:version.common.SearchLog/addSearchLog');
//支付宝密钥
Route::get('api/:version/get_ali_pay_merchant_private_key$', 'api/:version.common.AppConfig/getAliPayMerchantPrivateKey');
//检查版本更新
Route::get('api/:version/get_app_version$', 'api/:version.AppVersion/getAppVersion');


/*登录*/
Route::post('api/:version/test_login$', 'api/:version.common.Login/testLogin');
Route::post('api/:version/abc_wx_login$', 'api/:version.common.Login/abcWxLogin');
Route::post('api/:version/abc_app_login$', 'api/:version.common.Login/abcAppLogin');
Route::post('api/:version/abc_new_app_login$', 'api/:version.common.Login/abcNewAppLogin');
Route::post('api/:version/abc_tourist_login$', 'api/:version.common.Login/abcTouristLogin');
Route::post('api/:version/wx_login$', 'api/:version.common.Login/wxLogin');
Route::post('api/:version/user_login_count$', 'api/:version.common.Login/loginCount');
Route::post('api/:version/mobile_user_login$', 'api/:version.common.Login/mobileLogin');
Route::post('api/:version/android_user_login$', 'api/:version.common.Login/androidLogin');
Route::post('api/:version/android_wx_login$', 'api/:version.common.Login/androidWeChatLogin');
Route::post('api/:version/android_ali_pay_login$', 'api/:version.common.Login/androidAliPayLogin');
Route::post('api/:version/ios_user_login$', 'api/:version.common.Login/iosLogin');
Route::post('api/:version/ios_wx_login$', 'api/:version.common.Login/iosWeChatLogin');
Route::post('api/:version/ios_ali_pay_login$', 'api/:version.common.Login/iosAliPayLogin');

//发送登录或重置密码的邮箱验证码
Route::post('api/:version/send_retrieve_password_or_login_email_verify_code$', 'api/:version.common.User/sendRetrievePasswordOrLoginEmailVerifyCode');
//通过邮箱验证码重置密码
Route::post('api/:version/retrieve_password_by_email_verify_code$', 'api/:version.common.User/retrievePasswordByEmailVerifyCode');
//pc端获取请求code
Route::get('api/:version/user_get_pc_login_code$', 'api/:version.common.Login/pcGetLoginCode');
//pc端通过wxOpenid登录
Route::get('api/:version/pc_login_by_openid$', 'api/:version.common.Login/pcByWxOpenidLogin');
//wx端写入PcToken
Route::post('api/:version/wx_write_pc_token$', 'api/:version.common.Login/writePcTokenByWxToken');
//手机端注册验证
Route::post('api/:version/mobile_register_check$', 'api/:version.common.Register/checkMobileRegister');
//手机端注册
Route::post('api/:version/mobile_register$', 'api/:version.common.Register/mobileRegister');
//通过邮箱号注册
Route::post('api/:version/register_by_email$', 'api/:version.common.Register/registerAccountsByEmailAddress');
//注册邮箱发送验证码
Route::post('api/:version/send_register_email_verify_code$', 'api/:version.common.Register/sendRegisterEmailVerifyCode');

/*定时任务*/
//定时查看订单支付情况
Route::post('api/:version/timing_check_order_payment_state$', 'api/:version.TimedTask/timingCheckOrderPaymentState');

/*阅读打卡*/
//获取用户信息
Route::get('api/:version/article_sign_get_user_info$', 'api/:version.article_sign.User/giveUserInfo');
//开始签到
Route::post('api/:version/article_sign_sign$', 'api/:version.article_sign.User/sign');
//开始签到
Route::post('api/:version/article_sign_join_activity$', 'api/:version.article_sign.User/joinActivity');
//查看用户表
Route::get('api/:version/cms/article_sign_get_user_list$', 'api/:version.article_sign.Cms/giveUserList');
//创建分享链接
Route::post('api/:version/cms/create_share_url$', 'api/:version.article_sign.Cms/createShareUrl');

/*意见反馈*/
//添加反馈信息
Route::post('api/:version/add_feed_back$', 'api/:version.FeedBack/addFeedBack');
//查看反馈信息
Route::get('api/:version/cms/feed_back$', 'api/:version.FeedBack/mainFeedBack');
//处理反馈信息
Route::post('api/:version/cms/handle_feed_back$', 'api/:version.FeedBack/handleFeedBack');
//删除反馈信息
Route::post('api/:version/cms/del_feed_back$', 'api/:version.FeedBack/delFeedBack');