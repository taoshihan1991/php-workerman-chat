这是一款旧版本的PHP客服源码。

基于ThinkPHP5 + workerman，整体架构比较老，PHP客服端以及界面等需要在php-fpm下运行，即时通讯websocket服务端需要命令行执行。

## web服务配置文件

配置文件地址在，项目路径/config/database.php

## websocket服务配置文件

配置文件地址在，项目路径/websocket/config.php

## websocket服务启动

windows系统 进入项目路径/websocket，双击start_for_win.bat
linux系统 进入项目路径/websocket，执行php start.php start

## 特别声明
此代码为网络公开的客服系统源码，不保证可用性以及安全性，不能用于任何商业线上环境，仅供个人学习研究使用。

如果您有客服系统需求，可以来我官网gofly.v1kf.com，测试我完整独立开发的客服系统，基于golang语言，是一款高性能高可用功能全面的多商户客服系统。
