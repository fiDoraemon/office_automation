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
use app\index\model\DocCodeCount;
use app\index\model\DocFile;
use app\index\model\DocRequest;
use app\index\model\Project;
use app\index\model\ProjectStageInfo;
use app\index\model\User;
use app\index\model\UserRole;
use think\Db;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;
use think\Request;
use think\Session;

/**
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
    public function getProCOdeAndAuthor(){
        //查询项目代号类型
        $listCodes = $this -> getProjectCode();
        //查询所有评审人
        $listAuthor =  $this -> getAllAuthor();
        //是否为文控
        $isDocAdmin = $this -> isDocAdmin();
        $resultArray = [
            "projectCodes"   => $listCodes,          //项目类型
            "authors"        => $listAuthor,         //作者
            "isDocAdmin"     => $isDocAdmin          //是否为文控
        ];
        return Result::returnResult(Result::SUCCESS,$resultArray);
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
    public function getAllDocFile($limit = 15,$page = 1){
        $docFile = new DocFile();
        $count = $docFile -> where("status", 1) -> count();
        $fileList = $docFile -> where("status", 1)
                             -> field("request_id,file_code,save_name,source_name,upload_time,path")
                             -> order("upload_time","desc")
                             -> page($page, $limit)
                             -> select();
        foreach ($fileList as $file){
            $file -> author = $file -> request -> requestUser -> user_name;
            $file -> project = $file -> request -> projectCode -> project_code;
            $file -> stage = $file -> request -> stage;
            $file -> remark = $file -> request -> remark;
            unset($file -> request);
        }
        return Result::returnResult(Result::SUCCESS,$fileList,$count);
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
    public function getDocFileOfCondition($projectCode = 0 , $projectStage = "", $author = "",$limit = 15,$page = 1){
        $docReq = new DocRequest();
        if($projectCode != 0){
            $docReq -> where("project_id", $projectCode);
        }
        if($projectStage != ""){
            $docReq -> where("stage","like" , "$projectStage%");
        }
        if($author != ""){
            $docReq-> where("author_id", $author);
        }
        $reqIdList = $docReq -> where("status", 1)
                             -> column("request_id");
        $docFile = new DocFile();
        $count = $docFile -> where("request_id","in", $reqIdList)
                          -> where("status",1)
                          -> count();
        $fileList = $docFile -> where("request_id","in", $reqIdList)
                             -> where("status",1)
                             -> field("request_id,file_code,save_name,source_name,upload_time,path")
                             -> order("upload_time","desc")
                             -> page($page, $limit)
                             -> select();
        foreach ($fileList as $file){
            $file -> author = $file -> request -> requestUser -> user_name;
            $file -> project = $file -> request -> projectCode -> project_code;
            $file -> stage = $file -> request -> stage;
            $file -> remark = $file -> request -> remark;
            unset($file -> request);
        }
        return Result::returnResult(Result::SUCCESS,$fileList,$count);
    }

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
    public function getDocFileOfKeyword($keyword = "",$limit = 15,$page = 1){
        //获取符合remark条件的文件id集合
        $remarkList = $this -> getFileIdOfRemark($keyword);
        $nameAndCodeList = $this -> getFileIdOfCodeOrName($keyword);
        $result = array_merge($remarkList,$nameAndCodeList);
        $result = array_unique($result);
        $count = count($result);
        $docFile = new DocFile();
        $fileList = $docFile -> where("id", "in",$result)
                             -> field("request_id,file_code,save_name,source_name,upload_time,path")
                             -> order("upload_time","desc")
                             -> page($page, $limit)
                             -> select();
        foreach ($fileList as $file){
            $file -> author = $file -> request -> requestUser -> user_name;
            $file -> project = $file -> request -> projectCode -> project_code;
            $file -> stage = $file -> request -> stage;
            $file -> remark = $file -> request -> remark;
            unset($file -> request);
        }
        return Result::returnResult(Result::SUCCESS,$fileList,$count);
    }

    /**
     * 根据request_id查询发起的文档审批信息
     * @param $requestId
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getRequestInfo($requestId){
        $info = Session::get("info");
        $userId = $info["user_id"];
        $docRequest = new DocRequest();
        $req = $docRequest -> where("request_id", $requestId)
                           -> field("request_id,author_id,approver_id,project_id,stage,remark,review_opinion,request_time,review_time,status")
                           -> find();
        $req -> requestUser;
        $req -> approverUser;
        $req -> projectCode;
        $req -> projectStage;
        $req -> author_name = $req -> requestUser -> user_name;
        $req -> approver_name = $req -> approverUser -> user_name;
        $req -> project_code = $req -> projectCode -> project_code;
//        $req -> project_stage = $req -> projectStage -> stage_name;
        //关联多个附件
        $req -> attachments;
        if(($userId === $req -> approver_id) && ($req -> status === 0) ){
            $req -> isAuthor = 1;
        }else{
            $req -> isAuthor = 0;
        }
        unset($req -> requestUser,$req -> approverUser,$req -> projectCode, $req -> projectStage,$req -> approver_id);
        return Result::returnResult(Result::SUCCESS,$req);
    }

    /**
     * 查询我发起的归档请求
     * @param int $limit
     * @param int $page
     * @return array
     * @throws \think\Exception
     */
    public function getAllRequest($limit = 15,$page = 1){
        $info = Session::get("info");
        $userId = $info["user_id"];
        $docRequest = new DocRequest();
        $docRequest -> where("author_id", $userId);
        $count = $docRequest->count();  //获取条件符合的总人数
        $docRequest -> where("author_id", $userId);
        try {
            $listRequest = $docRequest -> field("request_id,author_id,approver_id,project_id,stage,remark,request_time,status")
                                       -> order("request_time","desc")
                                       -> page($page, $limit)
                                       -> select();
            foreach ($listRequest as $req){
                $req -> requestUser;
                $req -> approverUser;
                $req -> projectCode;
                $req -> projectStage;
                $req -> author_name = $req -> requestUser -> user_name;
                $req -> approver_name = $req -> approverUser -> user_name;
                $req -> project_code = $req -> projectCode -> project_code;
                unset($req -> requestUser,$req -> approverUser,$req -> projectCode, $req -> projectStage);
            }
            return Result::returnResult(Result::SUCCESS,$listRequest,$count);
        } catch (DataNotFoundException $e) {
        } catch (ModelNotFoundException $e) {
        } catch (DbException $e) {
        }
    }

    /**
     * 根据文档关键字查询审批请求
     * @param int $limit
     * @param int $page
     * @param string $keyword
     * @return array
     * @throws \think\Exception
     */
    public function getRequestOfKeyword($limit = 15, $page = 1, $keyword = ""){
        $info = Session::get("info");
        $userId = $info["user_id"];
        $docRequest = new DocRequest();
        $docRequest -> where("author_id", $userId);
        if($keyword != ""){
            $docRequest -> where("remark","like", "%$keyword%");
        }
        $count = $docRequest->count();  //获取条件符合的总人数
        $docRequest -> where("author_id", $userId);
        if($keyword != ""){
            $docRequest -> where("remark","like", "%$keyword%");
        }
        try {
            $listRequest = $docRequest -> field("request_id,author_id,approver_id,project_id,stage,remark,request_time,status")
                                       -> order("request_time","desc")
                                       -> page($page, $limit)
                                       -> select();
            foreach ($listRequest as $req){
                $req -> requestUser;
                $req -> approverUser;
                $req -> projectCode;
                $req -> projectStage;
                $req -> author_name = $req -> requestUser -> user_name;
                $req -> approver_name = $req -> approverUser -> user_name;
                $req -> project_code = $req -> projectCode -> project_code;
//                $req -> project_stage = $req -> projectStage -> stage_name;
                unset($req -> requestUser,$req -> approverUser,$req -> projectCode, $req -> projectStage);
            }
            return Result::returnResult(Result::SUCCESS,$listRequest,$count);
        } catch (DataNotFoundException $e) {
        } catch (ModelNotFoundException $e) {
        } catch (DbException $e) {
        }
    }

    /**
     * 根据项目Code和项目阶段查询发起的评审
     * @param int $limit
     * @param int $page
     * @param int $projectCode
     * @param string $projectStage
     * @return array
     * @throws \think\Exception
     */
    public function getRequestOfProject($limit = 15, $page = 1, $projectCode = 0, $projectStage = ""){
        $info = Session::get("info");
        $userId = $info["user_id"];
        $docRequest = new DocRequest();
        $docRequest -> where("author_id", $userId);
        if($projectCode != 0){
            $docRequest -> where("project_id", $projectCode);
        }
        if($projectStage != ""){
            $docRequest -> where("stage", $projectStage);
        }
        $count = $docRequest->count();  //获取条件符合的总人数
        $docRequest -> where("author_id", $userId);
        if($projectCode != 0){
            $docRequest -> where("project_id", $projectCode);
        }
        if($projectStage != ""){
            $docRequest -> where("stage", $projectStage);
        }
        try {
            $listRequest = $docRequest -> field("request_id,author_id,approver_id,project_id,stage,remark,request_time,status")
                                       -> order("request_time","desc")
                                       -> page($page, $limit)
                                       -> select();
            foreach ($listRequest as $req){
                $req -> requestUser;
                $req -> approverUser;
                $req -> projectCode;
                $req -> projectStage;
                $req -> author_name = $req -> requestUser -> user_name;
                $req -> approver_name = $req -> approverUser -> user_name;
                $req -> project_code = $req -> projectCode -> project_code;
//                $req -> project_stage = $req -> projectStage -> stage_name;
                unset($req -> requestUser,$req -> approverUser,$req -> projectCode, $req -> projectStage);
            }
            return Result::returnResult(Result::SUCCESS,$listRequest,$count);
        } catch (DataNotFoundException $e) {
        } catch (ModelNotFoundException $e) {
        } catch (DbException $e) {
        }
    }

    /**
     * 查询待处理申请
     * @param int $limit
     * @param int $page
     * @return array
     * @throws \think\Exception
     */
    public function getRequestOfMyRequest($limit = 15, $page = 1){
        $info = Session::get("info");
        $userId = $info["user_id"];
        $docRequest = new DocRequest();
        $docRequest -> where("author_id", $userId);
        $docRequest -> where("status", 0);
        $count = $docRequest->count();  //获取条件符合的总人数
        $docRequest -> where("author_id", $userId);
        $docRequest -> where("status", 0);
        try {
            $listRequest = $docRequest -> field("request_id,author_id,approver_id,project_id,stage,remark,request_time,status")
                                       -> order("request_time","desc")
                                       -> page($page, $limit)
                                       -> select();
            foreach ($listRequest as $req){
                $req -> requestUser;
                $req -> approverUser;
                $req -> projectCode;
                $req -> projectStage;
                $req -> author_name = $req -> requestUser -> user_name;
                $req -> approver_name = $req -> approverUser -> user_name;
                $req -> project_code = $req -> projectCode -> project_code;
//                $req -> project_stage = $req -> projectStage -> stage_name;
                unset($req -> requestUser,$req -> approverUser,$req -> projectCode, $req -> projectStage);
            }
            return Result::returnResult(Result::SUCCESS,$listRequest,$count);
        } catch (DataNotFoundException $e) {
        } catch (ModelNotFoundException $e) {
        } catch (DbException $e) {
        }
    }

    /**
     * 需要我处理而又没有处理的申请
     * @param int $limit
     * @param int $page
     * @return array
     * @throws \think\Exception
     */
    public function getRequestOfMyReview($limit = 15, $page = 1){
        $info = Session::get("info");
        $userId = $info["user_id"];
        $docRequest = new DocRequest();
        $docRequest -> where("approver_id", $userId);
        $docRequest -> where("status", 0);
        $count = $docRequest->count();  //获取条件符合的总人数
        $docRequest -> where("approver_id", $userId);
        $docRequest -> where("status", 0);
        try {
            $listRequest = $docRequest -> field("request_id,author_id,approver_id,project_id,stage,remark,request_time,status")
                                       -> order("request_time","desc")
                                       -> page($page, $limit)
                                       -> select();
            foreach ($listRequest as $req){
                $req -> requestUser;
                $req -> approverUser;
                $req -> projectCode;
                $req -> projectStage;
                $req -> author_name = $req -> requestUser -> user_name;
                $req -> approver_name = $req -> approverUser -> user_name;
                $req -> project_code = $req -> projectCode -> project_code;
//                $req -> project_stage = $req -> projectStage -> stage_name;
                unset($req -> requestUser,$req -> approverUser,$req -> projectCode, $req -> projectStage);
            }
            return Result::returnResult(Result::SUCCESS,$listRequest,$count);
        } catch (DataNotFoundException $e) {
        } catch (ModelNotFoundException $e) {
        } catch (DbException $e) {
        }
    }

    /**
     * 保存发起的文档审批请求
     */
    public function saveRequest(){
        //开启事务
        Db::transaction(function () {
            $info = Session::get("info");
            $userId = $info["user_id"];
            $projectCode  = $_POST["projectCode"];      //所属项目
            $projectStage = $_POST["projectStage"];     //项目状态
            $approver     = $_POST["approver"];         //评审人
            $remark       = $_POST["remark"];           //文档描述
            $uploadList   = $_POST["uploadList"];       //上传的归档文件
            $docRequest = new DocRequest([
                'author_id'     =>  $userId,
                'approver_id'   =>  $approver,
                'project_id'    =>  $projectCode,
                'stage'         =>  $projectStage,
                'remark'        =>  $remark,
                'request_time'  =>  date('Y-m-d H:i:s', time()),
            ]);
            $result = $docRequest -> save();  //保存发起的评审
            foreach ($uploadList as $upload){
                $attachment = Attachment::get($upload);
                $attachment -> attachment_type = "doc";
                $attachment -> related_id = $docRequest -> request_id;
                $attachment->save();
            }
            //保存发起的评审文档
            if($result > 0){
                //发送钉钉消息给审批人
                $this -> sendRequestMessage($docRequest);
                return Result::returnResult(Result::SUCCESS, null);
            }
        });
        return Result::returnResult(Result::SUCCESS, null);
    }

    /**
     * 驳回文档归档申请
     */
    public function noPassRequest(){
        $requestId = $_POST["requestId"];
        $reviewOpinion = $_POST["reviewOpinion"];
        $info = Session::get("info");
        $userId = $info["user_id"];
        $req = DocRequest::get($requestId);
        if($req -> approver_id != $userId){
            return Result::returnResult(Result::NOT_MODIFY_PERMISSION, null);
        }
        $req -> review_opinion = $reviewOpinion;
        $req -> review_time = date('Y-m-d H:i:s', time());
        $req -> status = -1;
        $req -> save();
        //发送钉钉消息
        $this -> sendNoPassMessage($req);
        return Result::returnResult(Result::SUCCESS, null);
    }

    /**
     * 同意文档归档申请
     */
    public function passRequest(){
        $requestId = $_POST["requestId"];
        $reviewOpinion = $_POST["reviewOpinion"];
        $info = Session::get("info");
        $userId = $info["user_id"];
        $req = DocRequest::get($requestId);
        if($req -> approver_id != $userId){
            return Result::returnResult(Result::NOT_MODIFY_PERMISSION, null);
        }
        $req -> review_opinion = $reviewOpinion;
        $req -> review_time = date('Y-m-d H:i:s', time());
        $req -> status = 1;
        $req -> save();
        $fileList = $req -> attachments;
        foreach ($fileList as $file){
            $projectCode = $req -> projectCode -> project_code;
            //生成文档编码
            $docCode = $this -> getDocCode($projectCode,$req -> stage);
            $newPath = "doc-file/" . $projectCode . "/" .$req -> stage ;
            //添加归档的信息到已归档文件中
            $docFile = new DocFile();
            $docFile -> data([
                'request_id'  => $requestId,
                'file_code'   => $docCode,   //文件编码自动生成
                'save_name'   => $file -> storage_name,
                'source_name' => $file -> source_name,
                'upload_time' => date('Y-m-d H:i:s', time()),
                'version'     => 1,
                'path'        => $newPath . "/" . $file -> storage_name,
                'size'        => $file -> file_size,
                'status'      => 1,
            ]);
            $docFile->save();
            //移动文档文件到指定位置
            $this -> moveDocFile($file -> save_path, $newPath,$file -> storage_name);
        }
        //发送钉钉消息
        $this -> sendPassMessage($req);
        return Result::returnResult(Result::SUCCESS, null);
    }

    /**
     * 移动文件
     * @param $oldFilePath 旧文件地址
     * @param $newPath 新目录
     * @param $newFile 新文件名
     */
    private function moveDocFile($oldFilePath,$newPath,$newFile){
        $oldFilePath = ROOT_PATH . 'public/upload/' . $oldFilePath;
        $newPath = ROOT_PATH . 'public/upload/' . $newPath;
        if (!is_dir( $newPath)){
            mkdir($newPath,0777,true);
        }
        copy($oldFilePath, $newPath . "/" . $newFile);  //拷贝到新目录
        unlink($oldFilePath);                             //删除旧目录下的文件
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
     * 获取所有的审批人
     */
    private function getAllReviewer(){
        $userRole = new UserRole();
        try {
            $listReviewer = $userRole->where("role_id", 2)
                ->select();
            foreach ($listReviewer as $review){
                $review -> user_name =  $review -> user -> user_name;
                unset( $review -> user);
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
            $listReq = $docReq -> field("author_id")
                               -> distinct(true)
                               -> select();
            foreach ($listReq as $req){
                $req -> author_name = $req -> requestUser -> user_name;
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
        $docCodeCount = new DocCodeCount();
        $stageCount = $docCodeCount -> where(["project_code" => $projectCode,"stage" => $stagePre[0]])
                                    -> find();
        $count = $stageCount -> count;
        $count++;
        $stageCount -> count = $count;
        $stageCount -> save();
        $count = str_pad($count,4,"0",STR_PAD_LEFT);
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
     * 发送钉钉消息(发起申请给审批人发，驳回、通过给申请人发)
     */
    private function sendRequestMessage($req){
        $approverId = $req -> approver_id;
        $DDidList = User::where('user_id',$approverId)->column('dd_userid');
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
        $url = 'http://192.168.0.249/office_automation/public/static/layuimini/?requestId=' . $req -> request_id;
        $authorName = $req -> requestUser -> user_name;
        $remark = $req -> remark;
        $requestId =  $req -> request_id;
        $data = DataEnum::$msgData;
        $data['userList'] = $DDidList;
        $templet  = '▪ 申请人：'   . $authorName . "\n";
        $templet .= '▪ 文档描述：' . $remark  . "\n";
        $templet .= '▪ 文档列表：' . $fileList  . "\n";
        $templet .= '▪ 链接：' . $url;
        $message  = '◉ ' . '您有新文档审批需要处理！(#' . $requestId . ')' . "\n" . $templet;
        $data['data']['content'] = $message;
        $result = curlUtil::post($postUrl, $data);
        return true;
    }

    /**
     * 发送钉钉消息(发起申请给审批人发，驳回、通过给申请人发)
     */
    private function sendNoPassMessage($req){
        $authorId = $req -> author_id;
        $DDidList = User::where('user_id',$authorId)->column('dd_userid');
        $DDidList = implode(',',$DDidList);
        $fileList = Attachment::where(
            ['attachment_type' => 'doc',
            'related_id'       => $req -> request_id])
            -> column('source_name');
        if($fileList == null){
            $fileList = "";
        }else{
            $fileList = implode('，',$fileList);
        }
        $postUrl = 'http://www.bjzzdr.top/us_service/public/other/ding_ding_c/sendMessage';
        $url = 'http://192.168.0.249/office_automation/public/static/layuimini/?requestId=' . $req -> request_id;
        $approverName = $req -> approverUser -> user_name;
        $opinion = $req -> review_opinion;
        $requestId =  $req -> request_id;
        $data = DataEnum::$msgData;
        $data['userList'] = $DDidList;
        $templet  = '▪ 评审人：'   . $approverName . "\n";
        $templet .= '▪ 评审意见：' . $opinion . "\n";
        $templet .= '▪ 文档列表：' . $fileList . "\n";
        $templet .= '▪ 链接：' . $url;
        $message  = '◉ ' . '您的文档审批(#' . $requestId . ')被驳回！' . "\n" . $templet;
        $data['data']['content'] = $message;
        $result = curlUtil::post($postUrl, $data);
        return true;
    }

    /**
     * 发送钉钉消息(发起申请给审批人发，驳回、通过给申请人发)
     */
    private function sendPassMessage($req){
        $authorId = $req -> author_id;
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
        $url = 'http://192.168.0.249/office_automation/public/static/layuimini/?requestId=' . $req -> request_id;
        $approverName = $req -> approverUser -> user_name;
        $opinion = $req -> review_opinion;
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

}