<?php


namespace app\api\controller\v1;
use app\api\model\GlFeedBack;
use app\api\service\UserAuthority;
use app\api\validate\CurrencyValidate;
use app\lib\exception\CommonException;

class FeedBack
{
  public function addFeedBack(){
      (new CurrencyValidate())->myGoCheck(['feed_back_type', 'problem_details','contact'], 'require');
      $data['feed_back_type'] = request()->param('feed_back_type');
      $data['problem_details'] = request()->param('problem_details');
      $data['contact'] = request()->param('contact');
      $data['img_files_url'] = request()->param('img_files_url');
      $data['add_time'] = time();
      GlFeedBack::create($data);
      return true;
  }
  public function mainFeedBack(){
      UserAuthority::checkAuthority(8);
      (new CurrencyValidate())->myGoCheck(['page', 'limit'], 'require');
      (new CurrencyValidate())->myGoCheck(['page', 'limit'], 'positiveInt');
      $page=request()->param('page');
      $limit=request()->param('limit');
      $result['list']=GlFeedBack::where('is_del','=',0)->order('add_time desc')->page($page,$limit)->select();
      $result['count']=GlFeedBack::all()->count();
      return $result;
  }
  public function handleFeedBack(){
      UserAuthority::checkAuthority(8);
      (new CurrencyValidate())->myGoCheck(['feed_back_id'], 'require');
      $upd_number =  GlFeedBack::where([
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
    public function delFeedBack(){
      UserAuthority::checkAuthority(8);
        (new CurrencyValidate())->myGoCheck(['feed_back_id'], 'require');
        $upd_number =  GlFeedBack::where([
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