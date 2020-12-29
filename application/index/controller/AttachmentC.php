<?php

namespace app\index\controller;

use app\index\model\Attachment;
use app\common\Result;
use app\index\service\AttachmentService;
use think\Controller;
use think\Request;
use think\Session;

class AttachmentC extends Controller
{
    /**
     * 单个文件上传
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        $file = $request->file('file');
        if($file && !is_array($file)) {
            $fileInfo = $file->getInfo();
            // 验证并移动文件（最大 100 MB）
            $info = $file->validate(['size' => 104857600])->move(ROOT_PATH . 'public/upload');           // 最大100M
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
                return Result::returnResult(Result::SUCCESS, ['id' => $attachment->attachment_id]);
            } else {
                // 上传失败返回错误信息
                return Result::returnResult(Result::ERROR, ['error' => $file->getError()]);
            }
        } else {
            // 如果上传文件不存在或有多个文件
            return Result::returnResult(Result::UPLOAD_ERROR);
        }
    }

    /**
     * 多个文件上传
     */
    public function multipleUpload() {
        $file = $this->request->file('file');
    }

    /**
     * 删除指定附件
     * @param $id
     * @return array
     * @throws \think\exception\DbException
     */
    public function delete($id)
    {
        $attachment = Attachment::get($id);
        if($attachment) {
            $filePath = ROOT_PATH . 'public/upload/' . $attachment->save_path;
            $attachment->delete();          // 删除附件信息
            unlink($filePath);          // 删除附件真实文件

            return Result::returnResult(Result::SUCCESS);
        } else {
            return Result::returnResult(Result::DELETE_ATTACHMENT);
        }
    }
}
