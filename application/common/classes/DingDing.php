<?php

include dirname(__FILE__) . "/../../../extend/taobao-sdk-PHP/TopSdk.php";
date_default_timezone_set('Asia/Shanghai');

// 钉钉操作类
class DingDing
{
    // 企业id
    private $corpId = 'ding16a17f0d360322f635c2f4657eb6378f';
    private $accessToken;           // 凭证
    private $app;           // 系统配置

    // OA系统配置
    private $app1 = [
        'AgentId' => '619291922',
        'AppKey' => 'dingddzrt47ctvolkd3b',
        'AppSecret' => 'Kbz3cMdO_Y0B0DDGhXKYwoAkYF23JGiUwdIXCigcc-QGjVqGjPTqYQGUuNuhBSCP'
    ];

    // 销售漏斗配置
    private $app2 = [
        'AppKey' => 'ding2medrtswtro9bxhv',
        'AppSecret' => 'Ec7mCxZGUooenCNjM1iveevNaMMf_culJepFsDbW5cSUCOFY_f-O-8pL1JJc5kfK'
    ];

    // 用服系统配置
    private $app3 = [
        'AppKey' => 'ding5q1rzl0s3svlkxwr',
        'AppSecret' => '3rMuIeQEoJki2hOjeQPTQGyTDG44HqEdd_JYCKOiyAWYwkxfcpfvULggXDaXBpBY'
    ];

    // 获取企业 id
    public function getCorpId()
    {
        return $this->corpId;
    }

    // 获取凭证
    public function getAccessToken($type = 1)
    {
        if($this->accessToken) {
            return $this->accessToken;
        }
        if($type == 1) {
            $this->app = $this->app1;
        } else if($type == 2) {
            $this->app = $this->app2;
        } else {
            $this->app = $this->app3;
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
    public function getNotLoginInfo($code, $type = 3)
    {
        $accessToken = $this->getAccessToken($type);
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
                    $userInfo = array_merge($userInfo, $userlist);
                }
            }
        }

        return $userInfo;
    }

    /*
     * 获取消息对象
     * $data 消息数据
     */
    public function getMsg($data) {
        $msg = new Msg;
        // oa
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
        $body->form = $forms;
        $body->title = $data['title'];
        $oa->body = $body;
        $head = new Head;
        $head->bgcolor = "FFBBBBBB";
        $head->text = $data['head'];
        $oa->head = $head;
        $msg->oa = $oa;
        // link
//        $msg->msgtype="link";
//        $link = new Link;
//        $link->message_url="https://www.baidu.com";
//        $link->text="121213aaaaaaa";
//        $link->title="1213";
//        $msg->link = $link;

        return $msg;
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

        if($resp->errcode == 0) {
            return true;
        } else {
            return false;
        }
    }
}

//$dingding = new DingDing();
//$data = [
//    'head' => 'OA通知',
//    'title' => '测试消息',
//    'detail'=> [
//        ['key' => '标题：', 'value' => '123'],
//        ['key' => '处理后状态：', 'value' => '123'],
//        ['key' => '链接：', 'value' =>  "[asd](https://www.baidu.com)"]
//    ],
//    'file_count' => 3
//];
//echo $dingding->sendMessage('15717987769981419', $data);

