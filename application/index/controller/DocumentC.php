<?php
/**
 * Created by PhpStorm.
 * User: Conqin
 * Date: 2020/10/22
 * Time: 14:55
 */

namespace app\index\controller;


use app\common\Result;
use app\common\util\curlUtil;
use app\index\common\DataEnum;
use app\index\model\Attachment;
use app\index\model\DocBorrow;
use app\index\model\DocBorrowRequest;
use app\index\model\DocCodeCount;
use app\index\model\DocFile;
use app\index\model\DocFileVersion;
use app\index\model\DocRequest;
use app\index\model\DocUpgradeRequest;
use app\index\model\Project;
use app\index\model\ProjectStageInfo;
use app\index\model\User;
use app\index\model\UserRole;
use app\index\service\DocumentService;
use app\index\service\UserService;
use think\Db;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;
use think\Request;
use think\Session;

/**
 * 文件归档控制器
 * @property  request
 */
class DocumentC
{
    /**
     * 查询所有所属项目、审批人
     * @return array
     */
    public function getProCodeAndReviewer(){
        //查询项目代号类型
        $listCodes = $this -> getProjectCode();
        //查询所有评审人
        $listReviewer =  $this ->getAllReviewer();
        $resultArray = [
            "projectCodes"   => $listCodes,          //项目类型
            "reviewer"       => $listReviewer          //审批人
        ];
        return Result::returnResult(Result::SUCCESS,$resultArray);
    }

    /**
     * 获取项目代号和作者信息
     */
    public function getProCOdeAndAuthor()
    {
        //查询项目代号类型
        $listCodes = $this->getProjectCode();
        //查询所有评审人
        $listAuthor = $this->getAllAuthor();
        //是否为文控
        $isDocAdmin = $this->isDocAdmin();
        $resultArray = [
            "projectCodes" => $listCodes,          // 项目类型
            "authors" => $listAuthor,         // 作者
            "isDocAdmin" => $isDocAdmin          // 是否为文控
        ];
        return Result::returnResult(Result::SUCCESS, $resultArray);
    }

    /**
     * 已经归档的文件查询
     * @param int $limit
     * @param int $page
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws \think\Exception
     */
    public function getAllDocFile($limit = 15, $page = 1)
    {
        $docFile = new DocFile();
        $projectCode = input('get.projectCode');
        $projectStage = input('get.projectStage');
        $uploader = input('get.uploader');
        $keyword = input('get.keyword');
        // 查询文件总数
        $docFile->alias('df')->where("status", 1);
        // 项目代号
        if($projectCode) {
            $docFile->where('df.project_id', $projectCode);
        }
        // 项目阶段
        if($projectStage) {
            $docFile->where("project_stage", "like", "$projectStage%");         // TODO
        }
        // 上传者
        $condition = 'dfv.file_id = df.file_id and dfv.version = df.version';
        if($uploader) {
            $condition .= " and dfv.uploader_id = '$uploader'";
        }
        $condition2 = 'a.attachment_id = dfv.attachment_id';
        // 关键词（对文件名）
        if($keyword) {
            $condition2 .= " and a.source_name like '%$keyword%'";
        }
        $count = $docFile->join('oa_doc_file_version dfv', $condition)
            ->join('oa_attachment a', $condition2)->count();
        // 查询文件条目
        $docFile->alias('df')->where("status", 1);
        if($projectCode) {
          $docFile->where('df.project_id', $projectCode);
        }
        if($projectStage) {
            $docFile->where("project_stage", "like", "$projectStage%");
        }
        $condition = 'dfv.file_id = df.file_id and dfv.version = df.version';
        if($uploader) {
            $condition .= " and dfv.uploader_id = '$uploader'";
        }
        $condition2 = 'a.attachment_id = dfv.attachment_id';
        if($keyword) {
            $condition2 .= " and a.source_name like '%$keyword%'";
        }
        $fileList = $docFile->join('oa_doc_file_version dfv', $condition)
            ->join('oa_attachment a', $condition2)
            ->join('oa_project p', 'p.project_id = df.project_id')
            ->join('oa_user u', 'u.user_id = dfv.uploader_id')
            ->order("create_time desc")
            ->page($page, $limit)
            ->field("df.file_id,df.file_code,p.project_code,df.project_stage,df.create_time,dfv.description,dfv.version,u.user_name as uploader,a.source_name,a.save_path")
            ->select();

        return Result::returnResult(Result::SUCCESS, $fileList, $count);
    }

    /**
     * 根据条件查询文件
     * @param int $projectCode
     * @param string $projectStage
     * @param string $author
     * @param int $limit
     * @param int $page
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws \think\Exception
     */
//    public function getDocFileOfCondition($projectCode = 0 , $projectStage = "", $author = "",$limit = 15,$page = 1){
//        $docReq = new DocRequest();
//        if($projectCode != 0){
//            $docReq -> where("project_id", $projectCode);
//        }
//        if($projectStage != ""){
//            $docReq -> where("project_stage","like" , "$projectStage%");
//        }
//        if($author != ""){
//            $docReq-> where("applicant_id", $author);
//        }
//        $reqIdList = $docReq -> where("status", 1)
//                             -> column("request_id");
//        $docFile = new DocFile();
//        $count = $docFile -> where("request_id","in", $reqIdList)
//                          -> where("status",1)
//                          -> count();
//        $fileList = $docFile -> where("request_id","in", $reqIdList)
//                             -> where("status",1)
//                             -> field("request_id,file_code,save_name,source_name,upload_time,path")
//                             -> order("upload_time","desc")
//                             -> page($page, $limit)
//                             -> select();
//        foreach ($fileList as $file){
//            $file -> author = $file -> request -> requestUser -> user_name;
//            $file -> project = $file -> request -> projectCode -> project_code;
//            $file -> project_stage = $file -> request -> project_stage;
//            $file -> description = $file -> request -> description;
//            $file -> controlled = $file -> request -> controlled;
//            unset($file -> request);
//        }
//        return Result::returnResult(Result::SUCCESS,$fileList,$count);
//    }

    /**
     * 根据关键字查询归档文件
     * @param string $keyword 关键字可以为文档编码，文档名称，文档描述
     * @param int $limit
     * @param int $page
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws \think\Exception
     */
//    public function getDocFileOfKeyword($keyword = "",$limit = 15,$page = 1){
//        //获取符合remark条件的文件id集合
//        $remarkList = $this -> getFileIdOfRemark($keyword);
//        $nameAndCodeList = $this -> getFileIdOfCodeOrName($keyword);
//        $result = array_merge($remarkList,$nameAndCodeList);
//        $result = array_unique($result);
//        $count = count($result);
//        $docFile = new DocFile();
//        $fileList = $docFile -> where("id", "in",$result)
//                             -> field("request_id,file_code,save_name,source_name,upload_time,path")
//                             -> order("upload_time","desc")
//                             -> page($page, $limit)
//                             -> select();
//        foreach ($fileList as $file){
//            $file -> author = $file -> request -> requestUser -> user_name;
//            $file -> project = $file -> request -> projectCode -> project_code;
//            $file -> project_stage = $file -> request -> project_stage;
//            $file -> description = $file -> request -> description;
//            $file -> controlled = $file -> request -> controlled;
//            unset($file -> request);
//        }
//        return Result::returnResult(Result::SUCCESS,$fileList,$count);
//    }

