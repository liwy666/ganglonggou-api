<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/9/2
 * Time: 9:42
 */

namespace app\api\controller\v1\article_sign;


use app\api\model\GlArticleSignActivityUrl;
use app\api\model\GlArticleSignUser;
use app\api\model\glArticleSignUserSign;
use app\api\service\Login\BaseLogin;
use app\api\validate\CurrencyValidate;
use app\lib\exception\CommonException;

class User
{
    /**
     * @return array
     * @throws CommonException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 用户信息，签到记录
     */
    public function giveUserInfo()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['user_token'], 'require');
        $user_token = request()->param('user_token');
        $user_id = BaseLogin::getCurrentIdentity(['user_id'], $user_token)['user_id'];
        $user_info = GlArticleSignUser::where([
            ['user_id', '=', $user_id]
        ])
            ->find();
        if (!$user_info) {
            GlArticleSignUser::create([
                'user_id' => $user_id,
                'create_time' => time(),
                'count_day' => 0,
                'continuity_day' => 0,
                'avg_time' => 0,
                'count_share' => 0,
                'is_be_share' => 0
            ]);
        }

        $sign_log = GlArticleSignUserSign::where([
            ['user_id', '=', $user_id]
        ])
            ->select();

        return ['user_info' => $user_info, 'sign_log' => $sign_log];
    }

    /**
     * @throws CommonException
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * 用户签到
     */
    public function sign()
    {
        (new CurrencyValidate())->myGoCheck(['user_token', 'url_code'], 'require');
        $user_token = request()->param('user_token');
        $user_id = BaseLogin::getCurrentIdentity(['user_id'], $user_token)['user_id'];
        $url_code = request()->param('url_code');
        $result_msg = "签到成功!";
        /*检查code合法性*/
        $url_info = GlArticleSignActivityUrl::where([
            ['url_code', '=', $url_code],
            ['is_del', '=', 0],
        ])->find();

        if (!$url_info) {
            $result_msg = "签到链接可能已经失效~";
        }
        if ($url_info['start_date'] >= time()) {
            $result_msg = "还没开始签到哦~";
        }
        if ($url_info['end_date'] <= time()) {
            $result_msg = "签到已经结束,明天再来吧~";
        }

        /*检查用户是否已经签到*/
        if (GlArticleSignUserSign::where([
            ['url_code', '=', $url_code],
            ['user_id', '=', $user_id]
        ])->find()) {
            $result_msg = "您今天已经签到过了,继续保持~";
        }

        /*开始签到*/
        if ($result_msg == '签到成功!') {
            $sign_date = (float)(date('H') . '.' . date('i'));
            $sign_time = time();
            GlArticleSignUserSign::create([
                'user_id' => $user_id,
                'sign_date' => $sign_date,
                'sign_time' => $sign_time,
                'url_code' => $url_code,
                'article_name' => $url_info['article_name']
            ]);

            //计算是否连续签到
            $sign_log = GlArticleSignUserSign::where([['user_id', '=', $user_id]])
                ->order(['sign_time' => 'desc'])
                ->select();

            $add_continuity_day = false;
            if (count($sign_log) === 1 || $sign_log[1]['sign_time'] - time() < 86400) {
                $add_continuity_day = true;
            }
            $count_sign_data = 0;
            //计算签到平均时间
            if (count($sign_log) > 30) {
                $sign_log = array_slice(0, 30, array_reverse($sign_log));

                foreach ($sign_log as $value) {
                    $count_sign_data += $value['sign_date'];
                }
                $avg_time = $count_sign_data / 30;
            } else {
                foreach ($sign_log as $value) {
                    $count_sign_data += $value['sign_date'];
                }
                $avg_time = $count_sign_data / count($sign_log);
            }

            /*更新用户表*/
            if ($add_continuity_day) {
                //平均时间
                GlArticleSignUser::where([
                    ['user_id', '=', $user_id]
                ])->update([
                    'avg_time' => $avg_time
                ]);
                //参与天数+1
                GlArticleSignUser::where([
                    ['user_id', '=', $user_id]
                ])->setInc('count_day');
                //连续签到+1
                GlArticleSignUser::where([
                    ['user_id', '=', $user_id]
                ])->setInc('continuity_day');

            } else {
                //平均时间
                GlArticleSignUser::where([
                    ['user_id', '=', $user_id]
                ])->update([
                    'avg_time' => $avg_time,
                    'continuity_day' => 1
                ]);
                //参与天数+1
                GlArticleSignUser::where([
                    ['user_id', '=', $user_id]
                ])->setInc('count_day');
            }
        }

        return $result_msg;

    }

    /**
     * @return bool
     * @throws CommonException
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * 参加活动
     */
    public function joinActivity()
    {
        //验证必要
        (new CurrencyValidate())->myGoCheck(['token', 'user_token'], 'require');
        $share_user_token = request()->param('token');
        $user_token = request()->param('user_token');
        $share_user_id = BaseLogin::getCurrentIdentity(['user_id'], $share_user_token)['user_id'];
        $user_id = BaseLogin::getCurrentIdentity(['user_id'], $user_token)['user_id'];

        $user_info = GlArticleSignUser::where([
            ['user_id', '=', $user_id]
        ])->find();

        //没有被分享过，并且没有签到过
        if ($user_info['count_day'] == 0 && $user_info['is_be_share'] == 0 && $share_user_id != $user_id) {
            //改变用户为已被分享
            GlArticleSignUser::where([
                ['user_id', '=', $user_id]])
                ->update([
                    'is_be_share' => 1
                ]);
            //分享次数加1
            GlArticleSignUser::where([['user_id', '=', $share_user_id]])
                ->setInc('count_share');
        }


        return true;
    }
}