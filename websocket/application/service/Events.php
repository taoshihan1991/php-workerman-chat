<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2020/5/17
 * Time: 9:07 PM
 */
namespace app\service;

use GatewayWorker\Lib\Gateway;
use Workerman\Worker;

class Events
{
    public static $db = null;

    public static $config = [];

    public static function onWorkerStart($worker)
    {
        self::$config = include __DIR__ . '/../../config.php';
        $dbConf = self::$config['database'];
        self::$db = new \Workerman\MySQL\Connection($dbConf['host'], $dbConf['port'],
            $dbConf['user'], $dbConf['password'], $dbConf['database']);


        // 监听一个http端口,提供api服务
        if($worker->id === 0) {
            $http = new Worker('http://0.0.0.0:' . self::$config['api_port']);
            $http->reusePort = true;

            // 当http客户端发来数据时触发
            $http->onMessage = function($connection, $data) {
                return HttpEvents::onMessage($connection, $data, self::$db);
            };

            // 执行监听
            $http->listen();
        }
    }

    public static function onMessage($clientId, $data)
    {
        $message = json_decode($data, true);
        switch ($message['cmd']) {

            // 访客进入
            case 'customerIn':
                SocketEvents::customerIn($clientId, $message['data'], self::$db);
                break;

            // 尝试分配客服给访客
            case 'userInit':
                SocketEvents::userInit($clientId, $data, self::$db);
                break;

            // 客服初始化进入
            case 'init':
                SocketEvents::init($clientId, $data, self::$db, self::$config);
                break;

            // 访客直接联系客服
            case 'directLinkKF':
                SocketEvents::directLinkKF($clientId, $data, self::$db);
                break;

            // 聊天
            case 'chatMessage':
                SocketEvents::chatMessage($clientId, $data, self::$db);
                break;

            // 处理已读未读
            case 'readMessage':
                SocketEvents::readMessage($data, self::$db);
                break;

            // 关闭访客
            case 'closeUser':
                SocketEvents::closeUser($data, self::$db);
                break;

            // 常见问题
            case 'comQuestion':
                SocketEvents::comQuestion($clientId, $data, self::$db);
                break;

            // 处理转接
            case 'changeGroup':
                SocketEvents::changeGroup($clientId, $data, self::$db);
                break;

            // 手动接待访客
            case 'linkByKF':
                SocketEvents::linkByKF($clientId, $data, self::$db);
                break;

            // 评价
            case 'praiseKf':
                SocketEvents::praiseKf($clientId, $data);
                break;

            // 访客正在输入
            case 'typing':
                SocketEvents::typing($data);
                break;

            // 消息撤回
            case 'rollBackMessage':
                SocketEvents::rollBackMessage($data, self::$db);
                break;

            // 心跳维持
            case 'ping':
                Gateway::sendToClient($clientId, json_encode([
                    'cmd' => 'pong'
                ]));
                break;
        }
    }

    public static function onClose($clientId)
    {
        SocketEvents::disConnect($clientId, self::$db);
    }
}