    /**
     * 查询发起的申请信息
     * @param $requestId
     * @param $type 0归档申请，1升版申请，2借阅申请
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getRequestInfo($requestId, $type){
        $sessionUserId = Session::get("info")["user_id"];
        if($type == 0) {
            $docRequest = new DocRequest();
            $req = $docRequest -> where("request_id", $requestId)
                -> field("request_id,applicant_id,approver_id,project_id,project_stage,description,process_opinion,request_time,process_time,status")
                -> find();
            $req -> requestUser;
            $req -> approverUser;
            $req -> projectCode;
            $req -> projectStage;
            $req -> applicant_name = $req -> requestUser -> user_name;
            $req -> approver_name = $req -> approverUser -> user_name;
            $req -> project_code = $req -> projectCode -> project_code;
//        $req -> project_stage = $req -> projectStage -> stage_name;
            //关联多个附件
            $req -> attachments;
            if(($sessionUserId === $req -> approver_id) && ($req -> status === 0) ){
                $req -> isAuthor = 1;
            }else{
                $req -> isAuthor = 0;
            }
            unset($req -> requestUser,$req -> approverUser,$req -> projectCode, $req -> projectStage,$req -> approver_id);
            return Result::returnResult(Result::SUCCESS, $req);
        } else if($type == 1){
            // 获取升版申请详情
            $docUpgradeRequest = DocUpgradeRequest::get($requestId);
            // 获取项目代号和项目阶段
            $docFile = new DocFile();
            $file = $docFile->alias('df')->where('file_id', $docUpgradeRequest->file_id)->join('oa_project p', 'p.project_id = df.project_id')->field('project_code,project_stage')->find();
            $docUpgradeRequest->project_code = $file->project_code;
            $docUpgradeRequest->project_stage = $file->project_stage;
            $docUpgradeRequest->approver = UserService::userIdToName($docUpgradeRequest->approver_id);
            $docUpgradeRequest->applicant = UserService::userIdToName($docUpgradeRequest->applicant_id);
            $docUpgradeRequest->attachment;
            $requestDetail = $docUpgradeRequest->hidden(['file_id','applicant_id','approver_id'])->toArray();
            // 判断当前用户是否是升版审批人
            $isApprover = ($docUpgradeRequest->approver_id == $sessionUserId)? 1 : 0;
            $data = [
                'requestDetail' => $requestDetail,
                'isApprover' => $isApprover
            ];

            return Result::returnResult(Result::SUCCESS, $data);
        } else {
            // 获取借阅申请详情
            $docBorrowRequest = DocBorrowRequest::get($requestId);
            // 获取项目代号和项目阶段
            $docFile = new DocFile();
            $file = $docFile->alias('df')->where('file_id', $docBorrowRequest->file_id)->join('oa_project p', 'p.project_id = df.project_id')->field('df.description,project_code,project_stage')->find();
            $docBorrowRequest->project_code = $file->project_code;
            $docBorrowRequest->project_stage = $file->project_stage;
            $docBorrowRequest->description = $file->description;
            $docBorrowRequest->approver = $docBorrowRequest->approver_id? UserService::userIdToName($docBorrowRequest->approver_id) : '';
            $docBorrowRequest->applicant = UserService::userIdToName($docBorrowRequest->applicant_id);
            $docBorrowRequest->attachment = DocumentService::getFileAttachment($docBorrowRequest->file_id, $docBorrowRequest->version);
            $requestDetail = $docBorrowRequest->hidden(['file_id','applicant_id','approver_id'])->toArray();
            $data = [
                'requestDetail' => $requestDetail,
                'isDocAdmin' => $this->isDocAdmin()         // 判断当前用户是否是文控
            ];

            return Result::returnResult(Result::SUCCESS, $data);
        }
    }

    /**
     * 查询我发起的归档请求
     * @param int $limit
     * @param int $page
     * @return array
     * @throws \think\Exception
     */
    public function getAllRequest($limit = 15,$page = 1, $type = 0, $personType = 0){
        $sessionUserId = Session::get("info")["user_id"];
        $projectCode = input('get.projectCode');
        $projectStage = input('get.projectStage');
        $keyword = input('get.keyword');
        if ($type == 0) {
            $docRequest = new DocRequest();
            // 获取条件符合的总人数
            if ($projectCode) {         // 项目代号
                $docRequest->where("project_id", $projectCode);
            }
            if ($projectStage) {            // 项目阶段
                $docRequest->where("project_stage", $projectStage);
            }
            if($keyword) {          // 关键词
                $docRequest->where("description", 'like', "%$keyword%");
            }
            if(!$personType) {          // 我处理的/我申请的分类
                $docRequest->where("applicant_id", $sessionUserId);
            } else {
                $docRequest->where("approver_id", $sessionUserId);
            }
            $count = $docRequest->count();
            // 获取申请条目
            if ($projectCode) {
                $docRequest->where("project_id", $projectCode);
            }
            if ($projectStage) {
                $docRequest->where("project_stage", $projectStage);
            }
            if($keyword) {
                $docRequest->where("description", 'like', "%$keyword%");
            }
            if(!$personType) {          // 我处理的/我申请的分类
                $docRequest->where("applicant_id", $sessionUserId);
            } else {
                $docRequest->where("approver_id", $sessionUserId);
            }
            $listRequest = $docRequest->field("request_id,applicant_id,approver_id,project_id,project_stage,description,request_time,status")
                ->order("request_time", "desc")
                ->page($page, $limit)
                ->select();
            foreach ($listRequest as $req) {
                $req->requestUser;
                $req->approverUser;
                $req->projectCode;
                $req->projectStage;
                $req->applicant = $req->requestUser->user_name;
                $req->approver = $req->approverUser->user_name;
                $req->project_code = $req->projectCode->project_code;
                unset($req->requestUser, $req->approverUser, $req->projectCode, $req->projectStage);
            }
        } else if($type == 1){
            $docUpgradeRequest = new DocUpgradeRequest();
            $docUpgradeRequest->alias('dur');
            // 获取总数
            $condition = 'df.file_id = dur.file_id';
            if ($projectCode) {
                $condition .= " and df.project_id = $projectCode";
            }
            if ($projectStage) {
                $condition .= " and df.project_stage like '$projectStage%'";
            }
            if($keyword) {
                $docUpgradeRequest->where("dur.description", 'like', "%$keyword%");
            }
            if(!$personType) {
                $docUpgradeRequest->where("applicant_id", $sessionUserId);
            } else {
                $docUpgradeRequest->where("approver_id", $sessionUserId);
            }
            $count = $docUpgradeRequest->join('oa_doc_file df', $condition)->count();
            // 获取申请条目
            $docUpgradeRequest->alias('dur');
            $condition = 'df.file_id = dur.file_id';
            if ($projectCode) {
                $condition .= " and df.project_id = $projectCode";
            }
            if ($projectStage) {
                $condition .= " and df.project_stage like '$projectStage%'";
            }
            if($keyword) {
                $docUpgradeRequest->where("dur.description", 'like', "%$keyword%");
            }
            if(!$personType) {
                $docUpgradeRequest->where("applicant_id", $sessionUserId);
            } else {
                $docUpgradeRequest->where("approver_id", $sessionUserId);
            }
            $listRequest = $docUpgradeRequest->join('oa_doc_file df', $condition)
                ->join('oa_project p', 'p.project_id = df.project_id')
                ->join('oa_user u', 'u.user_id = dur.applicant_id')
                ->join('oa_user u2', 'u2.user_id = dur.approver_id')
                ->order('request_time', 'desc')
                ->page($page, $limit)
                ->field('dur.request_id,u.user_name as applicant,u2.user_name as approver,dur.description,dur.version,dur.request_time,dur.status,p.project_code,df.project_stage')
                ->select();
        } else {
            // 如果不是文控
            if(!$this->isDocAdmin()) {
                return Result::returnResult(Result::SUCCESS, []);
            }
            $docBorrowRequest = new DocBorrowRequest();
            // 获取总数
            $condition = 'df.file_id = dbr.file_id';
            if ($projectCode) {
                $condition .= " and df.project_id = $projectCode";
            }
            if ($projectStage) {
                $condition .= " and df.project_stage like '$projectStage%'";
            }
            if($keyword) {
                $condition .= " and df.description like '%$keyword%'";
            }
            $count = $docBorrowRequest->alias('dbr')->join('oa_doc_file df', $condition)->count();
            // 获取申请条目
            $condition = 'df.file_id = dbr.file_id';
            if ($projectCode) {
                $condition .= " and df.project_id = $projectCode";
            }
            if ($projectStage) {
                $condition .= " and df.project_stage like '$projectStage%'";
            }
            if($keyword) {
                $condition .= " and df.description like '%$keyword%'";
            }
            $listRequest = $docBorrowRequest->alias('dbr')->join('oa_doc_file df', $condition)
                ->join('oa_project p', 'p.project_id = df.project_id')
                ->join('oa_user u', 'u.user_id = dbr.applicant_id')
                ->join('oa_user u2', 'u2.user_id = dbr.approver_id')
                ->order('request_time', 'desc')
                ->page($page, $limit)
                ->field('dbr.request_id,u.user_name as applicant,u2.user_name as approver,df.description,dbr.version,dbr.request_time,dbr.status,p.project_code,df.project_stage')
                ->select();
        }

        return Result::returnResult(Result::SUCCESS, $listRequest, $count);
    }

