<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2019/3/27
 * Time: 9:39 PM
 */
namespace app\websocket\service;

use app\model\BlackList;
use app\model\Customer;
use app\model\Service;
use app\model\Chat;
use app\model\ServiceLog;
use GatewayWorker\Lib\Gateway;
use Workerman\Worker;
use think\Db;

class ApiServer
{
    // api版本服务入口
    public static function service()
    {
        // 监听一个http端口
        $http = new Worker('http://0.0.0.0:2945');
        $http->reusePort = true;

        // 当http客户端发来数据时触发
        $http->onMessage = function($connection, $data) {
            return self::onMessage($connection, $data);
        };

        // 执行监听
        $http->listen();
    }

    /**
     * api 消息处理
     * @param $connection
     * @param $data
     * @return mixed
     */
    private static function onMessage($connection, $data)
    {
        $message = $data['post'];
        switch ($message['cmd']) {
            // api访客连接
            case 'link':
                self::customerLink($message, $connection);
                break;
            // api访客聊天
            case 'c2sChat':
                self::customerChat($message, $connection);
                break;
            // api访客转接
            case 'changeGroup':
                self::reLink($message, $connection);
                break;
            // api用户关闭
            case 'closeUser':
                self::closeUser($message, $connection);
                break;

        }
    }

    /**
     * 处理聊天消息
     * @param $message
     * @param $connection
     * @return array
     */
    private static function customerLink($message, $connection)
    {
        // 黑名单过滤
        $black = new BlackList();
        $isIn = $black->checkBlackList($message['data']['ip'], $message['seller_code']);
        if (0 == $isIn['code']) {
            return $connection->send(json_encode(['code' => 403, 'data' => '', 'msg' => '黑名单用户']));
        }

        $customerModel = new Customer();
        $customer = [
            'customer_id' => $message['data']['uid'],
            'customer_name' => $message['data']['name'],
            'customer_avatar' => $message['data']['avatar'],
            'customer_ip' => $message['data']['ip'],
            'seller_code' => $message['seller_code'],
            'client_id' => 0,
            'create_time' => date('Y-m-d H:i:s'),
            'online_status' => 1,
            'protocol' => 'http'
        ];

        // 记录服务日志
        $serviceLog = new ServiceLog();
        $logId = $serviceLog->addServiceLog([
            'customer_id' => $customer['customer_id'],
            'client_id' => 0,
            'customer_name' => $customer['customer_name'],
            'customer_avatar' => $customer['customer_avatar'],
            'customer_ip' => $customer['customer_ip'],
            'kefu_code' => ltrim($message['kefu_code'], 'KF_'),
            'seller_code' => $customer['seller_code'],
            'start_time' => date('Y-m-d H:i:s'),
            'protocol' => 'http'
        ]);

        // 通知客服连接访客
        $client = Gateway::getClientIdByUid($message['kefu_code']);
        if (!empty($client)) {

            $customer['log_id'] = $logId['data'];
            Gateway::sendToClient($client[0], json_encode([
                'cmd' => 'customerLink',
                'data' => $customer
            ]));

            unset($customer['log_id']);
        }

        // 记录服务数据
        $service = new Service();
        $service->addServiceCustomer(ltrim($message['kefu_code'], 'KF_'), $customer['customer_id'],
            $logId['data'], 0);

        $customer['pre_kefu_code'] = ltrim($message['kefu_code'], 'KF_');
        // 更新访客表
        $customerModel->updateCustomer($customer);

        return $connection->send(json_encode(['code' => 200, 'data' => '', 'msg' => 'ok']));
    }

    /**
     * 访客发送消息给客服
     * @param $message
     * @param $connection
     * @return mixed
     */
    private static function customerChat($message, $connection)
    {
        $client = Gateway::getClientIdByUid($message['data']['to_id']);

        if(!empty($client)) {
            $chat_message = [
                'cmd' => 'chatMessage',
                'data' => [
                    'name' => $message['data']['from_name'],
                    'avatar' => $message['data']['from_avatar'],
                    'id' => $message['data']['from_id'],
                    'time' => date('Y-m-d H:i:s'),
                    'content' => htmlspecialchars($message['data']['content']),
                    'protocol' => 'http'
                ]
            ];
            Gateway::sendToClient($client[0], json_encode($chat_message));
            unset($chat_message);
        }

        // 聊天信息入库
        $chatLog = new Chat();
        $chatLog->addChatLog([
            'from_id' => $message['data']['from_id'],
            'from_name' => $message['data']['from_name'],
            'from_avatar' => $message['data']['from_avatar'],
            'to_id' => $message['data']['to_id'],
            'to_name' => $message['data']['to_name'],
            'seller_code' => $message['data']['seller_code'],
            'content' => $message['data']['content'],
            'create_time' => date('Y-m-d H:i:s')
        ]);

        return $connection->send(json_encode(['code' => 200, 'data' => '', 'msg' => 'ok']));
    }

