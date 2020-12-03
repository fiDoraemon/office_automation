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

class AttachmentService
{
    /*
     * 文件上传
     * $type：文件类型，比如 mission
     */
    public static function fileUpload($file) {

        $fileInfo = $file->getInfo();
        // 验证并移动文件（最大 50 MB）
        $info = $file->validate(['size' => 52428800])->move(ROOT_PATH . 'public/upload');

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
            // 上传失败获取错误信息
            return ['result' => false, 'error' => $file->getError()];
        }
    }
}