    /**
     * 根据文档关键字查询审批请求
     * @param int $limit
     * @param int $page
     * @param string $keyword
     * @return array
     * @throws \think\Exception
     */
//    public function getRequestOfKeyword($limit = 15, $page = 1, $keyword = ""){
//        $sessionUserId = Session::get("info")["user_id"];
//        $docRequest = new DocRequest();
//        $docRequest -> where("applicant_id", $sessionUserId);
//        if($keyword != ""){
//            $docRequest -> where("remark","like", "%$keyword%");
//        }
//        $count = $docRequest->count();  //获取条件符合的总人数
//        $docRequest -> where("applicant_id", $sessionUserId);
//        if($keyword != ""){
//            $docRequest -> where("remark","like", "%$keyword%");
//        }
//        try {
//            $listRequest = $docRequest -> field("request_id,applicant_id,approver_id,project_id,project_stage,remark,request_time,status")
//                                       -> order("request_time","desc")
//                                       -> page($page, $limit)
//                                       -> select();
//            foreach ($listRequest as $req){
//                $req -> requestUser;
//                $req -> approverUser;
//                $req -> projectCode;
//                $req -> projectStage;
//                $req -> author_name = $req -> requestUser -> user_name;
//                $req -> approver_name = $req -> approverUser -> user_name;
//                $req -> project_code = $req -> projectCode -> project_code;
////                $req -> project_stage = $req -> projectStage -> stage_name;
//                unset($req -> requestUser,$req -> approverUser,$req -> projectCode, $req -> projectStage);
//            }
//            return Result::returnResult(Result::SUCCESS,$listRequest,$count);
//        } catch (DataNotFoundException $e) {
//        } catch (ModelNotFoundException $e) {
//        } catch (DbException $e) {
//        }
//    }

    /**
     * 根据项目Code和项目阶段查询发起的评审
     * @param int $limit
     * @param int $page
     * @param int $projectCode
     * @param string $projectStage
     * @return array
     * @throws \think\Exception
     */
//    public function getRequestOfProject($limit = 15, $page = 1, $requestCategory = 0, $projectCode = 0, $projectStage = "")
//    {
//        $sessionUserId = Session::get("info")["user_id"];
//        $docRequest = new DocRequest();
//        // 获取总数
//        $docRequest->where("applicant_id", $sessionUserId);
//        if ($projectCode != 0) {
//            $docRequest->where("project_id", $projectCode);
//        }
//        if ($projectStage != "") {
//            $docRequest->where("project_stage", $projectStage);
//        }
//        $count = $docRequest->count();
//        // 获取申请条目
//        $docRequest->where("applicant_id", $sessionUserId);
//        if ($projectCode != 0) {
//            $docRequest->where("project_id", $projectCode);
//        }
//        if ($projectStage != "") {
//            $docRequest->where("project_stage", $projectStage);
//        }
//        try {
//            $listRequest = $docRequest->field("request_id,applicant_id,approver_id,project_id,project_stage,description,request_time,status")
//                ->order("request_time", "desc")
//                ->page($page, $limit)
//                ->select();
//            foreach ($listRequest as $req) {
//                $req->requestUser;
//                $req->approverUser;
//                $req->projectCode;
//                $req->projectStage;
//                $req->author_name = $req->requestUser->user_name;
//                $req->approver_name = $req->approverUser->user_name;
//                $req->project_code = $req->projectCode->project_code;
////                $req -> project_stage = $req -> projectStage -> stage_name;
//                unset($req->requestUser, $req->approverUser, $req->projectCode, $req->projectStage);
//            }
//        } catch (DataNotFoundException $e) {
//        } catch (ModelNotFoundException $e) {
//        } catch (DbException $e) {
//        }
//        return Result::returnResult(Result::SUCCESS, $listRequest, $count);
//    }

