<?php
/**
 * Created by PhpStorm.
 * User: TZX
 * Date: 2020/12/11
 * Time: 17:35
 */

namespace app\index\service;

use app\common\util\curlUtil;
use app\index\common\DataEnum;
use app\index\model\DocBorrowRequest;
use app\index\model\DocFile;
use app\index\model\DocFileVersion;
use app\index\model\UserRole;
use think\Session;

/**
 * 归档服务类
 * Class DocumentService
 * @package app\index\service
 */
class DocumentService
{
    /**
     * 判断是否为文件管理员（文控）
     * @return bool
     * @throws \think\exception\DbException
     */
    public static function isDocAdmin(){
        $sessionUserId = Session::get('info')['user_id'];
        $userRole = UserRole::get(["user_id" => $sessionUserId,"role_id" => 1]);
        if($userRole){
            return true;
        } else {
            return false;
        }
    }

    /**
     * 判断是否为一级审批人
     * @return bool
     * @throws \think\exception\DbException
     */
    public static function isFirstApprover($userId) {
        $userRole = UserRole::get(["user_id" => $userId, "role_id" => 2]);
        if ($userRole) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 判断是否为二级审批人
     * @return bool
     * @throws \think\exception\DbException
     */
    public static function isSecondApprover($userId) {
        $userRole = UserRole::get(["user_id" => $userId,"role_id" => 5]);
        if($userRole){
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取所有一级审批人
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getAllFirstApprover()
    {
        $userRole = new UserRole();
        $listApprover = $userRole->alias('ur')->where("role_id", 2)
            ->join('oa_user u', 'u.user_id = ur.user_id')
            ->field('u.user_id,u.user_name')
            ->select();

        return $listApprover;
    }

    /**
     * 获取所有二级审批人
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getAllSecondApprover()
    {
        $userRole = new UserRole();
        $listApprover = $userRole->alias('ur')->where("role_id", 5)
            ->join('oa_user u', 'u.user_id = ur.user_id')
            ->field('u.user_id,u.user_name')
            ->select();

        return $listApprover;
    }

    /**
     * 判断是否借阅过文档的某个版本
     * @param $versionId
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function isBorrow($versionId){
        $sessionUserId = Session::get('info')['user_id'];
        $docFileVersion = DocFileVersion::get($versionId);
        $docBorrowRequest = new DocBorrowRequest();
        $today = date('Y-m-d H:i:s', time());
        // 查找有效时间大于现在的借阅信息
        $result = $docBorrowRequest->where('applicant_id', $sessionUserId)
            ->where('file_id', $docFileVersion->file_id)
            ->where('version', $docFileVersion->version)
            ->where('effective_time', '>', $today)
            ->find();
        if($result) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取文档的某个版本的附件
     * @param $fileId
     * @param $version
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getFileAttachment($fileId, $version){
        $docFileVersion = new DocFileVersion();
        $attachment = $docFileVersion->alias('dfv')->where('file_id', $fileId)
            ->where('version', $version)
            ->join('oa_attachment a', 'a.attachment_id = dfv.attachment_id')
            ->field('source_name,file_size,save_path')
            ->find();

        return $attachment;
    }

//    public static function sendUpgradeRequestMessage($result) {
//
//    }
//
//    public static function sendUpgradeResultMessage($result) {
//
//    }

//    public static function sendUpgradeRequestMessage($result) {
//
//    }

    /**
     * 发送升版申请处理结果钉钉消息
     * @param $result 0成功，1失败
     * @param $docUpgradeRequest
     * @param $sourceName
     * @return mixed
     */
    public static function sendUpgradeResultMessage($result, $docUpgradeRequest) {
        $sessionUserId = Session::get('info')['user_id'];
        // 查询附件
        $attachmentModel = new Attachment();
        $attachment = $attachmentModel->where('attachment_type', 'doc_upgrade')
            ->where('related_id', $docUpgradeRequest->request_id)->find();
        if(!$result) {
            $approver = User::get(['user_id' => $sessionUserId]);
            $applicant = User::get(['user_id' => $docUpgradeRequest->applicant_id]);
            $postUrl = 'http://www.bjzzdr.top/us_service/public/other/ding_ding_c/sendMessage';
            $url = 'http://192.168.0.249/office_automation/public/static/layuimini/?requestType=1&requestId=' . $docUpgradeRequest->request_id;
            $data = DataEnum::$msgData;
            $data['userList'] = $applicant->dd_userid;
            $templet = '▪ 处理人：' . $approver->user_name . "\n";
            $templet .= '▪ 处理意见：' . $docUpgradeRequest->process_opinion . "\n";
            $templet .= '▪ 文档名称：' . $attachment->source_name . '(第' . $docUpgradeRequest->version . '版)' . "\n";
            $templet .= '▪ 链接：' . $url;
            $message = '◉ ' . '您的文档升版申请(#' . $docUpgradeRequest->request_id . ')已通过' . "\n" . $templet;
            $data['data']['content'] = $message;
            return curlUtil::post($postUrl, $data);
        } else {
            $approver = User::get(['user_id' => $sessionUserId]);
            $applicant = User::get(['user_id' => $docUpgradeRequest->applicant_id]);
            $postUrl = 'http://www.bjzzdr.top/us_service/public/other/ding_ding_c/sendMessage';
            $url = 'http://192.168.0.249/office_automation/public/static/layuimini/?requestType=1&requestId=' . $docUpgradeRequest->request_id;
            $data = DataEnum::$msgData;
            $data['userList'] = $applicant->dd_userid;
            $templet = '▪ 处理人：' . $approver->user_name . "\n";
            $templet .= '▪ 处理意见：' . $docUpgradeRequest->process_opinion . "\n";
            $templet .= '▪ 文档名称：' . $sourceName . '(第' . $docUpgradeRequest->version . '版)' . "\n";
            $templet .= '▪ 链接：' . $url;
            $message = '◉ ' . '您的文档升版申请(#' . $docUpgradeRequest->request_id . ')被驳回' . "\n" . $templet;
            $data['data']['content'] = $message;
            $result = curlUtil::post($postUrl, $data);
        }
    }

//    public static function sendBorrowRequestMessage($result) {
//
//    }
//
//    public static function sendBorrowResultMessage($result) {
//
//    }
}