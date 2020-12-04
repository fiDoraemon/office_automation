<?php
/**
 * Created by PhpStorm.
 * User: TZX
 * Date: 2020/9/9
 * Time: 16:46
 */

namespace app\index\service;

use app\common\Result;
use app\index\model\Attachment;
use think\Session;

/**
 * 附件服务类
 * Class AttachmentService
 * @package app\index\service
 */
class AttachmentService
{
    /**
     *
     * @param $file
     * @return array
     */
    public static function fileUpload($file) {
        $fileInfo = $file->getInfo();
        // 验证并移动文件（最大 50 MB）
        $info = $file->validate(['size' => 52428800])->move(ROOT_PATH . 'public/upload');           // 最大50m
        if ($info) {
            // 插入附件信息
            $attachment = new Attachment([
                'source_name' => $fileInfo['name'],
                'storage_name' => $info->getFilename(),
                'uploader_id' => Session::get("info")["user_id"],
                'file_size' => $fileInfo['size'],           // 单位：字节
                'save_path' => $info->getSaveName()
            ]);
            $attachment->save();
            return ['result' => true, 'id' => $attachment->attachment_id];
        } else {
            // 上传失败返回错误信息
            return ['result' => false, 'error' => $file->getError()];
        }
    }
}