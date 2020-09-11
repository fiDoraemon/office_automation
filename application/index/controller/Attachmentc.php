<?php

namespace app\index\controller;

use app\common\model\Attachment;
use app\common\Result;
use app\common\service\AttachmentService;
use think\Controller;
use think\Request;

class Attachmentc extends Controller
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        //
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create()
    {
        //
    }

    /**
     * 保存新建的资源
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        $file = $this->request->file('file');

        if($file && !is_array($file)) {         // 如果上传文件不存在或有多个文件
            $data = AttachmentService::fileUpload($file);
            if($data['result'] == true) {
                return Result::returnResult(Result::SUCCESS, ['id' => $data['id']]);
            } else {
                return Result::returnResult(Result::ERROR, ['error' => $data['error']]);
            }
        } else {
            return Result::returnResult(Result::UPLOAD_ERROR);
        }
    }

    /**
     * 显示指定的资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function read($id)
    {
        //
    }

    /**
     * 显示编辑资源表单页.
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * 保存更新的资源
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * 删除指定资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function delete($id)
    {
        //
    }
}