    /**
     * 查询待处理申请
     * @param int $limit
     * @param int $page
     * @return array
     * @throws \think\Exception
     */
//    public function getRequestOfMyRequest($limit = 15, $page = 1){
//        $info = Session::get("info");
//        $userId = $info["user_id"];
//        $docRequest = new DocRequest();
//        $docRequest -> where("applicant_id", $userId);
//        $docRequest -> where("status", 0);
//        $count = $docRequest->count();  //获取条件符合的总人数
//        $docRequest -> where("applicant_id", $userId);
//        $docRequest -> where("status", 0);
//        try {
//            $listRequest = $docRequest -> field("request_id,applicant_id,approver_id,project_id,project_stage,description,request_time,status")
//                                       -> order("request_time","desc")
//                                       -> page($page, $limit)
//                                       -> select();
//            foreach ($listRequest as $req){
//                $req -> requestUser;
//                $req -> approverUser;
//                $req -> projectCode;
//                $req -> projectStage;
//                $req -> author_name = $req -> requestUser -> user_name;
//                $req -> approver_name = $req -> approverUser -> user_name;
//                $req -> project_code = $req -> projectCode -> project_code;
////                $req -> project_stage = $req -> projectStage -> stage_name;
//                unset($req -> requestUser,$req -> approverUser,$req -> projectCode, $req -> projectStage);
//            }
//            return Result::returnResult(Result::SUCCESS,$listRequest,$count);
//        } catch (DataNotFoundException $e) {
//        } catch (ModelNotFoundException $e) {
//        } catch (DbException $e) {
//        }
//    }

    /**
     * 需要我处理而又没有处理的申请
     * @param int $limit
     * @param int $page
     * @return array
     * @throws \think\Exception
     */
//    public function getRequestOfMyReview($limit = 15, $page = 1){
//        $sessionUserId = Session::get('info')['user_id'];
//        $docRequest = new DocRequest();
//        $docRequest -> where("approver_id", $sessionUserId);
//        $docRequest -> where("status", 0);
//        $count = $docRequest->count();  //获取条件符合的总人数
//        $docRequest -> where("approver_id", $sessionUserId);
//        $docRequest -> where("status", 0);
//        try {
//            $listRequest = $docRequest -> field("request_id,applicant_id,approver_id,project_id,stage,remark,request_time,status")
//                                       -> order("request_time","desc")
//                                       -> page($page, $limit)
//                                       -> select();
//            foreach ($listRequest as $req){
//                $req -> requestUser;
//                $req -> approverUser;
//                $req -> projectCode;
//                $req -> projectStage;
//                $req -> author_name = $req -> requestUser -> user_name;
//                $req -> approver_name = $req -> approverUser -> user_name;
//                $req -> project_code = $req -> projectCode -> project_code;
////                $req -> project_stage = $req -> projectStage -> stage_name;
//                unset($req -> requestUser,$req -> approverUser,$req -> projectCode, $req -> projectStage);
//            }
//            return Result::returnResult(Result::SUCCESS,$listRequest,$count);
//        } catch (DataNotFoundException $e) {
//        } catch (ModelNotFoundException $e) {
//        } catch (DbException $e) {
//        }
//    }

    /**
     * 保存请求
     */
    public function saveRequest()
    {
        // 开启事务
        return Db::transaction(function () {
            $sessionUserId = Session::get('info')['user_id'];
            $fields = input('post.');
            if($fields['type'] == 0) {
                $uploadList = explode(';', $fields["attachment_list"]);       // 上传的归档文件
                $docRequest = new DocRequest([
                    'applicant_id' => $sessionUserId,
                    'approver_id' => $fields["approver"],
                    'project_id' => $fields["project_id"],
                    'project_stage' => $fields["project_stage"],
                    'description' => $fields["description"],
                    'request_time' => date('Y-m-d H:i:s', time()),
                ]);
                $docRequest->save();
                // 处理附件列表
                foreach ($uploadList as $upload) {
                    $attachment = Attachment::get($upload);
                    $attachment->attachment_type = "doc";
                    $attachment->related_id = $docRequest->request_id;
                    $attachment->save();
                }
                // 发送钉钉消息给审批人
                $this->sendRequestMessage($docRequest);
            } else if($fields['type'] == 1) {
                // 增加升版申请
                $docFile = DocFile::get($fields['fileId']);
                $docUpgradeRequest = new DocUpgradeRequest();
                $docUpgradeRequest->data([
                    'applicant_id' => $sessionUserId,
                    'approver_id' => $fields['approver'],
                    'file_id' => $fields['fileId'],
                    'version' => $docFile->version + 1,
                    'description' => $fields['description'],
                ]);
                $docUpgradeRequest->save();
                // 关联附件
                $attachment = Attachment::get($fields['attachmentId']);
                $attachment->attachment_type = 'doc_upgrade';
                $attachment->related_id = $docUpgradeRequest->request_id;
                $attachment->save();
                // 发送钉钉消息给审批人
                $approver = User::get(['user_id' => $fields['approver']]);
                $applicant = User::get(['user_id' => $sessionUserId]);
                $postUrl = 'http://www.bjzzdr.top/us_service/public/other/ding_ding_c/sendMessage';
                $url = 'http://192.168.0.249/office_automation/public/static/layuimini/?requestType=1&requestId=' . $docUpgradeRequest->request_id;
                $data = DataEnum::$msgData;
                $data['userList'] = $approver->dd_userid;
                $templet = '▪ 申请人：' . $applicant->user_name . "\n";
                $templet .= '▪ 升版描述：' . $fields['description'] . "\n";
                $templet .= '▪ 文档名称：' . $attachment->source_name . '(第' . $docUpgradeRequest->version . '版)' . "\n";
                $templet .= '▪ 链接：' . $url;
                $message = '◉ ' . '您有新的文档升版申请(#' . $docUpgradeRequest->request_id . ')需要处理！' . "\n" . $templet;
                $data['data']['content'] = $message;
                $result = curlUtil::post($postUrl, $data);
            } else {
                $docBorrowRequest = new DocBorrowRequest();
                $returnResult = Result::returnResult(Result::ERROR);
                $today = date('Y-m-d H:i:s', time());
                //查询是否有已经提交过的借阅申请
                $borrowRequest = $docBorrowRequest->where('status', 0)
                    ->where("applicant_id", $sessionUserId)
                    ->where("file_id", $fields['fileId'])
                    ->where("version", $fields['version'])
                    ->order('request_time desc')
                    ->find();
                // 判断
                if ($borrowRequest) {
                    if($borrowRequest->effective_time == null) {            // 有效时间为空
                        $returnResult['msg'] = '已经提交过申请';
                        return $returnResult;
                    } else if($borrowRequest->effective_time > $today) {            // 有效时间大于现在
                        $returnResult['msg'] = '已经借阅过当前版本文档';
                        return $returnResult;
                    }
                }
                // 增加借阅信息
                $docBorrowRequest->data([
                    'file_id' => $fields['fileId'],
                    'version' => $fields['version'],
                    'applicant_id' => $sessionUserId
                ]);
                $docBorrowRequest->save();
                // 发送钉钉消息
                $this->sendBorrowMessage($docBorrowRequest->request_id);
            }

            return Result::returnResult(Result::SUCCESS);
        });
    }

