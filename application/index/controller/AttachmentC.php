<?php

namespace app\index\controller;

use app\index\model\Attachment;
use app\common\Result;
use app\index\service\AttachmentService;
use think\Controller;
use think\Request;

class AttachmentC extends Controller
{
    /**
     * 保存新建的文件
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        $file = $request->file('file');
        if($file && !is_array($file)) {
            $data = AttachmentService::fileUpload($file);
            if($data['result']) {
                return Result::returnResult(Result::SUCCESS, ['id' => $data['id']]);
            } else {
                return Result::returnResult(Result::ERROR, ['error' => $data['error']]);
            }
        } else {
            // 如果上传文件不存在或有多个文件
            return Result::returnResult(Result::UPLOAD_ERROR);
        }
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
