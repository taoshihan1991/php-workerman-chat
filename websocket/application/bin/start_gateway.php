<?php 
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link http://www.workerman.net/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */
use \Workerman\Worker;
use \Workerman\WebServer;
use \GatewayWorker\Gateway;
// 自动加载类
require_once __DIR__ . '/../../vendor/autoload.php';
$config = include __DIR__ . '/../../config.php';

if ($config['is_open_ssl']) {
    // gateway 进程，Websocket协议实现webIM
    $gateway = new Gateway("Websocket://0.0.0.0:" . $config['ws_port'], $config['context']);
    $gateway->transport = 'ssl';
} else {
    // gateway 进程，Websocket协议实现webIM
    $gateway = new Gateway("Websocket://0.0.0.0:" . $config['ws_port']);
}

// gateway名称，status方便查看
$gateway->name = 'wsAppGateway';
// gateway进程数
$gateway->count = $config['gateway_worker'];
// 本机ip，分布式部署时使用内网ip
$gateway->lanIp = '127.0.0.1';
// 内部通讯起始端口，假如$gateway->count=4，起始端口为4000
// 则一般会使用4000 4001 4002 4003 4个端口作为内部通讯端口 
$gateway->startPort = 2900;
// 服务注册地址
$gateway->registerAddress = '127.0.0.1:1238';

// 心跳间隔
$gateway->pingInterval = 30;
// 连续次数
$gateway->pingNotResponseLimit = 2;
// 心跳数据
$gateway->pingData = '';

// 如果不是在根目录启动，则运行runAll方法
if(!defined('GLOBAL_START')) {
    Worker::runAll();
}