    /**
     * 同意申请
     */
    public function passRequest()
    {
        $sessionUserId = Session::get('info')['user_id'];
        $requestId = input('post.requestId');
        $type = input('post.type');
        $processOpinion = input('post.processOpinion');

        if($type == 0) {
            $docRequest = DocRequest::get($requestId);
            // 如果当前用户不是审批人
            if ($docRequest->approver_id != $sessionUserId) {
                return Result::returnResult(Result::NOT_MODIFY_PERMISSION);
            }
            // 修改申请信息
            $docRequest->process_opinion = $processOpinion;
            $docRequest->process_time = date('Y-m-d H:i:s', time());
            $docRequest->status = 1;
            $docRequest->save();
            // 处理附件列表
            $fileList = $docRequest->attachments;
            foreach ($fileList as $file) {
                $projectCode = $docRequest->projectCode->project_code;
                // 生成文档编码
                $docCode = $this->getDocCode($projectCode, $docRequest->project_stage);
                $newPath = "doc-file/" . $projectCode . "/" . $docRequest->project_stage;
                // 增加新的归档文件
                $docFile = new DocFile();
                $docFile->data([
//                    'request_id' => $requestId,
                    'file_code' => $docCode,            // 文件编码自动生成
                    'description' => $docRequest->description,
//                    'save_name' => $file->storage_name,
//                    'source_name' => $file->source_name,
//                    'path' => $newPath . "/" . $file->storage_name,
//                    'size' => $file->file_size,
                    'project_id' => $docRequest->project_id,
                    'project_stage' => $docRequest->project_stage,
                    'create_time' => date('Y-m-d H:i:s', time()),
                ]);
                $docFile->save();
                // 增加文档第一个版本信息
                $docFileVersion = new DocFileVersion();
                $docFileVersion->data([
                    'file_id' => $docFile->file_id,
                    'request_id' => $docRequest->request_id,
                    'attachment_id' => $file->attachment_id,
                    'uploader_id' => $docRequest->applicant_id,
                    'description' => $docRequest->description
                ]);
                $docFileVersion->save();
                // 移动文档文件到指定位置
                $this->moveDocFile($file->save_path, $newPath, $file->storage_name);
                // 同时修改附件信息
                $file->save_path = $newPath;
                $file->save();
                // 发送钉钉消息
                $this->sendPassMessage($docRequest);
            }
        } else if($type == 1){
            $docUpgradeRequest = DocUpgradeRequest::get($requestId);
            $docFile = DocFile::get($docUpgradeRequest->file_id);
            // 查询附件
            $attachmentModel = new Attachment();
            $attachment = $attachmentModel->where('attachment_type', 'doc_upgrade')
                ->where('related_id', $docUpgradeRequest->request_id)->find();
            // 修改申请信息
            $docUpgradeRequest->status = 1;
            $docUpgradeRequest->process_opinion = $processOpinion;
            $docUpgradeRequest->process_time = date('Y-m-d H:i:s', time());
            $docUpgradeRequest->save();
            // 增加文档版本信息
            $docFileVersion = new DocFileVersion();
            $docFileVersion->data([
                'file_id' => $docUpgradeRequest->file_id,
                'request_id' => $docUpgradeRequest->request_id,
                'version' => $docFile->version + 1,
                'attachment_id' => $attachment->attachment_id,
                'uploader_id' => $docUpgradeRequest->applicant_id,
                'description' => $docUpgradeRequest->description
            ]);
            $docFileVersion->save();
            $docFile->version = $docFile->version + 1;
            $docFile->save();
            // 移动文档文件到指定位置
            $project = Project::get($docFile->project_id);
            $newPath = "doc-file/" . $project->project_code . "/" . $docFile->project_stage;
            $this->moveDocFile($attachment->save_path, $newPath, $attachment->storage_name);
            // 同时修改附件信息
            $attachment->save_path = $newPath;
            $attachment->save();
            // 发送钉钉消息
            $approver = User::get(['user_id' => $sessionUserId]);
            $applicant = User::get(['user_id' => $docUpgradeRequest->applicant_id]);
            $postUrl = 'http://www.bjzzdr.top/us_service/public/other/ding_ding_c/sendMessage';
            $url = 'http://192.168.0.249/office_automation/public/static/layuimini/?requestType=1&requestId=' . $docUpgradeRequest->request_id;
            $data = DataEnum::$msgData;
            $data['userList'] = $applicant->dd_userid;
            $templet = '▪ 处理人：' . $approver->user_name . "\n";
            $templet .= '▪ 处理意见：' . $processOpinion . "\n";
            $templet .= '▪ 文档名称：' . $attachment->source_name . '(第' . $docUpgradeRequest->version . '版)' . "\n";
            $templet .= '▪ 链接：' . $url;
            $message = '◉ ' . '您的文档升版申请(#' . $docUpgradeRequest->request_id . ')已通过' . "\n" . $templet;
            $data['data']['content'] = $message;
            $result = curlUtil::post($postUrl, $data);
        } else {
            // 修改申请信息
            $docBorrowRequest = DocBorrowRequest::get($requestId);
            $docBorrowRequest->data([
                'approver_id' => $sessionUserId,
                'status' => 1,
                'process_opinion' => $processOpinion,
                'process_time' => date('Y-m-d H:i:s', time()),
                'effective_time' => date('Y-m-d H:i:s', time() + 3600 * 24 * 30)   // 借阅有效期一个月
            ]);
            $docBorrowRequest->save();
            // 发送钉钉消息
            $this->passBorrowMsg($requestId);
        }

        return Result::returnResult(Result::SUCCESS);
    }

