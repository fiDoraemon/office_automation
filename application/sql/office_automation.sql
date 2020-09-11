/*
Navicat MySQL Data Transfer

Source Server         : localhost
Source Server Version : 100119
Source Host           : localhost:3306
Source Database       : office_automation

Target Server Type    : MYSQL
Target Server Version : 100119
File Encoding         : 65001

Date: 2020-09-11 10:51:52
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for oa_attachment
-- ----------------------------
DROP TABLE IF EXISTS `oa_attachment`;
CREATE TABLE `oa_attachment` (
  `attachment_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '附件号',
  `uploader_id` varchar(16) NOT NULL COMMENT '上传用户工号',
  `source_name` varchar(128) NOT NULL COMMENT '源文件名',
  `storage_name` varchar(128) NOT NULL COMMENT '现文件名',
  `file_size` int(11) unsigned NOT NULL COMMENT '文件大小（字节）',
  `save_path` varchar(128) NOT NULL COMMENT '保存路径',
  `upload_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '上传日期',
  PRIMARY KEY (`attachment_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1773 DEFAULT CHARSET=utf8 COMMENT='附件表';

-- ----------------------------
-- Table structure for oa_config_change
-- ----------------------------
DROP TABLE IF EXISTS `oa_config_change`;
CREATE TABLE `oa_config_change` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '流水号',
  `machine_id` int(10) unsigned NOT NULL COMMENT '样机号',
  `applicant_id` int(10) NOT NULL COMMENT '申请人工号',
  `change_description` varchar(255) NOT NULL COMMENT '变更描述',
  `approve_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '生效日期',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for oa_config_request
-- ----------------------------
DROP TABLE IF EXISTS `oa_config_request`;
CREATE TABLE `oa_config_request` (
  `request_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '请求号',
  `applicant_id` int(10) unsigned NOT NULL COMMENT '申请人工号',
  `current_assignee` int(10) NOT NULL COMMENT '当前处理人工号',
  `machine_id` int(10) unsigned NOT NULL COMMENT '样机号',
  `status` tinyint(1) unsigned NOT NULL COMMENT '申请单状态',
  `change_reason` varchar(255) NOT NULL COMMENT '变更理由',
  `change_description` varchar(512) NOT NULL COMMENT '变更描述',
  `apply_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '申请日期',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`request_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='存储每一个配置变更申请的全部信息，包括申请审批流程的状态信息。';

-- ----------------------------
-- Table structure for oa_config_request_process
-- ----------------------------
DROP TABLE IF EXISTS `oa_config_request_process`;
CREATE TABLE `oa_config_request_process` (
  `id` int(11) NOT NULL,
  `request_id` int(10) unsigned NOT NULL COMMENT '处理人工号',
  `handler_id` int(11) NOT NULL COMMENT '处理意见',
  `process_note` varchar(128) NOT NULL,
  `post_status` tinyint(1) unsigned NOT NULL COMMENT '处理后状态',
  `process_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '处理时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for oa_data
-- ----------------------------
DROP TABLE IF EXISTS `oa_data`;
CREATE TABLE `oa_data` (
  `data_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '数据号',
  `data_name` varchar(16) NOT NULL COMMENT '数据名称',
  `data_value` varchar(255) NOT NULL COMMENT '数据值',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`data_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='数据字典表';

-- ----------------------------
-- Table structure for oa_department
-- ----------------------------
DROP TABLE IF EXISTS `oa_department`;
CREATE TABLE `oa_department` (
  `department_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '部门号',
  `department_name` varchar(32) NOT NULL COMMENT '部门名称',
  `manager_id` varchar(16) NOT NULL COMMENT '部门管理者工号',
  `parent_id` int(10) unsigned NOT NULL COMMENT '上级部门id',
  `level` tinyint(2) unsigned NOT NULL DEFAULT '0' COMMENT '部门层级',
  `description` varchar(64) NOT NULL COMMENT '部门描述',
  `create_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`department_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COMMENT='部门表';

-- ----------------------------
-- Table structure for oa_login_record
-- ----------------------------
DROP TABLE IF EXISTS `oa_login_record`;
CREATE TABLE `oa_login_record` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '流水号',
  `user_id` varchar(16) NOT NULL COMMENT '用户工号',
  `login_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '登录时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=5259 DEFAULT CHARSET=utf8 COMMENT='登录记录表';

-- ----------------------------
-- Table structure for oa_machine
-- ----------------------------
DROP TABLE IF EXISTS `oa_machine`;
CREATE TABLE `oa_machine` (
  `machine_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '样机号',
  `machine_name` varchar(32) NOT NULL COMMENT '样机名称',
  `machine_model` varchar(16) NOT NULL COMMENT '机器型号',
  `machine_config` text NOT NULL COMMENT '样机配置信息',
  `create_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`machine_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='样机信息表';

-- ----------------------------
-- Table structure for oa_minute
-- ----------------------------
DROP TABLE IF EXISTS `oa_minute`;
CREATE TABLE `oa_minute` (
  `minute_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '会议号',
  `department_id` int(10) unsigned NOT NULL COMMENT '组织部门号',
  `minute_theme` varchar(64) NOT NULL COMMENT '会议主题',
  `project` varchar(32) NOT NULL COMMENT '项目代号',
  `minute_date` date NOT NULL COMMENT '会议日期',
  `minute_time` varchar(32) NOT NULL,
  `place` varchar(32) NOT NULL COMMENT '会议地点',
  `host_id` int(10) unsigned NOT NULL COMMENT '会议主持人工号',
  `attend_list` varchar(255) NOT NULL COMMENT '应到会人员',
  `attended_list` varchar(255) NOT NULL COMMENT '实际到会人员',
  `resolution` varchar(512) NOT NULL COMMENT '会议决议',
  `record` varchar(512) NOT NULL COMMENT '会议记录',
  `attachment_list` varchar(128) NOT NULL COMMENT '附件清单',
  `mission_list` varchar(128) NOT NULL COMMENT '相关任务清单',
  `minute_type` char(20) DEFAULT '普通会议' COMMENT '会议类型',
  `review_status` tinyint(1) NOT NULL COMMENT '评审状态',
  `project_stage` tinyint(1) NOT NULL COMMENT '项目阶段',
  `create_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`minute_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='会议纪要信息表';

-- ----------------------------
-- Table structure for oa_mission
-- ----------------------------
DROP TABLE IF EXISTS `oa_mission`;
CREATE TABLE `oa_mission` (
  `mission_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '任务号',
  `mission_title` varchar(64) NOT NULL COMMENT '任务标题',
  `reporter_id` varchar(16) NOT NULL COMMENT '发起人工号',
  `assignee_id` varchar(16) NOT NULL COMMENT '负责人工号',
  `finish_standard` varchar(512) DEFAULT NULL COMMENT '任务完成标准',
  `description` varchar(512) NOT NULL COMMENT '任务描述',
  `status` tinyint(2) unsigned NOT NULL DEFAULT '0' COMMENT '任务状态',
  `priority` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '任务优先级',
  `label` varchar(32) NOT NULL COMMENT '任务标签',
  `start_date` date NOT NULL COMMENT '任务开始日期',
  `finish_date` date NOT NULL COMMENT '任务结束日期',
  `interested_list` varchar(255) NOT NULL COMMENT '关注人工号清单',
  `related_project` varchar(32) NOT NULL COMMENT '关联项目代号',
  `project_control` tinyint(1) NOT NULL COMMENT '是否在项目控制下',
  `parent_mission_id` int(10) unsigned NOT NULL COMMENT '父任务id',
  `root_mission_id` int(10) unsigned NOT NULL COMMENT '关联根任务号',
  `create_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `related_requirement` int(11) unsigned NOT NULL COMMENT '关联需求号',
  `related_problem` int(11) unsigned NOT NULL COMMENT '关联故障专题号',
  `children_mission_list` varchar(255) NOT NULL COMMENT '子任务清单',
  `is_root_mission` tinyint(1) unsigned NOT NULL COMMENT '是否是根任务',
  `prior_mission_list` varchar(64) NOT NULL COMMENT '前置任务清单',
  PRIMARY KEY (`mission_id`),
  KEY `mission_id` (`mission_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='任务表';

-- ----------------------------
-- Table structure for oa_mission_process
-- ----------------------------
DROP TABLE IF EXISTS `oa_mission_process`;
CREATE TABLE `oa_mission_process` (
  `process_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '流水号',
  `mission_id` int(10) NOT NULL COMMENT '对应任务号',
  `handler_id` int(10) unsigned NOT NULL COMMENT '处理人工号',
  `process_note` varchar(512) NOT NULL COMMENT '任务处理信息',
  `post_status` tinyint(1) unsigned NOT NULL COMMENT '提交后状态',
  `attachment_list` varchar(128) NOT NULL COMMENT '附件清单',
  `dd_task_list` varchar(255) NOT NULL COMMENT '钉钉消息号清单，用来撤回消息',
  `process_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '处理时间',
  PRIMARY KEY (`process_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='任务处理记录表';

-- ----------------------------
-- Table structure for oa_project
-- ----------------------------
DROP TABLE IF EXISTS `oa_project`;
CREATE TABLE `oa_project` (
  `project_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '流水号',
  `project_code` varchar(16) NOT NULL COMMENT '项目代号',
  `project_name` varchar(32) NOT NULL COMMENT '项目名称',
  `project_goal` varchar(255) NOT NULL COMMENT '项目目标',
  `description` varchar(255) NOT NULL COMMENT '项目描述',
  `remark` varchar(128) NOT NULL COMMENT '项目备注',
  `update_user_id` varchar(16) NOT NULL COMMENT '当前修改人工号',
  `create_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`project_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COMMENT='项目信息表';

-- ----------------------------
-- Table structure for oa_right
-- ----------------------------
DROP TABLE IF EXISTS `oa_right`;
CREATE TABLE `oa_right` (
  `right_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '权限号',
  `right_name` varchar(32) NOT NULL COMMENT '权限名称',
  `description` varchar(64) NOT NULL COMMENT '权限描述',
  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`right_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for oa_role
-- ----------------------------
DROP TABLE IF EXISTS `oa_role`;
CREATE TABLE `oa_role` (
  `role_id` int(10) unsigned NOT NULL COMMENT '角色号',
  `role_name` varchar(32) NOT NULL COMMENT '角色名称',
  `description` varchar(64) NOT NULL COMMENT '角色描述',
  `right_list` varchar(128) NOT NULL COMMENT '权限清单',
  `create_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for oa_user
-- ----------------------------
DROP TABLE IF EXISTS `oa_user`;
CREATE TABLE `oa_user` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '流水号',
  `user_id` varchar(16) NOT NULL COMMENT '用户工号',
  `user_name` varchar(32) NOT NULL COMMENT '用户名字',
  `password` varchar(32) NOT NULL COMMENT '用户密码',
  `phone` varchar(16) NOT NULL COMMENT '用户手机号',
  `email` varchar(32) NOT NULL COMMENT '用户邮箱',
  `dd_userid` varchar(32) NOT NULL COMMENT '钉钉userid',
  `department_id` int(10) unsigned NOT NULL COMMENT '部门号',
  `user_status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '用户状态(1可用，0禁用)',
  `create_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户信息表';

-- ----------------------------
-- Table structure for oa_user_config
-- ----------------------------
DROP TABLE IF EXISTS `oa_user_config`;
CREATE TABLE `oa_user_config` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '流水号',
  `user_id` int(10) unsigned NOT NULL COMMENT '用户工号',
  `identifier` varchar(32) NOT NULL COMMENT '第二身份标识',
  `token` varchar(32) NOT NULL COMMENT '永久登录标识',
  `timeout` int(10) unsigned NOT NULL COMMENT '永久登录超时时间',
  `dd_open` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否开启钉钉通知',
  `view_mission_list` varchar(128) NOT NULL COMMENT '浏览过的任务清单',
  `interested_mission_list` varchar(512) NOT NULL COMMENT '关注任务清单',
  `temp_minute_data` varchar(512) NOT NULL COMMENT '保存的临时会议信息',
  `create_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户配置信息表';
