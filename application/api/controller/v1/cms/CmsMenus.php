<?php


namespace app\api\controller\v1\cms;

use app\api\model\GlCmsMenus;
use app\api\service\UserAuthority;
use app\api\validate\CurrencyValidate;

/**
 * Class CmsMenus
 * @package app\api\controller\v1\cms
 * 后台菜单管理
 */
class CmsMenus
{
    public function getMenusList()
    {
        UserAuthority::checkAuthority(10);
        return GlCmsMenus::select();
    }

    /**
     * @return bool
     * @throws \app\lib\exception\CommonException
     * 添加菜单
     */
    public function addMenu()
    {
        UserAuthority::checkAuthority(8);

        //验证必要
        (new CurrencyValidate())->myGoCheck(['titleName', 'parentId', 'actionCode'], 'require');
        //验证正整数
        (new CurrencyValidate())->myGoCheck(['actionCode'], 'positiveInt');

        $titleName = request()->param('titleName');
        $parentId = request()->param('parentId');
        $actionCode = request()->param('actionCode');
        $iconName = request()->param('iconName');
        $routerPath = request()->param('routerPath');
        $onClickName = request()->param('onClickName');


        GlCmsMenus::create([
            "titleName" => $titleName,
            "parentId" => $parentId,
            "actionCode" => $actionCode,
            "iconName" => $iconName,
            "routerPath" => $routerPath,
            "onClickName" => $onClickName,
        ]);

        return true;

    }
}