    /**
     * 驳回申请
     */
    public function noPassRequest()
    {
        $sessionUserId = Session::get('info')['user_id'];
        $requestId = input('post.requestId');
        $type = input('post.type');
        $processOpinion = input('post.processOpinion');

        if ($type == 0) {
            $docRequest = DocRequest::get($requestId);
            // 判断当前用户是否是审批人
            if ($docRequest->approver_id != $sessionUserId) {
                return Result::returnResult(Result::NOT_MODIFY_PERMISSION);
            }
            // 修改申请信息
            $docRequest->process_opinion = $processOpinion;
            $docRequest->status = 2;
            $docRequest->process_time = date('y-m-d H:i:s', time());
            $docRequest->save();
            // 发送钉钉消息
            $this->sendNoPassMessage($docRequest);
        } else if ($type == 1) {
            $docUpgradeRequest = DocUpgradeRequest::get($requestId);
            $docUpgradeRequest->process_opinion = $processOpinion;
            $docUpgradeRequest->status = 2;
            $docUpgradeRequest->process_time = date('y-m-d H:i:s', time());
            $docUpgradeRequest->save();
            // 查询附件
            $attachmentModel = new Attachment();
            $attachment = $attachmentModel->where('attachment_type', 'doc_upgrade')
                ->where('related_id', $docUpgradeRequest->request_id)->find();
            // 发送钉钉消息
            $approver = User::get(['user_id' => $sessionUserId]);
            $applicant = User::get(['user_id' => $docUpgradeRequest->applicant_id]);
            $postUrl = 'http://www.bjzzdr.top/us_service/public/other/ding_ding_c/sendMessage';
            $url = 'http://192.168.0.249/office_automation/public/static/layuimini/?requestType=1&requestId=' . $docUpgradeRequest->request_id;
            $data = DataEnum::$msgData;
            $data['userList'] = $applicant->dd_userid;
            $templet = '▪ 处理人：' . $approver->user_name . "\n";
            $templet .= '▪ 处理意见：' . $processOpinion . "\n";
            $templet .= '▪ 文档名称：' . $attachment->source_name . '(第' . $docUpgradeRequest->version . '版)' . "\n";
            $templet .= '▪ 链接：' . $url;
            $message = '◉ ' . '您的文档升版申请(#' . $docUpgradeRequest->request_id . ')被驳回' . "\n" . $templet;
            $data['data']['content'] = $message;
            $result = curlUtil::post($postUrl, $data);
        } else {
            // 修改申请信息
            $docBorrowRequest = DocBorrowRequest::get($requestId);
            $docBorrowRequest->data([
                'approver_id' => $sessionUserId,
                'status' => 2,
                'process_opinion' => $processOpinion,
                'process_time' => date('Y-m-d H:i:s', time())
            ]);
            $docBorrowRequest->save();
            // 发送钉钉消息
            $this -> noPassBorrowMsg($requestId);
        }

        return Result::returnResult(Result::SUCCESS);
    }


    /**
     * 同意借阅
     */
//    public function passBorrow(){
//        $borrowId = $_POST["borrowId"];
//        $docBorrow = DocBorrow::get($borrowId);
//        $docBorrow -> effective_time = date('Y-m-d H:i:s', time()+3600*24*30);
//        $docBorrow -> save();
//        $this -> passBorrowMsg($borrowId);
//        return Result::returnResult(Result::SUCCESS);
//    }

    /**
     * 反对借阅
     */
//    public function noPassBorrow(){
//        $borrowId = $_POST["borrowId"];
//        $docBorrow = DocBorrow::get($borrowId);
//        $docBorrow -> effective_time = $docBorrow -> request_time;
//        $docBorrow  -> save();
//        $this -> noPassBorrowMsg($borrowId);
//        return Result::returnResult(Result::SUCCESS);
//    }

    /**
     * 检查是否允许升版
     * @param $fileCode 文档编码
     * @param $version 版本
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
//    public function checkVersion($fileCode, $version){
//        $docFile = new DocFile();
//        $doc = $docFile -> where("file_code",$fileCode) -> field("version") -> order("version desc") -> find();
//        if($doc -> version == $version){
//            return Result::returnResult(Result::SUCCESS);
//        }
//        return Result::returnResult(Result::ERROR);
//    }

    /**
     * 根据id获取文档
     */
    public function getDocOfId($docId){
        $docFile = new DocFile();
        $doc = $docFile -> where("id",$docId)
            -> field("id,request_id,file_code,source_name")
            -> find();
        $doc -> request;
        return Result::returnResult(Result::SUCCESS,$doc);
    }

    /**
     * 移动文件
     * @param $oldFilePath 旧文件地址
     * @param $newPath 新目录
     * @param $newFile 新文件名
     */
    private function moveDocFile($oldFilePath,$newPath,$newFile){
        $oldFilePath = ROOT_PATH . 'public/upload/' . iconv('utf-8', 'gbk', $oldFilePath);          // windows 下需要转换编码
        $newPath = ROOT_PATH . 'public/upload/' . iconv('utf-8', 'gbk', $newPath);
        if (!is_dir( $newPath)){
            mkdir($newPath,0777,true);
        }
        copy($oldFilePath, $newPath . "/" . $newFile);          //拷贝到新目录
        unlink($oldFilePath);           //删除旧目录下的文件
    }

    /**
     * 查询所有项目代号
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function getProjectCode(){
        try {
            $listProjectCodes = Db::table('oa_project')
                -> field("project_id,project_code")
                -> select();
            return $listProjectCodes;
        } catch (DataNotFoundException $e) {
        } catch (ModelNotFoundException $e) {
        } catch (DbException $e) {
        }
    }

    /**
     * 查询所有项目状态
     * @param $projectId 所属项目ID
     * @return array
     */
    public function getProjectStage($projectId){
        $listStage = Project::where('project_id',$projectId) -> value('doc_stage');
        $label_array = explode(";",$listStage);
        return $label_array;
    }

    /**
     * 根据项目阶段前缀获取项目阶段后缀
     * @param $stagePre  项目阶段前缀
     * @return mixed
     */
    public function getProjectStageFix($stagePre){
        $listStageFix = ProjectStageInfo::where('project_stage_pre',$stagePre) -> column('project_stage_fix');
        return $listStageFix;
    }

    /**
     * 查询我借阅的文档
     */
//    public function getMyBorrow(){
//        $info   = Session::get("info");
//        $userId = $info["user_id"];
//        $docBorrow = new DocBorrow();
//        $requsetIdList = $docBorrow -> where("user_id",$userId)
//                                 -> where("effective_time", ">" , date('Y-m-d H:i:s', time()))
//                                 -> column("request_id");
//        $docFile = new DocFile();
//        $fileList = $docFile -> where("status", 1)
//                             -> where("request_id" ,"in", $requsetIdList)
//                             -> field("request_id,file_code,save_name,source_name,upload_time,path")
//                             -> order("upload_time","desc")
//                             -> select();
//        foreach ($fileList as $file){
//            $file -> author = $file -> request -> requestUser -> user_name;
//            $file -> project = $file -> request -> projectCode -> project_code;
//            $file -> stage = $file -> request -> stage;
//            $file -> remark = $file -> request -> remark;
//            unset($file -> request);
//        }
//        return Result::returnResult(Result::SUCCESS,$fileList);
//    }

    /**
     * 获取所有的文档审批申请
     */
//    public function getAllApproval(){
//        $docBorrow = new DocBorrow();
//        $approvalList = $docBorrow -> where("effective_time", null)
//                                   -> field("id,request_id,user_id,request_time")
//                                   -> select();
//        foreach ($approvalList as $approval){
//            $approval -> code      = $approval -> docFile -> file_code;
//            $approval -> name      = $approval -> docFile -> source_name;
//            $approval -> user_name = $approval -> user    -> user_name;
//            unset( $approval -> docFile, $approval -> user);
//        }
//        return Result::returnResult(Result::SUCCESS,$approvalList);
//    }

