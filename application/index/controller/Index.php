<?php
namespace app\index\controller;

use app\common\model\UserInfo;
use app\common\util\EncryptionUtil;
use app\index\model\Department;
use app\index\model\Minute;
use app\index\model\MissionView;
use app\index\model\Project;
use app\index\model\User;
use app\common\Result;
use app\index\service\UserService;
use app\other\model\MissionInfo;
use app\other\model\UserMission;
use app\index\service\AttachmentService;
use app\index\model\Mission;
use app\index\model\MissionInterest;
use app\common\util\curlUtil;
use think\Controller;
use think\File;
use think\Session;

class Index extends Controller
{
    public function index()
    {
//        return '<style type="text/css">*{ padding: 0; margin: 0; } .think_default_text{ padding: 4px 48px;} a{color:#2E5CD5;cursor: pointer;text-decoration: none} a:hover{text-decoration:underline; } body{ background: #fff; font-family: "Century Gothic","Microsoft yahei"; color: #333;font-size:18px} h1{ font-size: 100px; font-weight: normal; margin-bottom: 12px; } p{ line-height: 1.6em; font-size: 42px }</style><div style="padding: 24px 48px;"> <h1>:)</h1><p> ThinkPHP V5<br/><span style="font-size:30px">十年磨一剑 - 为API开发设计的高性能框架</span></p><span style="font-size:22px;">[ V5.0 版本由 <a href="http://www.qiniu.com" target="qiniu">七牛云</a> 独家赞助发布 ]</span></div><script type="text/javascript" src="https://tajs.qq.com/stats?sId=9347272" charset="UTF-8"></script><script type="text/javascript" src="https://e.topthink.com/Public/static/client.js"></script><think id="ad_bd568ce7058a1091"></think>';
//        return $this->fetch();
    }

    // 获取菜单列表
    public function getMenuList()
    {
        $filePath = APP_PATH . 'common/api/init.json';
        $sessionUserId = Session::get("info")["user_id"];
        $menuList = json_decode(file_get_contents($filePath));
        $user = User::get($sessionUserId);
        // 如果不是管理员
        if($user->super == 0) {
            unset($menuList->menuInfo[1]);
        }

        return $menuList;
    }

}
