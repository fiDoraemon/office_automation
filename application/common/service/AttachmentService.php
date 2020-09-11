<?php
/**
 * Created by PhpStorm.
 * User: TZX
 * Date: 2020/9/9
 * Time: 16:46
 */

namespace app\common\service;

use app\common\Result;
use app\common\model\Attachment;

class AttachmentService
{
    // 文件上传
    public static function fileUpload($file) {

        $fileInfo = $file->getInfo();
        // 验证并移动文件（最大 20 MB）
        $info = $file->validate(['size' => 20971520])->move(ROOT_PATH . 'public' . DS . 'upload');

        if ($info) {
            // 插入附件信息
            $attachment = new Attachment([
                'uploader_id' => '3330011',
                'source_name' => $fileInfo['name'],
                'storage_name' => $info->getFilename(),
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