    /**
     * 转接访客
     * @param $message
     * @param $connection
     * @return bool
     */
    public static function reLink($message, $connection)
    {
        Db::startTrans();
        try {

            // 上一次服务的客服设置结束时间，并开启本次服务客服的log
            $service = new Service();
            $serviceInfo = $service->getServiceInfo(ltrim($message['data']['from_kefu_id'], 'KF_'),
                $message['data']['customer_id']);

            $clientId = Gateway::getClientIdByUid($message['data']['from_kefu_id']);
            if(empty($serviceInfo['data']) && !empty($clientId)) {
                Gateway::sendToClient($clientId['0'], json_encode([
                    'cmd' => 'error',
                    'data' => [
                        'msg' => '转接失败'
                    ]
                ]));

                return false;
            }

            $log = new ServiceLog();
            $log->updateEndTime($serviceInfo['data']['service_log_id']);

            $logId = $log->addServiceLog([
                'customer_id' => $message['data']['customer_id'],
                'client_id' => $serviceInfo['data']['client_id'],
                'customer_name' => $message['data']['customer_name'],
                'customer_avatar' => $message['data']['customer_avatar'],
                'customer_ip' => $message['data']['customer_ip'],
                'kefu_code' => $message['data']['to_kefu_id'],
                'seller_code' => $message['data']['seller_code'],
                'start_time' => date('Y-m-d H:i:s'),
                'protocol' => 'http'
            ]);

            if(0 != $logId['code'] && !empty($clientId)) {
                Gateway::sendToClient($clientId['0'], json_encode([
                    'cmd' => 'error',
                    'data' => [
                        'msg' => '转接失败'
                    ]
                ]));

                Db::rollback();

                return false;
            }

            // 更新当前服务的客服id 为转接的客服id 和 新的 log id
            $service->addServiceCustomer(
                ltrim($message['data']['from_kefu_id'], 'KF_'),
                $message['data']['customer_id'],
                $logId['data'],
                $serviceInfo['data']['client_id'],
                $message['data']['to_kefu_id']
            );

            // 访客的上次服务客服改为新的客服
            $customer = new Customer();
            $customer->updateCustomer([
                'customer_id' => $message['data']['customer_id'],
                'seller_code' => $message['data']['seller_code'],
                'pre_kefu_code' => $message['data']['to_kefu_id']
            ]);

            // 通知新客服接收转接用户
            $keFuClientId = Gateway::getClientIdByUid('KF_' . $message['data']['to_kefu_id']);

            if(!empty($keFuClientId)) {
                Gateway::sendToClient($keFuClientId['0'], json_encode([
                    'cmd' => 'reLink',
                    'data' => [
                        'customer_id' => $message['data']['customer_id'],
                        'customer_name' => $message['data']['customer_name'],
                        'customer_avatar' => $message['data']['customer_avatar'],
                        'customer_ip' => $message['data']['customer_ip'],
                        'seller_code' => $message['data']['seller_code'],
                        'create_time' => date('Y-m-d H:i:s'),
                        'online_status' => 1,
                        'protocol' => 'http',
                        'log_id' => $logId['data']
                    ]
                ]));
            }

            Db::commit();
            // 通知访客，信息被转接
            return $connection->send(json_encode(['code' => 200, 'data' => [
                'cmd' => 'relink',
                'data' => [
                    'kefu_code' => 'KF_' . $message['data']['to_kefu_id'],
                    'kefu_name' => $message['data']['to_kefu_name'],
                    'msg' => '您已被转接'
                ]
            ], 'msg' => 'ok']));
        } catch (\Exception $e) {

            Db::rollback();
        }
    }

    /**
     * 主动关闭用户
     * @param $message
     * @param $connection
     * @return bool
     */
    private static function closeUser($message, $connection)
    {
        $service = new Service();

        $serviceInfo = $service->getServiceInfo(ltrim($message['data']['kefu_code'], 'KF_'), $message['data']['customer_id']);
        $clientId = Gateway::getClientIdByUid($message['data']['kefu_code']);
        if(0 != $serviceInfo['code'] && !empty($clientId)) {

            Gateway::sendToClient($clientId['0'], json_encode([
                'cmd' => 'error',
                'data' => [
                    'msg' => '关闭失败'
                ]
            ]));

            return false;
        }

        if(!empty($serviceInfo['data'])) {

            $log = new ServiceLog();
            $log->updateEndTime($serviceInfo['data']['service_log_id']);

            $service->removeServiceCustomer($serviceInfo['data']['service_id']);
        }

        Gateway::sendToClient($clientId['0'], json_encode([
            'cmd' => 'customerCloseOk',
            'data' => [
                'msg' => '关闭成功'
            ]
        ]));

        return $connection->send(json_encode(['code' => 200, 'data' => [
            'cmd' => 'isClose',
            'data' => [
                'msg' => '客服下班了,稍后再来吧。'
            ]
        ], 'msg' => 'ok']));
    }
}