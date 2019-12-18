<?php

namespace app\api\controller\v1;
use app\api\model\GlFeedBack;
use app\api\service\UserAuthority;
use app\api\validate\CurrencyValidate;
use app\lib\exception\CommonException;

class FeedBack
{
    /**
     * @return bool
     * @throws CommonException
     * 添加反馈信息
     */
    public function addFeedBack()
    {
        (new CurrencyValidate())->myGoCheck(['feed_back_type', 'problem_details', 'contact'], 'require');
        $data['feed_back_type'] = request()->param('feed_back_type');
        $data['problem_details'] = request()->param('problem_details');
        $data['contact'] = request()->param('contact');
        $img_files_url = request()->param('img_files_url');
        if (strlen($data['problem_details']) < 5) {
            throw new CommonException(['msg' => '反馈内容至少输入5个字符']);
        }
        if (strlen($data['problem_details'])>250){
            throw new CommonException(['msg' => '反馈内容太长']);
        }
        $pattern = '/^[a-z0-9]+([._-][a-z0-9]+)*@([0-9a-z]+\.[a-z]{2,14}(\.[a-z]{2})?)$/i';
        $search = '/^0?1[3|4|5|6|7|8][0-9]\d{8}$/';
        if (!preg_match($pattern, $data['contact']) && !preg_match($search, $data['contact'])) {
            throw new CommonException(['msg' => '联系方式错误']);
        }
        if (!empty($img_files_url)) {
            $list = explode(',', $img_files_url);
            $img_url = [];
            foreach ($list as $k => $l) {
                $img_url[$k] = removeImgUrl($l);
            }
            $data['img_files_url'] = implode(",", $img_url);
        }
        $data['add_time'] = time();
        GlFeedBack::create($data);
        return true;
    }

    /**
     * @return mixed
     * @throws CommonException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 反馈信息
     */
    public function mainFeedBack()
    {
//        UserAuthority::checkAuthority(8);
        (new CurrencyValidate())->myGoCheck(['page', 'limit'], 'require');
        (new CurrencyValidate())->myGoCheck(['page', 'limit'], 'positiveInt');
        $page = request()->param('page');
        $limit = request()->param('limit');
        $result['list'] = GlFeedBack::where('is_del', '=', 0)->order('add_time desc')->page($page, $limit)->select();
        $result['count'] = GlFeedBack::where('is_del', '=', 0)->count();
        return $result;
    }

    /**
     * @return bool
     * @throws CommonException
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 处理反馈信息
     */
    public function handleFeedBack()
    {
        UserAuthority::checkAuthority(8);
        (new CurrencyValidate())->myGoCheck(['feed_back_id'], 'require');
        $upd_number = GlFeedBack::where([
            ['id', '=', request()->param('feed_back_id')],
            ['is_del', '=', 0],
            ['is_handle', '=', 0]
        ])
            ->update([
                'is_handle' => 1
            ]);
        if ($upd_number < 1) {
            throw new CommonException(['msg' => '处理失败']);
        }
        return true;
    }

    /**
     * @return bool
     * @throws CommonException
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 删除反馈信息
     */
    public function delFeedBack()
    {
        UserAuthority::checkAuthority(8);
        (new CurrencyValidate())->myGoCheck(['feed_back_id'], 'require');
        $upd_number = GlFeedBack::where([
            ['id', '=', request()->param('feed_back_id')],
            ['is_del', '=', 0],
        ])
            ->update([
                'is_del' => 1
            ]);
        if ($upd_number < 1) {
            throw new CommonException(['msg' => '删除失败']);
        }
        return true;

    }
}