    /**
     * 获取所有的审批人
     */
    private function getAllReviewer()
    {
        $userRole = new UserRole();
        try {
            $listReviewer = $userRole->where("role_id", 2)
                ->select();
            foreach ($listReviewer as $review) {
                $review->user_name = $review->user->user_name;
                unset($review->user);
            }
            return $listReviewer;
        } catch (DataNotFoundException $e) {
        } catch (ModelNotFoundException $e) {
        } catch (DbException $e) {
        }
        return null;

    }

    /**
     * 查询所有作者信息
     */
    private function getAllAuthor(){
        $docReq = new DocRequest();
        try {
            $listReq = $docReq -> field("applicant_id")
                               -> distinct(true)
                               -> select();
            foreach ($listReq as $req){
                $req -> applicant_name = $req -> requestUser -> user_name;
                unset($req -> requestUser);
            }
            return $listReq;
        } catch (DataNotFoundException $e) {
        } catch (ModelNotFoundException $e) {
        } catch (DbException $e) {
        }
    }

    /**
     * 生成文档编码
     * @param $projectCode
     * @param $projectStage
     * @return string
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    private function getDocCode($projectCode,$projectStage){
        $stagePre = explode("-",$projectStage);
        $docCodeCount = DocCodeCount::get(['project_code' => $projectCode, 'stage' => $stagePre[0]]);
        if(!$docCodeCount) {
            $docCodeCount = new DocCodeCount();
            $docCodeCount->project_code = $projectCode;
            $docCodeCount->stage = $stagePre[0];
            $docCodeCount->count = 0;
        }
        $docCodeCount->count = $docCodeCount->count + 1;
        $docCodeCount -> save();
        $count = str_pad($docCodeCount->count,4,"0",STR_PAD_LEFT);
        return $projectCode . "-" . $projectStage . "-" . $count;
    }

    //test
//    public function pullDocCountInCache(){
//        $project = new Project();
//        $projectList = $project -> field("project_code,doc_stage")->select();
//        foreach ($projectList as $pro){
//            $code = $pro -> project_code;
//            $stageList = explode(";",$pro -> doc_stage);
//            foreach ($stageList as $s){
//               $codeCount = new DocCodeCount();
//                $codeCount -> project_code = $code;
//                $codeCount -> stage = $s;
//                $codeCount -> save();
//            }
//        }
//    }

    /**
     * 获取文档说明关键字的文件id
     * @param $keyword
     * @return array
     */
    private function getFileIdOfRemark($keyword){
        $docReq = new DocRequest();
        if($keyword != ""){
            $docReq -> where("remark","like","%$keyword%" );
        }
        $reqIdList = $docReq -> where("status", 1)
                             -> column("request_id");
        $docFile = new DocFile();
        $fileIdList = $docFile -> where("status",1)
                               -> where("request_id","in", $reqIdList)
                               -> column("id");
        return $fileIdList;
    }

    /**
     * 获取文档编码或者文件名字的文件id
     * @param $keyword
     * @return array
     */
    private function getFileIdOfCodeOrName($keyword){
        $docFile = new DocFile();
        $fileIdList = $docFile -> where("status",1)
                               -> where("file_code","like", "%$keyword%")
                               -> whereOr("source_name","like", "%$keyword%")
                               -> column("id");
        return $fileIdList;
    }

    /**
     * 判断是否为文件管理员（文控）
     * @return bool
     * @throws DbException
     */
    private function isDocAdmin(){
        $info = Session::get("info");
        $userId = $info["user_id"];
        $userRole = UserRole::get(["user_id" => $userId,"role_id" => 1]);
        if($userRole == null){
            return false;
        }
        return true;
    }

    /**
     * 归档请求发送钉钉消息(发起申请给审批人发，驳回、通过给申请人发)
     */
    private function sendRequestMessage($req)
    {
        $approverId = $req->approver_id;
        $DDidList = User::where('user_id', $approverId)->column('dd_userid');
        $DDidList = implode(',', $DDidList);
        $fileList = Attachment::where(['attachment_type' => 'doc',
            'related_id' => $req->request_id])
            ->column('source_name');
        if ($fileList == null) {
            $fileList = "";
        } else {
            $fileList = implode('，', $fileList);
        }
        $postUrl = 'http://www.bjzzdr.top/us_service/public/other/ding_ding_c/sendMessage';
        $url = 'http://192.168.0.249/office_automation/public/static/layuimini/?requestType=0&requestId=' . $req->request_id;
        $applicant = $req->requestUser->user_name;
        $description = $req->description;
        $requestId = $req->request_id;
        $data = DataEnum::$msgData;
        $data['userList'] = $DDidList;
        $templet = '▪ 申请人：' . $applicant . "\n";
        $templet .= '▪ 文档描述：' . $description . "\n";
        $templet .= '▪ 文档列表：' . $fileList . "\n";
        $templet .= '▪ 链接：' . $url;
        $message = '◉ ' . '您有新文档审批需要处理！(#' . $requestId . ')' . "\n" . $templet;
        $data['data']['content'] = $message;
        $result = curlUtil::post($postUrl, $data);
        return true;
    }

    /**
     * 归档请求不通过发送钉钉消息(发起申请给审批人发，驳回、通过给申请人发)
     */
    private function sendNoPassMessage($req)
    {
        $authorId = $req->applicant_id;
        $DDidList = User::where('user_id', $authorId)->column('dd_userid');
        $DDidList = implode(',', $DDidList);
        $fileList = Attachment::where(
            ['attachment_type' => 'doc',
                'related_id' => $req->request_id])
            ->column('source_name');
        if ($fileList == null) {
            $fileList = "";
        } else {
            $fileList = implode('，', $fileList);
        }
        $postUrl = 'http://www.bjzzdr.top/us_service/public/other/ding_ding_c/sendMessage';
        $url = 'http://192.168.0.249/office_automation/public/static/layuimini/?requestType=0&requestId=' . $req->request_id;
        $approverName = $req->approverUser->user_name;
        $opinion = $req->process_opinion;
        $requestId = $req->request_id;
        $data = DataEnum::$msgData;
        $data['userList'] = $DDidList;
        $templet = '▪ 评审人：' . $approverName . "\n";
        $templet .= '▪ 评审意见：' . $opinion . "\n";
        $templet .= '▪ 文档列表：' . $fileList . "\n";
        $templet .= '▪ 链接：' . $url;
        $message = '◉ ' . '您的文档审批(#' . $requestId . ')被驳回！' . "\n" . $templet;
        $data['data']['content'] = $message;
        $result = curlUtil::post($postUrl, $data);

        return true;
    }

