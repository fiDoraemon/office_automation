<?php

include dirname(__FILE__) . "/../../../extend/taobao-sdk-PHP/TopSdk.php";
date_default_timezone_set('Asia/Shanghai');

// 钉钉操作类
class DingDing
{
    // 企业id
    private $corpId = 'ding16a17f0d360322f635c2f4657eb6378f';

    // OA系统配置
    private $app = [
        'AgentId' => '619291922',
        'AppKey' => 'dingddzrt47ctvolkd3b',
        'AppSecret' => 'Kbz3cMdO_Y0B0DDGhXKYwoAkYF23JGiUwdIXCigcc-QGjVqGjPTqYQGUuNuhBSCP'
    ];

    private $accessToken;           // 凭证

    // 获取企业id
    public function getCorpId()
    {
        return $this->corpId;
    }

    // 获取凭证
    public function getAccessToken()
    {
        if($this->accessToken) {
            return $this->accessToken;
        }
        $c = new DingTalkClient(DingTalkConstant::$CALL_TYPE_OAPI, DingTalkConstant::$METHOD_GET, DingTalkConstant::$FORMAT_JSON);
        $req = new OapiGettokenRequest;

        $req->setAppkey($this->app['AppKey']);
        $req->setAppsecret($this->app['AppSecret']);
        $resp = $c->execute($req, null, "https://oapi.dingtalk.com/gettoken");

        if($resp->errcode == 0) {
            $this->accessToken = $resp->access_token;
            return $resp->access_token;
        } else {
            return false;
        }
    }

    // 根据授权码获取用户userid
    public function getNotLoginInfo($code)
    {
        $accessToken = $this->getAccessToken();
        $c = new DingTalkClient(DingTalkConstant::$CALL_TYPE_OAPI, DingTalkConstant::$METHOD_GET, DingTalkConstant::$FORMAT_JSON);
        $req = new OapiUserGetuserinfoRequest;

        $req->setCode($code);
        $resp = $c->execute($req, $accessToken, "https://oapi.dingtalk.com/user/getuserinfo");

        if($resp->errcode == 0) {
            return $resp->userid;
        } else {
            return false;
        }
    }

    // 获取所有用户 userid
    public function getAllUserId()
    {
        $accessToken = $this->getAccessToken();
        $userInfo = array();

        // 获取部门列表
        $c = new DingTalkClient(DingTalkConstant::$CALL_TYPE_OAPI, DingTalkConstant::$METHOD_GET , DingTalkConstant::$FORMAT_JSON);
        $req = new OapiDepartmentListRequest;
        $resp = $c->execute($req, $accessToken, "https://oapi.dingtalk.com/department/list");
        if($resp->errcode == 0) {
            $departmentList = $resp->department;

            foreach ($departmentList as $department) {
                $id = $department->id;
                // 获取部门下用户列表
                $c = new DingTalkClient(DingTalkConstant::$CALL_TYPE_OAPI, DingTalkConstant::$METHOD_GET, DingTalkConstant::$FORMAT_JSON);
                $req = new OapiUserSimplelistRequest;
                $req->setDepartmentId($id);
                $resp = $c->execute($req, $accessToken, "https://oapi.dingtalk.com/user/simplelist");
                if ($resp->errcode == 0) {
                    $userlist = $resp->userlist;
                    array_push($userInfo, $userlist);
                }
            }
        }

        return $userInfo;
    }

    /*
     * 发送钉钉消息
     * $userList 用户 userid 列表，以分号分隔
     * $data 数据数组
     */
    public function sendMessage($userList, $data)
    {
        $accessToken = $this->getAccessToken();
        $c = new DingTalkClient(DingTalkConstant::$CALL_TYPE_OAPI, DingTalkConstant::$METHOD_POST , DingTalkConstant::$FORMAT_JSON);
        $req = new OapiMessageCorpconversationAsyncsendV2Request;
        $req->setAgentId($this->app['AgentId']);
        $req->setUseridList($userList);
        $req->setMsg($this->getMsg($data));

        $resp = $c->execute($req, $accessToken, "https://oapi.dingtalk.com/topapi/message/corpconversation/asyncsend_v2");
    }

    /*
     * 获取消息对象
     *
     */
    public function getMsg($data) {
        $msg = new Msg;
        $msg->msgtype = "oa";
        $oa = new OA;
        $body = new Body;
        $body->author = "OA";
        $body->file_count = $data['file_count'];
        $forms = array();
        foreach ($data['detail'] as $item) {
            $form = new Form;
            $form->key = $item['key'];
            $form->value = $item['value'];
            array_push($forms, $form);
        }
        $body->form = array($form);
        $body->title = $data['title'];
        $oa->body = $body;
        $head = new Head;
        $head->bgcolor = "FFBBBBBB";
        $head->text = $data['head'];
        $oa->head = $head;
        $msg->oa = $oa;

        return $msg;
    }
}

// 示例
//$dingding = new DingDing();
//$data = [
//    'head' => 'OA通知',
//    'title' => '了解钉钉接口',
//    'detail'=> [
//        ['key' => '任务号', 'value' => '1'],
//        ['key' => '描述', 'value' => '学习下如何使用钉钉接口']
//    ],
//    'file_count' => 3
//];
//$dingding->sendMessage('15717987769981419', $data);

