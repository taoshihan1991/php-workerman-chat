<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2019/6/21
 * Time: 8:59 PM
 */
namespace app\socketio\service;

use Workerman\Worker;
use PHPSocketIO\SocketIO;

class Server
{
    // 在线客服
    public static $onlineKeFu = [];
    // 在线访客
    public static $onlineCustomer = [];

    public static function run()
    {
        $port = config('service_socketio.socket_port');
        $httpPort = config('service_socketio.http_port');

        if (config('service_socketio.is_open_ssl')) {

            $io = new SocketIO($port, config('service_socketio.context'));
        } else {
            $io = new SocketIO($port);
        }

        $io->on("workerStart", function ($socket) use($httpPort) {

            // 监听一个http端口
            $http = new Worker('http://0.0.0.0:' . $httpPort);
            $http->reusePort = true;

            // 当http客户端发来数据时触发
            $http->onMessage = function($connection, $data) {

                $message = $data['post'];
                switch ($message['cmd']) {
                    // api访客连接
                    case 'link':
                        HttpEvent::link(static::$onlineKeFu, $message, $connection);
                        break;
                    // api访客聊天
                    case 'c2sChat':
                        HttpEvent::c2sChat(static::$onlineKeFu, $message, $connection);
                        break;
                    // api访客转接
                    case 'changeGroup':
                        HttpEvent::changeGroup(static::$onlineKeFu, $message, $connection);
                        break;
                    // api用户关闭
                    case 'closeUser':
                        HttpEvent::closeUser($message, $connection);
                        break;
                }
            };

            // 执行监听
            $http->listen();
        });

        $io->on('connection', function($socket) {

            $socket->addedUser = false;

            // 客服登录
            $socket->on('init', function ($data, $callback) use($socket) {
                Event::init(static::$onlineKeFu, $data, $callback, $socket);
            });

            // 访客进入
            $socket->on('customerIn', function ($data, $callback) use($socket) {
                Event::customerIn(static::$onlineCustomer, $data, $callback, $socket);
            });

            // 访客连接客服
            $socket->on('userInit', function ($data, $callback) use($socket) {
                Event::userInit(static::$onlineCustomer, static::$onlineKeFu, $data, $callback, $socket);
            });

            // 访客直接联系客服
            $socket->on('directLinkKF', function ($data, $callback) use ($socket) {
                Event::directLinkKF(static::$onlineCustomer, static::$onlineKeFu, $data, $callback, $socket);
            });

            // 聊天
            $socket->on('chatMessage', function ($data, $callback) use($socket) {
                Event::chatMessage(static::$onlineCustomer, static::$onlineKeFu, $data, $callback, $socket);
            });

            // 处理已读未读
            $socket->on('readMessage', function ($data, $callback) use($socket) {
                Event::readMessage(static::$onlineCustomer, static::$onlineKeFu, $data, $callback, $socket);
            });

            // 关闭访客
            $socket->on('closeUser', function ($data, $callback) use($socket) {
                Event::closeUser(static::$onlineCustomer, $data, $callback);
            });

            // 常见问题
            $socket->on('comQuestion', function ($data, $callback) use($socket) {
                Event::comQuestion($data, $callback);
            });

            // 处理转接
            $socket->on('changeGroup', function ($data, $callback) use($socket) {
                Event::changeGroup(static::$onlineCustomer, static::$onlineKeFu, $data, $callback, $socket);
            });

            // 访客 或者 客服退出
            $socket->on('disconnect', function () use($socket) {
                Event::disconnect(static::$onlineCustomer, static::$onlineKeFu, $socket);
            });

            // 手动接待访客
            $socket->on('linkByKF', function ($data, $callback) use($socket) {
                Event::linkByKF(static::$onlineCustomer, static::$onlineKeFu, $data, $callback, $socket);
            });

            // 评价
            $socket->on('praiseKf', function ($data, $callback) use($socket) {
                Event::praiseKf(static::$onlineCustomer, $data, $callback);
            });

            // 访客正在输入
            $socket->on('typing', function ($data) {
                Event::typing(static::$onlineKeFu, $data);
            });
        });

        Worker::runAll();
    }
}