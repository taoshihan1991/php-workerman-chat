这是一款旧版本的PHP客服源码。

基于ThinkPHP5 + workerman，整体架构比较老，PHP客服端以及界面等需要在php-fpm下运行，即时通讯websocket服务端需要命令行执行。

## 源码下载

在下面链接中，下载zip压缩包

[https://gitee.com/taoshihan/php-workerman-chat](https://gitee.com/taoshihan/php-workerman-chat)

或者

git clone https://gitee.com/taoshihan/php-workerman-chat.git

composer install //如果有需要是否同意的操作，一路 y 到底

命令行进入websocket目录

composer install //如果有需要是否同意的操作，一路 y 到底

全新版本演示官网

[https://gofly.v1kf.com](https://gofly.v1kf.com)

## 配置文件

### 导入数据库

MySQL数据库创建数据库名称，字符集选utf8mb4

将项目根目录下的kefu.sql导入到MySQL数据库

### web服务
配置文件地址在，项目路径/config/database.php，配置MySQL链接信息

### websocket服务

配置文件地址在，项目路径/websocket/config.php，配置MySQL链接信息

## 服务启动

### websocket服务

windows系统 进入项目路径/websocket，双击start_for_win.bat

linux系统 进入项目路径/websocket，执行php start.php start

## 管理后台部署

### 配置nginx

此处参照普通PHP项目的配置方式，root路径配置到项目路径/public下
给runtime目录赋权限0777

### 伪静态配置

nginx配置以下伪静态设置，可以去除url中的index.php

```
if (!-e $request_filename) {
	rewrite ^/(.*)$ /index.php?s=$1 last;
	break;
}
```

## 后台地址

管理员后台：/admin/login/index.html   账号密码：admin/123

商户后台：/seller/login/index.html  账号密码：kefu2/123 或管理员创建

客服工作台：登录到商户后台，创建分组，创建客服账号，再点击左侧客服工作台，使用客服账号登录

## 特别声明
此代码为网络公开的客服系统源码，不保证可用性以及安全性，不能用于任何商业线上环境，仅供个人学习研究使用。

如果您有客服系统需求，可以来我官网gofly.v1kf.com，测试我完整独立开发的客服系统，基于golang语言，是一款高性能高可用功能全面的多商户客服系统。
