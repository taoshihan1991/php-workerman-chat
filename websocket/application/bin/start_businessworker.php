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
use \GatewayWorker\BusinessWorker;

// 自动加载类
require_once __DIR__ . '/../../vendor/autoload.php';
$config = include __DIR__ . '/../../config.php';

// bussinessWorker 进程
$worker = new BusinessWorker();
$worker->eventHandler = 'app\service\Events';
// worker名称
$worker->name = 'wsBusinessWorker';
// bussinessWorker进程数量
$worker->count = $config['business_worker'];
// 服务注册地址
$worker->registerAddress = '127.0.0.1:1238';

// 如果不是在根目录启动，则运行runAll方法
if(!defined('GLOBAL_START')) {
    Worker::runAll();
}

