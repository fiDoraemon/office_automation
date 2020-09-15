<?php
namespace app\index\controller;

use app\common\model\User;
use app\common\Result;
use app\common\service\AttachmentService;
use think\captcha\Captcha;
use think\Controller;

class Index extends Controller
{
    public function index()
    {
//        return '<style type="text/css">*{ padding: 0; margin: 0; } .think_default_text{ padding: 4px 48px;} a{color:#2E5CD5;cursor: pointer;text-decoration: none} a:hover{text-decoration:underline; } body{ background: #fff; font-family: "Century Gothic","Microsoft yahei"; color: #333;font-size:18px} h1{ font-size: 100px; font-weight: normal; margin-bottom: 12px; } p{ line-height: 1.6em; font-size: 42px }</style><div style="padding: 24px 48px;"> <h1>:)</h1><p> ThinkPHP V5<br/><span style="font-size:30px">十年磨一剑 - 为API开发设计的高性能框架</span></p><span style="font-size:22px;">[ V5.0 版本由 <a href="http://www.qiniu.com" target="qiniu">七牛云</a> 独家赞助发布 ]</span></div><script type="text/javascript" src="https://tajs.qq.com/stats?sId=9347272" charset="UTF-8"></script><script type="text/javascript" src="https://e.topthink.com/Public/static/client.js"></script><think id="ad_bd568ce7058a1091"></think>';
//        return $this->fetch();
    }

    // 显示用户列表
    public function userList($page = 1, $keyword = '') {
        $user = new User();

        if($keyword == '') {
            $count = $user->value('count(*)');
            $data = $user->field('id, user_id, user_name')->page("$page, 10")->select();
        } else {
            $count = $user->value('count(*)');
            $data = $user->field('id, user_id, user_name')->page("$page, 10")->select();
        }

        return Result::returnResult(Result::SUCCESS, $data, $count);
    }
}
