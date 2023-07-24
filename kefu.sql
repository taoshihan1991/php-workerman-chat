--
-- Table structure for table `v2_admin`
--

DROP TABLE IF EXISTS `v2_admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `v2_admin` (
  `admin_id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_name` varchar(55) NOT NULL,
  `admin_password` varchar(155) NOT NULL,
  `last_login_time` datetime DEFAULT NULL COMMENT '上次登录时间',
  PRIMARY KEY (`admin_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `v2_admin`
--

LOCK TABLES `v2_admin` WRITE;
/*!40000 ALTER TABLE `v2_admin` DISABLE KEYS */;
INSERT INTO `v2_admin` VALUES (1,'admin','f08216bf9788d42c2718b5cebc738092','2023-04-12 20:50:31');
/*!40000 ALTER TABLE `v2_admin` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `v2_black_list`
--

DROP TABLE IF EXISTS `v2_black_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `v2_black_list` (
  `list_id` int(11) NOT NULL AUTO_INCREMENT,
  `seller_code` varchar(32) NOT NULL COMMENT '商户标识',
  `ip` varchar(15) NOT NULL COMMENT '黑名单ip',
  `oper_kefu_id` int(11) NOT NULL COMMENT '操作者id',
  `customer_name` varchar(55) DEFAULT NULL,
  `customer_id` varchar(32) DEFAULT NULL,
  `customer_real_name` varchar(55) DEFAULT NULL,
  `add_time` datetime DEFAULT NULL COMMENT '添加时间',
  PRIMARY KEY (`list_id`) USING BTREE,
  KEY `seller_code,ip` (`seller_code`,`ip`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `v2_black_list`
--

LOCK TABLES `v2_black_list` WRITE;
/*!40000 ALTER TABLE `v2_black_list` DISABLE KEYS */;
/*!40000 ALTER TABLE `v2_black_list` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `v2_chat_log`
--

DROP TABLE IF EXISTS `v2_chat_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `v2_chat_log` (
  `log_id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT '日志id',
  `from_id` varchar(32) NOT NULL COMMENT '网页用户随机编号(仅为记录参考记录)',
  `from_name` varchar(55) NOT NULL COMMENT '发送者名称',
  `from_avatar` varchar(155) NOT NULL COMMENT '发送者头像',
  `to_id` varchar(55) NOT NULL COMMENT '接收方',
  `to_name` varchar(55) NOT NULL COMMENT '接受者名称',
  `seller_code` varchar(32) NOT NULL COMMENT '所属 商户标识',
  `content` text NOT NULL COMMENT '发送的内容',
  `read_flag` tinyint(1) DEFAULT '1' COMMENT '是否已读 1 未读 2 已读 3 发送失败',
  `valid` tinyint(1) DEFAULT '1' COMMENT '是否有效 0 无效  1 有效',
  `create_time` datetime NOT NULL COMMENT '记录时间',
  PRIMARY KEY (`log_id`) USING BTREE,
  KEY `from_id` (`from_id`) USING BTREE,
  KEY `to_id` (`to_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=7394 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `v2_customer`
--

DROP TABLE IF EXISTS `v2_customer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `v2_customer` (
  `cid` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` varchar(32) NOT NULL COMMENT '访客id',
  `customer_name` varchar(55) NOT NULL COMMENT '访客名称',
  `customer_avatar` varchar(155) NOT NULL COMMENT '访客头像',
  `customer_ip` varchar(15) NOT NULL COMMENT '访客ip',
  `seller_code` varchar(32) NOT NULL COMMENT '咨询商家的标识',
  `pre_kefu_code` varchar(32) DEFAULT NULL COMMENT '上次服务的客服标识',
  `client_id` varchar(32) NOT NULL COMMENT '客户端标识',
  `online_status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '0 离线 1 在线',
  `create_time` datetime NOT NULL COMMENT '访问时间',
  `protocol` varchar(15) DEFAULT 'ws' COMMENT '接入协议',
  `province` varchar(55) DEFAULT NULL COMMENT '访客所在省份',
  `city` varchar(55) DEFAULT NULL COMMENT '访客所在城市',
  PRIMARY KEY (`cid`) USING BTREE,
  KEY `visiter` (`customer_id`) USING BTREE,
  KEY `time` (`create_time`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=698 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `v2_customer_info`
--

DROP TABLE IF EXISTS `v2_customer_info`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `v2_customer_info` (
  `info_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` varchar(32) NOT NULL,
  `seller_code` varchar(32) NOT NULL,
  `search_engines` varchar(55) DEFAULT NULL COMMENT '搜索引擎',
  `from_url` varchar(255) DEFAULT NULL,
  `real_name` varchar(55) DEFAULT NULL COMMENT '真实名称',
  `email` varchar(55) DEFAULT NULL COMMENT '邮箱',
  `phone` varchar(11) DEFAULT NULL COMMENT '手机号',
  `remark` text,
  `user_agent` varchar(255) DEFAULT NULL COMMENT '访客的设备头信息',
  PRIMARY KEY (`info_id`) USING BTREE,
  KEY `customer_id` (`customer_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=631 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `v2_customer_queue`
--

DROP TABLE IF EXISTS `v2_customer_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `v2_customer_queue` (
  `qid` int(11) NOT NULL AUTO_INCREMENT COMMENT '队列id',
  `customer_id` varchar(32) NOT NULL COMMENT '访客id',
  `customer_name` varchar(55) NOT NULL COMMENT '访客名称',
  `customer_avatar` varchar(155) NOT NULL COMMENT '访客头像',
  `customer_ip` varchar(15) NOT NULL COMMENT '访客ip',
  `seller_code` varchar(32) NOT NULL COMMENT '咨询商家的标识',
  `client_id` varchar(32) NOT NULL COMMENT '客户端标识',
  `create_time` datetime NOT NULL COMMENT '访问时间',
  PRIMARY KEY (`qid`) USING BTREE,
  UNIQUE KEY `id` (`customer_id`) USING BTREE,
  KEY `visiter` (`customer_id`) USING BTREE,
  KEY `time` (`create_time`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=437 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `v2_customer_queue`
--

LOCK TABLES `v2_customer_queue` WRITE;
/*!40000 ALTER TABLE `v2_customer_queue` DISABLE KEYS */;
/*!40000 ALTER TABLE `v2_customer_queue` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `v2_customer_service_log`
--

DROP TABLE IF EXISTS `v2_customer_service_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `v2_customer_service_log` (
  `service_log_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '服务编号',
  `customer_id` varchar(55) NOT NULL COMMENT '访客id',
  `client_id` varchar(32) NOT NULL COMMENT '访客的客户端标识',
  `customer_name` varchar(55) NOT NULL COMMENT '访客名称',
  `customer_avatar` varchar(155) NOT NULL COMMENT '访客头像',
  `customer_ip` varchar(15) NOT NULL COMMENT '访客的ip',
  `kefu_code` varchar(32) NOT NULL DEFAULT '0' COMMENT '接待的客服标识',
  `seller_code` varchar(32) NOT NULL COMMENT '客服所属的商户标识',
  `start_time` datetime NOT NULL COMMENT '开始服务时间',
  `end_time` datetime NOT NULL COMMENT '结束服务时间',
  `protocol` varchar(5) NOT NULL DEFAULT 'ws' COMMENT '来自什么类型的连接',
  PRIMARY KEY (`service_log_id`) USING BTREE,
  KEY `user_id,client_id` (`customer_id`,`client_id`) USING BTREE,
  KEY `kf_id,start_time,end_time` (`kefu_code`,`start_time`,`end_time`) USING BTREE,
  KEY `idx_search` (`seller_code`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=2375 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `v2_group`
--

DROP TABLE IF EXISTS `v2_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `v2_group` (
  `group_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '业务组id',
  `group_name` varchar(55) NOT NULL COMMENT '业务组名称',
  `group_status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '业务组状态 0 禁用 1 激活',
  `first_service` tinyint(1) NOT NULL DEFAULT '0' COMMENT '会否前置服务组 0 不是 1 是',
  `seller_id` int(11) NOT NULL COMMENT '所属商户id',
  `create_time` datetime NOT NULL COMMENT '创建时间',
  `update_time` datetime NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`group_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `v2_kefu`
--

DROP TABLE IF EXISTS `v2_kefu`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `v2_kefu` (
  `kefu_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '客服id',
  `kefu_code` varchar(32) NOT NULL COMMENT '客服唯一标识',
  `kefu_name` varchar(55) NOT NULL COMMENT '客服名称',
  `kefu_avatar` varchar(55) NOT NULL COMMENT '客服头像',
  `kefu_password` varchar(32) NOT NULL COMMENT '客服密码',
  `seller_id` int(11) NOT NULL COMMENT '所属商家id',
  `seller_code` varchar(32) NOT NULL COMMENT '所属商家标识',
  `group_id` int(11) NOT NULL COMMENT '所属业务组id',
  `max_service_num` int(11) NOT NULL DEFAULT '10' COMMENT '最大服务人数',
  `kefu_status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '客服状态 0 禁用 1 激活',
  `online_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '在线状态 0 离线 1 在线',
  `create_time` datetime NOT NULL COMMENT '创建时间',
  `update_time` datetime NOT NULL COMMENT '更新时间',
  `last_login_time` datetime DEFAULT NULL COMMENT '最近登录时间',
  PRIMARY KEY (`kefu_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;



--
-- Table structure for table `v2_kefu_distribution`
--

DROP TABLE IF EXISTS `v2_kefu_distribution`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `v2_kefu_distribution` (
  `distribute_id` int(11) NOT NULL AUTO_INCREMENT,
  `seller_id` int(11) NOT NULL COMMENT '商户的id',
  `kefu_map` longtext COMMENT '待分配的客服数组',
  PRIMARY KEY (`distribute_id`) USING BTREE,
  KEY `idx_seller_id` (`seller_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `v2_kefu_word`
--

DROP TABLE IF EXISTS `v2_kefu_word`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `v2_kefu_word` (
  `word_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(155) NOT NULL COMMENT '简略标题',
  `word` text NOT NULL COMMENT '常用语内容',
  `kefu_id` int(11) NOT NULL COMMENT '所属客服的id',
  `cate_id` int(11) NOT NULL COMMENT '所属分类id',
  `create_time` datetime NOT NULL COMMENT '创建时间',
  `update_time` datetime NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`word_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;



--
-- Table structure for table `v2_kefu_word_cate`
--

DROP TABLE IF EXISTS `v2_kefu_word_cate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `v2_kefu_word_cate` (
  `cate_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '分类id',
  `cate_name` varchar(255) DEFAULT NULL COMMENT '分类名称',
  `kefu_id` int(11) DEFAULT NULL COMMENT '所属客服的id',
  `seller_id` int(11) DEFAULT NULL COMMENT '所属商户的id',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`cate_id`) USING BTREE,
  KEY `idx_kf_seller` (`kefu_id`,`seller_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;



--
-- Table structure for table `v2_knowledge_store`
--

DROP TABLE IF EXISTS `v2_knowledge_store`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `v2_knowledge_store` (
  `knowledge_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '知识库id',
  `question` varchar(155) NOT NULL COMMENT '问题',
  `answer` varchar(255) NOT NULL COMMENT '答案',
  `cate_id` int(11) DEFAULT '1' COMMENT '所属业务分类id',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态 1 启用  2 禁用',
  `seller_id` int(11) NOT NULL COMMENT '所属商户id',
  `useful_num` int(11) DEFAULT '0' COMMENT '被标记有用数量',
  `useless_num` int(11) DEFAULT '0' COMMENT '被标记无用次数',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`knowledge_id`) USING BTREE,
  KEY `sellerid` (`seller_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;



--
-- Table structure for table `v2_leave_msg`
--

DROP TABLE IF EXISTS `v2_leave_msg`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `v2_leave_msg` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(155) NOT NULL COMMENT '留言人名称',
  `phone` char(11) NOT NULL COMMENT '留言人手机号',
  `content` varchar(255) NOT NULL COMMENT '留言内容',
  `seller_code` varchar(32) NOT NULL COMMENT '所属商户',
  `add_time` int(10) NOT NULL COMMENT '留言时间',
  `status` tinyint(1) DEFAULT '1' COMMENT '留言是否已读 1 未读 2 已读',
  `update_time` datetime DEFAULT NULL COMMENT '已读处理时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `v2_login_log`
--

DROP TABLE IF EXISTS `v2_login_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `v2_login_log` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '日志id',
  `login_user` varchar(55) NOT NULL COMMENT '登录用户',
  `login_ip` varchar(15) NOT NULL COMMENT '登录ip',
  `login_area` varchar(55) DEFAULT NULL COMMENT '登录地区',
  `login_user_agent` varchar(155) DEFAULT NULL COMMENT '登录设备头',
  `login_time` datetime DEFAULT NULL COMMENT '登录时间',
  `login_status` tinyint(1) DEFAULT '1' COMMENT '登录状态 1 成功 2 失败',
  PRIMARY KEY (`log_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `v2_now_service`
--

DROP TABLE IF EXISTS `v2_now_service`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `v2_now_service` (
  `service_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `kefu_code` varchar(32) NOT NULL COMMENT '客服标识',
  `customer_id` varchar(32) NOT NULL COMMENT '访客id',
  `client_id` varchar(32) NOT NULL COMMENT '访客的客户端id',
  `create_time` int(10) NOT NULL COMMENT '记录添加时间',
  `service_log_id` int(11) DEFAULT '0' COMMENT '当前服务的日志id',
  PRIMARY KEY (`service_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=2292 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `v2_operate_log`
--

DROP TABLE IF EXISTS `v2_operate_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `v2_operate_log` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '操作日志id',
  `operator` varchar(55) NOT NULL COMMENT '操作用户',
  `operator_ip` varchar(15) NOT NULL COMMENT '操作者ip',
  `operate_method` varchar(100) NOT NULL COMMENT '操作方法',
  `operate_title` varchar(55) NOT NULL COMMENT '操作简述',
  `operate_desc` varchar(255) DEFAULT NULL COMMENT '操作描述',
  `operate_time` datetime NOT NULL COMMENT '操作时间',
  PRIMARY KEY (`log_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `v2_praise`
--

DROP TABLE IF EXISTS `v2_praise`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `v2_praise` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` varchar(32) NOT NULL COMMENT '访客标识',
  `kefu_code` varchar(32) NOT NULL COMMENT '客服标识',
  `seller_code` varchar(32) NOT NULL COMMENT '商户的标识',
  `service_log_id` varchar(20) NOT NULL COMMENT '本次会话标识',
  `star` int(2) NOT NULL DEFAULT '0' COMMENT '分数',
  `add_time` datetime NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `seller` (`seller_code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `v2_praise`
--

LOCK TABLES `v2_praise` WRITE;
/*!40000 ALTER TABLE `v2_praise` DISABLE KEYS */;
/*!40000 ALTER TABLE `v2_praise` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `v2_question`
--

DROP TABLE IF EXISTS `v2_question`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `v2_question` (
  `question_id` int(11) NOT NULL AUTO_INCREMENT,
  `seller_code` varchar(32) NOT NULL COMMENT '所属商户的标识',
  `question` varchar(55) NOT NULL COMMENT '常见问题',
  `answer` varchar(255) NOT NULL COMMENT '答案',
  `add_time` datetime NOT NULL COMMENT '添加时间',
  PRIMARY KEY (`question_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `v2_question_conf`
--

DROP TABLE IF EXISTS `v2_question_conf`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `v2_question_conf` (
  `question_conf_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '常见问题设置id',
  `question_title` varchar(55) NOT NULL DEFAULT '猜您想问：' COMMENT '常见问题标题',
  `seller_code` varchar(32) NOT NULL COMMENT '所属商户标识',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1 启动 0 禁用',
  PRIMARY KEY (`question_conf_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `v2_seller`
--

DROP TABLE IF EXISTS `v2_seller`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `v2_seller` (
  `seller_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '商户id',
  `seller_code` varchar(32) NOT NULL COMMENT '商户唯一标识',
  `seller_name` varchar(55) NOT NULL COMMENT '商户名',
  `seller_password` varchar(32) NOT NULL COMMENT '商户登录密码',
  `seller_avatar` varchar(55) DEFAULT NULL COMMENT '商户头像',
  `seller_status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '商户状态 0 禁用 1 激活',
  `access_url` text NOT NULL COMMENT '接入域名',
  `valid_time` datetime DEFAULT NULL COMMENT '有效期',
  `max_kefu_num` int(5) DEFAULT '1' COMMENT '最大客服数',
  `max_group_num` int(5) DEFAULT '1' COMMENT '最大分组数',
  `create_index_flag` tinyint(1) DEFAULT '1' COMMENT '是否创建了 es索引 1:未创建 2:已创建',
  `create_time` datetime NOT NULL COMMENT '创建时间',
  `update_time` datetime NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`seller_id`) USING BTREE,
  UNIQUE KEY `seller_code` (`seller_code`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `v2_seller` (`seller_name` , `seller_password` , `seller_status` , `access_url` , `seller_code` , `valid_time` , `max_kefu_num` , `max_group_num` , `create_time` , `update_time`) VALUES ('kefu2' , 'f08216bf9788d42c2718b5cebc738092' , 1 , 'http://www.baidu.com' , '64bdf594c2f60' , '2023-08-23 11:52:52' , 1 , 1 , '2023-07-24 11:52:52' , '2023-07-24 11:52:52');

--
-- Table structure for table `v2_seller_box_style`
--

DROP TABLE IF EXISTS `v2_seller_box_style`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `v2_seller_box_style` (
  `box_style_id` int(11) NOT NULL AUTO_INCREMENT,
  `style_type` tinyint(1) DEFAULT '1' COMMENT '按钮样式 1 底部 2 侧边',
  `box_color` varchar(55) DEFAULT NULL COMMENT '弹层和按钮的颜色',
  `box_icon` int(3) DEFAULT '1' COMMENT '按钮图标',
  `box_title` varchar(155) DEFAULT NULL COMMENT '按钮显示咨询字样',
  `box_margin` int(4) DEFAULT NULL COMMENT '按钮边距',
  `seller_id` int(11) DEFAULT NULL COMMENT '关联的商户id',
  `create_time` datetime DEFAULT NULL COMMENT '创建 时间',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`box_style_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;



--
-- Table structure for table `v2_system`
--

DROP TABLE IF EXISTS `v2_system`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `v2_system` (
  `sys_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '系统设置id',
  `hello_word` text NOT NULL COMMENT '欢迎语',
  `seller_id` int(11) NOT NULL COMMENT '所属商家',
  `seller_code` varchar(32) NOT NULL COMMENT '商户标识',
  `hello_status` tinyint(1) NOT NULL COMMENT '是否启用欢迎语 0 不启用 1 启用',
  `relink_status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否转接 0 不启用 1 启用',
  `auto_link` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否自动接待 0 否 1 是',
  `auto_link_time` int(5) NOT NULL DEFAULT '30' COMMENT '自动接待间隔 单位s',
  `robot_open` tinyint(1) DEFAULT '0' COMMENT '是否开启机器人 0:关闭  1:开启',
  `pre_input` tinyint(1) DEFAULT '0' COMMENT '咨询前输入个人信息 0:否 1:是',
  `auto_remark` tinyint(1) DEFAULT '1' COMMENT '自动备注 0 关闭 1 打开',
  PRIMARY KEY (`sys_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `v2_system`
--

LOCK TABLES `v2_system` WRITE;
/*!40000 ALTER TABLE `v2_system` DISABLE KEYS */;
INSERT INTO `v2_system` VALUES (1,'<p>AI智服您身边的智能客服系统，以在线人工客服和智能机器人两大系统为基础，融合ACD（Automatic Call Distribution）技术和大数据分析，为各行业企业提供云端和系统自建的应用产品，以及整体在线营销与服务解决方案。</p><p><span style=\"font-size: 14px; color: rgb(127, 127, 127);\">AI智服不得用于任何违法犯罪目的，包括非法言论、网络黄赌毒和诈骗等违法行为，一旦发现将采取关停账号并移交相关司法机构等措施！</span></p>',1,'64bdf594c2f60',0,1,1,5,1,0,0),(7,'<p>测试欢迎他妈的测试成功666</p>',7,'643159f27f6df',0,0,1,0,0,0,1);
/*!40000 ALTER TABLE `v2_system` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `v2_unknown_question`
--

DROP TABLE IF EXISTS `v2_unknown_question`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `v2_unknown_question` (
  `question_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '位置问题id',
  `seller_id` int(11) NOT NULL COMMENT '关联的商户id',
  `question` varchar(255) NOT NULL COMMENT '未知问题',
  `customer_name` varchar(155) NOT NULL COMMENT '提问的访客',
  `create_time` datetime NOT NULL COMMENT '提问时间',
  PRIMARY KEY (`question_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;



--
-- Table structure for table `v2_word`
--

DROP TABLE IF EXISTS `v2_word`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `v2_word` (
  `word_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(155) NOT NULL COMMENT '简略标题',
  `word` text NOT NULL COMMENT '常用语内容',
  `seller_code` varchar(32) NOT NULL COMMENT '所属商户的标识',
  `cate_id` int(11) NOT NULL COMMENT '所属分类id',
  `create_time` datetime NOT NULL COMMENT '创建时间',
  `update_time` datetime NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`word_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `v2_word_cate`
--

DROP TABLE IF EXISTS `v2_word_cate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `v2_word_cate` (
  `cate_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '问题分类id',
  `cate_name` varchar(55) NOT NULL COMMENT '问题分类名称',
  `seller_id` int(11) NOT NULL COMMENT '所属商户id',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1 启用 2 禁用',
  PRIMARY KEY (`cate_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
