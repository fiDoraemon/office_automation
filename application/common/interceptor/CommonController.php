<?php
/**
 * Created by PhpStorm.
 * User: Conqin
 * Date: 2020/9/7 0007
 * Time: 上午 11:42
 */

namespace app\common\interceptor;


use app\common\Result;
use think\Controller;
use think\Session;

/**
 * Class CommonController
 * @package app\interceptor
 * 登录拦截
 * 用户没有登录返回到登录页面
 */
class CommonController extends Controller
{
     public function _initialize(){
        //判断用户是否已经登录
       if (isset($_SESSION['info'])) { //isset — 检测变量是否已设置并且非 NULL
           return Result::returnResult(Result::NOT_LOGIN,null);
       }
    }
}