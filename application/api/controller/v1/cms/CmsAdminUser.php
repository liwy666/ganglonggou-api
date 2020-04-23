<?php


namespace app\api\controller\v1\cms;


use app\api\model\GlAdmin;
use app\api\model\GlUser;
use app\api\service\UserAuthority;
use app\api\validate\CurrencyValidate;

class CmsAdminUser
{
    public function getAdminUsers()
    {
        //返回管理用户列表
        UserAuthority::checkAuthority(10);
        //验证必要
        (new CurrencyValidate())->myGoCheck(['page', 'limit'], 'require');
        //验证正整数
        (new CurrencyValidate())->myGoCheck(['page', 'limit'], 'positiveInt');

        $data['page'] = request()->param('page');
        $data['limit'] = request()->param('limit');

        $result['list'] = GlAdmin::where([
            ['is_del', '=', 0]
        ])
            ->page($data['page'], $data['limit'])
            ->order('login_time desc')
            ->select();

        $result['count'] = GlUser::where([
            ['is_del', '=', 0]
        ])
            ->count();

        return $result;
    }

}