    /**
     * 归档请求通过发送钉钉消息(发起申请给审批人发，驳回、通过给申请人发)
     */
    private function sendPassMessage($req){
        $authorId = $req -> applicant_id;
        $DDidList = User::where('user_id',$authorId)->column('dd_userid');
        $DDidList = implode(',',$DDidList);
        $fileList = Attachment::where(['attachment_type' => 'doc',
            'related_id' => $req -> request_id])
            -> column('source_name');
        if($fileList == null){
            $fileList = "";
        }else{
            $fileList = implode('，',$fileList);
        }
        $postUrl = 'http://www.bjzzdr.top/us_service/public/other/ding_ding_c/sendMessage';
        $url = 'http://192.168.0.249/office_automation/public/static/layuimini/?requestType=0&requestId=' . $req -> request_id;
        $approverName = $req -> approverUser -> user_name;
        $opinion = $req -> process_opinion;
        $requestId =  $req -> request_id;
        $data = DataEnum::$msgData;
        $data['userList'] = $DDidList;
        $templet  = '▪ 评审人：'   . $approverName . "\n";
        $templet .= '▪ 评审意见：' . $opinion . "\n";
        $templet .= '▪ 文档列表：' . $fileList . "\n";
        $templet .= '▪ 链接：' . $url;
        $message  = '◉ ' . '您的文档审批(#' . $requestId . ')已通过！' . "\n" . $templet;
        $data['data']['content'] = $message;
        $result = curlUtil::post($postUrl, $data);
        return true;
    }

    /**
     * 借阅请求发送钉钉消息(给所有文控发送钉钉消息)
     */
    private function sendBorrowMessage($borrowId){
        $docBorrowRequest = DocBorrowRequest::get($borrowId);
        $userIdList = UserRole::where('role_id',1) -> column('user_id');
        $DDidList = User::where('user_id','in',$userIdList) -> column('dd_userid');
        $DDidList = implode(',',$DDidList);
        $postUrl = 'http://www.bjzzdr.top/us_service/public/other/ding_ding_c/sendMessage';
        $url = 'http://192.168.0.249/office_automation/public/static/layuimini/?requestType=2&requestId=' . $borrowId;
        $data = DataEnum::$msgData;
        $data['userList'] = $DDidList;
        $requestName = Session::get("info")["user_name"];
        $templet  = '▪ 申请人：'   . $requestName . "\n";
        $templet .= '▪ 借阅文档编码：' .  $docBorrowRequest -> docFile -> file_code . "\n";
        $templet .= '▪ 借阅文档名称：' .  DocumentService::getFileAttachment($docBorrowRequest->file_id, $docBorrowRequest->version) . '(第' . $docBorrowRequest->version . '版)' . "\n";
        $templet .= '▪ 链接：' . $url;
        $message  = '◉ ' . '您有文档借阅申请(#' . $borrowId . ')待处理！' . "\n" . $templet;
        $data['data']['content'] = $message;
        $result = curlUtil::post($postUrl, $data);
        return true;
    }

    /**
     * 给借阅人发送同意借阅消息
     */
    private function passBorrowMsg($borrowId)
    {
        $docBorrowRequest = DocBorrowRequest::get($borrowId);
        $userDDid = User::where('user_id', $docBorrowRequest->user_id)->value('dd_userid');
        $postUrl = 'http://www.bjzzdr.top/us_service/public/other/ding_ding_c/sendMessage';
        $data = DataEnum::$msgData;
        $data['userList'] = $userDDid;
        $requestName = Session::get("info")["user_name"];
        $templet = '▪ 审批人：' . $requestName . "\n";
        $templet .= '▪ 借阅文档编码：' . $docBorrowRequest->docFile->file_code . "\n";
        $templet .= '▪ 借阅文档名称：' . DocumentService::getFileAttachment($docBorrowRequest->file_id, $docBorrowRequest->version) . '(第' . $docBorrowRequest->version . '版)' . "\n";
        $message = '◉ ' . '您的文档借阅申请(#' . $borrowId . ')已通过！' . "\n" . $templet;
        $data['data']['content'] = $message;
        $result = curlUtil::post($postUrl, $data);
        return true;
    }

    /**
     * 给借阅人发送反对借阅消息
     */
    private function noPassBorrowMsg($borrowId){
        $docBorrowRequest = DocBorrowRequest::get($borrowId);
        $userDDid = User::where('user_id',$docBorrowRequest -> user_id) -> value('dd_userid');
        $postUrl = 'http://www.bjzzdr.top/us_service/public/other/ding_ding_c/sendMessage';
        $data = DataEnum::$msgData;
        $data['userList'] = $userDDid;
        $requestName = Session::get("info")["user_name"];
        $templet  = '▪ 审批人：'   . $requestName . "\n";
        $templet .= '▪ 借阅文档编码：' .  $docBorrowRequest -> docFile -> file_code . "\n";
        $templet .= '▪ 借阅文档名称：' . DocumentService::getFileName($docBorrowRequest->file_id, $docBorrowRequest->version) . '(第' . $docBorrowRequest->version . '版)' . "\n";
        $message  = '◉ ' . '您的文档借阅申请(#' . $borrowId . ')已被驳回！' . "\n" . $templet;
        $data['data']['content'] = $message;
        $result = curlUtil::post($postUrl, $data);
        return true;
    }

    /*
     * 获取升版申请页面所需信息
     */
    public function createUpVersion($fileId) {
        $docFile = DocFile::get($fileId);
        // 获取文档审批人列表
        $userRole = new UserRole();
        $approverList = $userRole->alias('ur')->where('role_id', 2)->join('oa_user u', 'u.user_id = ur.user_id')->field('u.user_id,u.user_name')->select();

        $data = [
            'approverList' => $approverList,
            'fileCode' => $docFile->file_code,
            'version' => $docFile->version
        ];
        return Result::returnResult(Result::SUCCESS, $data);
    }

    /*
     * 获取文档版本信息
     */
    public function getFileVersion($fileId) {
        $sessionUserId = Session::get('info')['user_id'];
        $docFile = DocFile::get($fileId);
        $docFileVersion = new DocFileVersion();
        $fileVersionList = $docFileVersion->where('file_id', $fileId)
//            ->where('version', '<>', $docFile->version)
            ->order('version desc')
            ->field('id,version,attachment_id,uploader_id,description,create_time')
            ->select();
        foreach ($fileVersionList as $fileVersion) {
            $fileVersion->attachment;
            $fileVersion->uploader = UserService::userIdToName($fileVersion->uploader_id);
            $fileVersion->isBorrow = DocumentService::isBorrow($fileVersion->id);
            $fileVersion->isUploader = ($fileVersion->uploader_id == $sessionUserId)? 1 : 0;
            unset($fileVersion->id, $fileVersion->attachment_id, $fileVersion->uploader_id);
        }
        $data = [
            'fileCode' => $docFile->file_code,
            'fileVersionList' => $fileVersionList
        ];

        return Result::returnResult(Result::SUCCESS, $data);
    }
}