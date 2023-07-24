<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2019/2/17
 * Time: 11:03 AM
 */
namespace app\websocket;

use app\websocket\service\ApiServer;
use app\websocket\service\EventDispatch;
use GatewayWorker\Lib\Gateway;

/**
 * Worker 命令行服务类
 */
class Events
{
    public static function onWorkerStart($worker)
    {
        ApiServer::service();
    }

    /**
     * onWebSocketConnect 事件回调
     * 当客户端连接上gateway完成websocket握手时触发
     *
     * @param  integer  $client_id 断开连接的客户端client_id
     * @param  mixed    $data
     * @return void
     */
    public static function onWebSocketConnect($client_id, $data)
    {
        EventDispatch::auth($data, $client_id);
    }

    /**
     * onMessage 事件回调
     * 当客户端发来数据(Gateway进程收到数据)后触发
     *
     * @access public
     * @param  int       $client_id
     * @param  mixed     $data
     * @return void
     */
    public static function onMessage($client_id, $data)
    {
        $message = json_decode($data, true);

        switch ($message['cmd']) {
            // 客服初始化
            case 'init':
                EventDispatch::keFuInit($message, $client_id);
                break;
            case 'customerIn':
                EventDispatch::customerIn($message, $client_id);
                break;
            // 访客初始化
            case 'userInit':
                EventDispatch::userInit($message, $client_id);
                break;
            // 聊天
            case 'chatMessage':
                EventDispatch::chatMessage($message);
                break;
            // 转接
            case 'changeGroup':
                EventDispatch::reLink($message, $client_id);
                break;
            // 主动关闭访客
            case 'closeUser':
                EventDispatch::closeUser($message, $client_id);
                break;
            // 系统主动接待
            case 'linkByAuto':
                EventDispatch::linkCustomer($message, $client_id, 0);
                break;
            // 客服主动接待
            case 'linkByKF':
                EventDispatch::linkCustomer($message, $client_id, 1);
                break;
            case 'closeKf':

                break;
            case 'ping':
                Gateway::sendToClient($client_id, json_encode(['cmd' => 'pong']));
                break;
            // 常见问题
            case 'comQuestion':
                EventDispatch::commonQuestion($message, $client_id);
                break;
        }
    }

    /**
     * onClose 事件回调 当用户断开连接时触发的方法
     *
     * @param  integer $client_id 断开连接的客户端client_id
     * @return void
     */
    public static function onClose($client_id)
    {
        EventDispatch::closeClient($client_id);
    }
}
