<?php
/**
 * Created by PhpStorm.
 * User: TZX
 * Date: 2020/12/11
 * Time: 17:35
 */

namespace app\index\service;

use app\index\model\DocBorrowRequest;
use app\index\model\DocFile;
use app\index\model\DocFileVersion;
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
     * @throws DbException
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
     * @return mixed
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
}