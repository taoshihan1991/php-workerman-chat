<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2020/5/17
 * Time: 9:18 PM
 */
namespace app\service;

use app\model\BlackList;
use app\model\Chat;
use app\model\ComQuestion;
use app\model\Customer;
use app\model\CustomerQueue;
use app\model\KeFu;
use app\model\Queue;
use app\model\Service;
use app\model\ServiceLog;
use app\model\System;
use app\utils\Common;
use app\utils\Distribution;
use app\utils\IPLocation;
use \GatewayWorker\Lib\Gateway;

class SocketEvents
{
    public static function customerIn($clientId, $data, $db)
    {
        if (empty($data['customer_id']) || empty($data['customer_name']
                || empty($data['customer_avatar']))) {

            Gateway::sendToClient($clientId, json_encode([
                'cmd' => 'customerIn',
                'data' => [
                    'code' => 204,
                    'data' => [],
                    'msg' => '您的浏览器版本过低，或者开启了隐身模式'
                ]
            ]));

            return ;
        }

        // 处理ip黑名单问题
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
        $blackListModel = new BlackList($db);
        $isIn = $blackListModel->checkBlackList($ip, $data['seller_code']);
        if (0 == $isIn['code']) {
            // 发送断开连接
            Gateway::sendToClient($clientId, json_encode([
                'cmd' => 'customerIn',
                'data' => [
                    'code' => 201,
                    'data' => [],
                    'msg' => '黑名单用户'
                ]
            ]));
            return;
        }

        // 更新访客队列
        $updateData = [
            'customer_id' => $data['customer_id'],
            'client_id' => $clientId,
            'customer_name' => $data['customer_name'],
            'customer_avatar' => $data['customer_avatar'],
            'customer_ip' => $ip,
            'seller_code' => $data['seller_code'],
            'create_time' => date('Y-m-d H:i:s')
        ];

        $customerQueueModel = new CustomerQueue($db);
        $customerQueueModel->updateQueue($updateData);

        // 更新访客信息
        $customer = new Customer($db);
        $location = IPLocation::getLocationByIp($ip, 2);
        $customer->updateCustomer([
            'customer_id' => $data['customer_id'],
            'client_id' => $clientId,
            'customer_name' => $data['customer_name'],
            'customer_avatar' => $data['customer_avatar'],
            'customer_ip' => $ip,
            'seller_code' => $data['seller_code'],
            'create_time' => date('Y-m-d H:i:s'),
            'online_status' => 1,
            'province' => $location['province'],
            'city' => $location['city']
        ]);

        // 绑定关系
        $_SESSION['id'] = $data['customer_id'];
        Gateway::bindUid($clientId, $data['customer_id']);

        Gateway::sendToClient($clientId, json_encode([
            'cmd' => 'customerIn',
            'data' => [
                'code' => 0,
                'data' => [],
                'msg' => 'login success'
            ]
        ]));
    }

    /**
     * 处理访客接入客服分配
     * @param $sessionId
     * @param $data
     * @param $db
     */
    public static function userInit($sessionId, $data, $db)
    {
        $customerModel = new Customer($db);
        $data = json_decode($data, true);
        $data = $data['data'];

        if (empty($data['uid']) || empty($data['name'] || empty($data['avatar']))) {

            Gateway::sendToClient($sessionId, json_encode([
                'cmd' => 'userInit',
                'data' => [
                    'code' => 204,
                    'data' => [],
                    'msg' => '您的浏览器版本过低，或者开启了隐身模式'
                ]
            ]));

            return ;
        }

        // 处理ip黑名单问题
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
        $blackListModel = new BlackList($db);
        $isIn = $blackListModel->checkBlackList($ip, $data['seller']);
        if (0 == $isIn['code']) {
            // 发送断开连接
            Gateway::sendToClient($sessionId, json_encode([
                'cmd' => 'userInit',
                'data' => [
                    'code' => 201,
                    'data' => [],
                    'msg' => '黑名单用户'
                ]
            ]));
            return;
        }

        // 固定链接维护用户
        if (isset($data['type']) && 2 == $data['type']) {

            $_SESSION['id'] = $data['uid'];
            Gateway::bindUid($sessionId, $data['uid']);
        }

        $location = IPLocation::getLocationByIp($ip, 2);
        $customer = [
            'customer_id' => $data['uid'],
            'customer_name' => $data['name'],
            'customer_avatar' => $data['avatar'],
            'customer_ip' => $ip,
            'seller_code' => $data['seller'],
            'client_id' => $sessionId,
            'create_time' => date('Y-m-d H:i:s'),
            'online_status' => 1,
            'protocol' => 'ws',
            'province' => $location['province'],
            'city' => $location['city']
        ];

        try {

            // 尝试分配新访客进入服务
            $distributionModel = new Distribution($db);
            $dsInfo = $distributionModel->customerDistribution($customer);
            switch ($dsInfo['code']) {

                case 200:

                    // 记录服务日志
                    $serviceLog = new ServiceLog($db);
                    $logId = $serviceLog->addServiceLog([
                        'customer_id' => $customer['customer_id'],
                        'client_id' => $sessionId,
                        'customer_name' => $customer['customer_name'],
                        'customer_avatar' => $customer['customer_avatar'],
                        'customer_ip' => $customer['customer_ip'],
                        'kefu_code' => ltrim($dsInfo['data']['kefu_code'], 'KF_'),
                        'seller_code' => $customer['seller_code'],
                        'start_time' => date('Y-m-d H:i:s'),
						'end_time'=>null,
                        'protocol' => 'ws'
                    ]);
                    try {

                        if (0 == Gateway::isUidOnline($dsInfo['data']['kefu_code'])) {
                            throw new \Exception("444客服不在线");
                        }

                        $customer['log_id'] = $logId['data'];
                        // 通知客服链接访客
                        Gateway::sendToUid($dsInfo['data']['kefu_code'], json_encode([
                            'cmd' => 'customerLink',
                            'data' => $customer
                        ]));

                        // 确定客服收到消息, 通知访客连接成功,完成闭环
                        Gateway::sendToClient($sessionId, json_encode([
                            'cmd' => 'userInit',
                            'data' => [
                                'code' => 0,
                                'data' => $dsInfo['data'],
                                'msg' => '分配客服成功'
                            ]
                        ]));

                        unset($customer['log_id']);

                    } catch (\Exception $e) {
                        // var_dump($e->getMessage());
                        Gateway::sendToClient($sessionId, json_encode([
                            'cmd' => 'userInit',
                            'data' => [
                                'code' => 400,
                                'data' => [],
                                'msg' => '请重新尝试分配客服'
                            ]
                        ]));

                        if (0 == Gateway::isUidOnline($dsInfo['data']['kefu_code'])) {
                            // 将当前异常客服状态重置
                            (new KeFu($db))->keFuOffline(ltrim($dsInfo['data']['kefu_code'], 'KF_'));
                        }

                        return ;
                    }

                    // notice service log 可能会出现写入错误
                    $dsInfo['data']['log_id'] = $logId['data'];

                    // 获取商户的配置
                    $system = new System($db);
                    $sysConfig = $system->getSellerConfig($customer['seller_code']);

                    // 对该访客的问候标识
                    $commonModel = new Common($db);
                    $commonModel->checkHelloWord($customer, $sysConfig, $dsInfo, $sessionId);

                    // 常见问题检测
                    $commonModel->checkCommonQuestion($customer);

                    // 记录服务数据
                    $service = new Service($db);
                    $service->addServiceCustomer(ltrim($dsInfo['data']['kefu_code'], 'KF_'), $customer['customer_id'],
                        $dsInfo['data']['log_id'], $sessionId);

                    $customer['pre_kefu_code'] = ltrim($dsInfo['data']['kefu_code'], 'KF_');
                    // 更新访客表
                    $customerModel->updateCustomer($customer);

                    // 从队列中移除访客
                    $queue = new Queue($db);
                    $queue->removeCustomerFromQueue($customer['customer_id'], $sessionId);

                    break;

                case 201:

                    // 通知访客没有客服在线
                    Gateway::sendToClient($sessionId, json_encode([
                        'cmd' => 'userInit',
                        'data' => [
                            'code' => 201,
                            'data' => [],
                            'msg' => '暂无客服在线，请稍后再来'
                        ]
                    ]));

                    break;

                case 202:

                    // 通知访客客服全忙
                    Gateway::sendToClient($sessionId, json_encode([
                        'cmd' => 'userInit',
                        'data' => [
                            'code' => 202,
                            'data' => [],
                            'msg' => '客服全忙，请稍后再来'
                        ]
                    ]));

                    break;

                case 203:

                    // 通知访客客服全忙
                    Gateway::sendToClient($sessionId, json_encode([
                        'cmd' => 'userInit',
                        'data' => [
                            'code' => 500,
                            'data' => [],
                            'msg' => '系统异常，无法提供服务'
                        ]
                    ]));

                    break;
            }

        } catch (\Exception $e) {

            Gateway::sendToClient($sessionId, json_encode([
                'cmd' => 'userInit',
                'data' => [
                    'code' => 401,
                    'data' => [],
                    'msg' => '请重新尝试分配客服'
                ]
            ]));
        }

        unset($customerModel, $customer, $analysis, $dsInfo);
    }

    /**
     * 客服初始化
     * @param $sessionId
     * @param $data
     * @param $db
     * @param $config
     */
    public static function init($sessionId, $data, $db, $config)
    {
        $data = json_decode($data, true);
        $data = $data['data'];
        // 通知先前登陆的客服下线
        if ($config['single_login'] && 1 == Gateway::isUidOnline($data['uid'])) {
            Gateway::sendToUid($data['uid'], json_encode([
                'cmd' => 'SSO',
                'data' => [
                    'code' => 0,
                    'data' => [],
                    'msg' => '其他地方登录'
                ]
            ]));
        }

        // 绑定关系
        $_SESSION['id'] = $data['uid'];
        Gateway::bindUid($sessionId, $data['uid']);

        // 设置客服在线
        $kefu = new KeFu($db);
        $kefu->setKeFuStatus(ltrim($data['uid'], 'KF_'));

        Gateway::sendToUid($data['uid'], json_encode([
            'cmd' => 'init',
            'data' => [
                'code' => 0,
                'data' => '',
                'msg' => 'login success'
            ]
        ]));
    }

    /**
     * 直接咨询指定客服
     * @param $sessionId
     * @param $data
     * @param $db
     */
    public static function directLinkKF($sessionId, $data, $db)
    {
        $data = json_decode($data, true);
        $data = $data['data'];

        $customerModel = new Customer($db);
        $kefuModel = new KeFu($db);

        // 处理ip黑名单问题
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
        $blackListModel = new BlackList($db);
        $isIn = $blackListModel->checkBlackList($ip, $data['seller']);
        if (0 == $isIn['code']) {
            // 发送断开连接
            Gateway::sendToClient($sessionId, json_encode([
                'cmd' => 'customerIn',
                'data' => [
                    'code' => 201,
                    'data' => [],
                    'msg' => '黑名单用户'
                ]
            ]));
            return;
        }

        $location = IPLocation::getLocationByIp($ip, 2);
        $customer = [
            'customer_id' => $data['uid'],
            'customer_name' => $data['name'],
            'customer_avatar' => $data['avatar'],
            'customer_ip' => $ip,
            'seller_code' => $data['seller'],
            'client_id' => $sessionId,
            'create_time' => date('Y-m-d H:i:s'),
            'online_status' => 1,
            'protocol' => 'ws',
            'province' => $location['province'],
            'city' => $location['city']
        ];

        // 检测客服账号的合法性
        $kefuInfo = $kefuModel->getKeFuInfoByCode($data['kefu_code']);
        if (empty($kefuInfo['data'])) {
            Gateway::sendToClient($sessionId, json_encode([
                'cmd' => 'customerIn',
                'data' => [
                    'code' => 201,
                    'data' => [],
                    'msg' => '暂无客服在线，请稍后再来 - 001'
                ]
            ]));

            return ;
        }

        // 检测连接客服是否在线
        if (0 == $kefuInfo['data']['online_status']) {
            Gateway::sendToClient($sessionId, json_encode([
                'cmd' => 'customerIn',
                'data' => [
                    'code' => 201,
                    'data' => [],
                    'msg' => '暂无客服在线，请稍后再来 - 002'
                ]
            ]));

            return ;
        }

        // 检测客服的是否达到了最大服务限制
        $nowServiceModel = new Service($db);
        $serviceNum = $nowServiceModel->getNowServiceNum($data['kefu_code']);

        if (0 != $serviceNum['code']) {
            Gateway::sendToClient($sessionId, json_encode([
                'cmd' => 'customerIn',
                'data' => [
                    'code' => 201,
                    'data' => [],
                    'msg' => '暂无客服在线，请稍后再来 - 003'
                ]
            ]));

            return ;
        }

        if ($serviceNum['data'] >= $kefuInfo['data']['max_service_num']) {
            Gateway::sendToClient($sessionId, json_encode([
                'cmd' => 'userInit',
                'data' => [
                    'code' => 202,
                    'data' => [],
                    'msg' => '客服全忙，请稍后再来'
                ]
            ]));

            return ;
        }

        // 固定链接维护用户
        if (isset($data['type']) && 2 == $data['type']) {

            $_SESSION['id'] = $data['uid'];
            Gateway::bindUid($sessionId, $data['uid']);
        }

        // 记录服务日志
        $serviceLog = new ServiceLog($db);
        $logId = $serviceLog->addServiceLog([
            'customer_id' => $customer['customer_id'],
            'client_id' => $sessionId,
            'customer_name' => $customer['customer_name'],
            'customer_avatar' => $customer['customer_avatar'],
            'customer_ip' => $customer['customer_ip'],
            'kefu_code' => $data['kefu_code'],
            'seller_code' => $customer['seller_code'],
            'start_time' => date('Y-m-d H:i:s'),
            'protocol' => 'ws'
        ]);

        try {

            if (0 == Gateway::isUidOnline('KF_' . $data['kefu_code'])) {
                throw new \Exception("555客服不在线");
            }

            $customer['log_id'] = $logId['data'];
            // 通知客服链接访客
            Gateway::sendToUid('KF_' . $data['kefu_code'], json_encode([
                'cmd' => 'customerLink',
                'data' => $customer
            ]));

            Gateway::sendToClient($sessionId, json_encode([
                'cmd' => 'userInit',
                'data' => [
                    'code' => 0,
                    'data' => [
                        'kefu_avatar' => $kefuInfo['data']['kefu_avatar'],
                        'kefu_code' => 'KF_' . $kefuInfo['data']['kefu_code'],
                        'kefu_name' => $kefuInfo['data']['kefu_name']
                    ],
                    'msg' => '分配客服成功'
                ]
            ]));
            unset($customer['log_id']);

        } catch (\Exception $e) {

            var_dump($e->getMessage());

            Gateway::sendToClient($sessionId, json_encode([
                'cmd' => 'userInit',
                'data' => [
                    'code' => 400,
                    'data' => [],
                    'msg' => '请重新尝试分配客服'
                ]
            ]));

            if (0 == Gateway::isUidOnline('KF_' . $data['kefu_code'])) {
                // 将当前异常客服状态重置
                (new KeFu($db))->keFuOffline($data['kefu_code']);
            }

            return ;
        }

        // notice service log 可能会出现写入错误
        $dsInfo['data']['log_id'] = $logId['data'];
        $dsInfo['data']['kefu_avatar'] = $kefuInfo['data']['kefu_avatar'];

        // 获取商户的配置
        $system = new System($db);
        $sysConfig = $system->getSellerConfig($customer['seller_code']);

        // 对该访客的问候标识
        $commonModel = new Common($db);
        $commonModel->checkHelloWord($customer, $sysConfig, $dsInfo, $sessionId);

        // 常见问题检测
        $commonModel->checkCommonQuestion($customer);

        // 记录服务数据
        $service = new Service($db);
        $service->addServiceCustomer($data['kefu_code'], $customer['customer_id'],
            $dsInfo['data']['log_id'], $sessionId);

        $customer['pre_kefu_code'] = $data['kefu_code'];
        // 更新访客表
        $customerModel->updateCustomer($customer);

        // 从队列中移除访客
        $queue = new Queue($db);
        $queue->removeCustomerFromQueue($customer['customer_id'], $sessionId);
    }

    /**
     * 处理聊天消息
     * @param $sessionId
     * @param $data
     * @param $db
     */
    public static function chatMessage($sessionId, $data, $db)
    {
        $data = json_decode($data, true);
        $data = $data['data'];

        try {

            // 聊天信息入库
            $chatLogId = self::writeChatLog($data, $db);

            $chatMessage = [
                'name' => $data['from_name'],
                'avatar' => $data['from_avatar'],
                'id' => $data['from_id'],
                'time' => date('Y-m-d H:i:s'),
                'content' => htmlspecialchars($data['content']),
                'protocol' => 'ws',
                'chat_log_id' => $chatLogId
            ];

            // 客服发送的消息
            if (strstr(Gateway::getUidByClientId($sessionId), "KF_") !== false) {

                // 访客离线
                if (0 == Gateway::isUidOnline($data['to_id'])) {
                    Gateway::sendToClient($sessionId, json_encode([
                        'cmd' => 'afterSend',
                        'data' => [
                            'code' => 0,
                            'data' => $chatLogId,
                            'msg' => $data['content']
                        ]
                    ]));

                    return ;
                } else {
                    Gateway::sendToUid($data['to_id'], json_encode([
                        'cmd' => 'chatMessage',
                        'data' => $chatMessage
                    ]));
                }

            } else { // 访客发送的消息

                // 检测自身的标识是否存在，不在，重新绑定
                if (0 == Gateway::isUidOnline($data['from_id'])) {
                    $_SESSION['id'] = $data['from_id'];
                    Gateway::bindUid($data['from_id'], $sessionId);
                }

                if (1 == Gateway::isUidOnline($data['to_id'])) {

                    Gateway::sendToUid($data['to_id'], json_encode([
                        'cmd' => 'chatMessage',
                        'data' => $chatMessage
                    ]));
                }
            }

            // 确定客服收到消息, 通知访客连接成功,完成闭环
            Gateway::sendToClient($sessionId, json_encode([
                'cmd' => 'afterSend',
                'data' => [
                    'code' => 0,
                    'data' => $chatLogId,
                    'msg' => $data['content']
                ]
            ]));

        } catch (\Exception $e) {
            Gateway::sendToClient($sessionId, json_encode([
                'cmd' => 'afterSend',
                'data' => [
                    'code' => 400,
                    'data' => $e->getMessage(),
                    'msg' => '消息发送失败'
                ]
            ]));
        }
    }

    /**
     * 处理已读未读
     * @param $data
     * @param $db
     */
    public static function readMessage($data, $db)
    {
        $data = json_decode($data, true);
        $data = $data['data'];

        $chat = new Chat($db);
        $res = $chat->updateReadStatusBatch($data['mid']);

        if (0 == $res['code'] && 1 == Gateway::isUidOnline($data['uid'])) {
            Gateway::sendToUid($data['uid'], json_encode([
                'cmd' => 'readMessage',
                'data' => [
                    'mid' => $data['mid']
                ]
            ]));
        }
    }

    /**
     * 主动关闭访客
     * @param $data
     * @param $db
     */
    public static function closeUser($data, $db)
    {
        $data = json_decode($data, true);

        $service = new Service($db);
        $serviceInfo = $service->getServiceInfo(ltrim($data['data']['kefu_code'], 'KF_'), $data['data']['customer_id']);

        if(!empty($serviceInfo['data'])) {

            $log = new ServiceLog($db);
            $log->updateEndTime($serviceInfo['data']['service_log_id']);

            $service->removeServiceCustomer($serviceInfo['data']['service_id']);
            // 通知访客
            if (1 == Gateway::isUidOnline($serviceInfo['data']['customer_id'])) {
                Gateway::sendToUid($serviceInfo['data']['customer_id'], json_encode([
                    'cmd' => 'isClose',
                    'data' => [
                        'msg' => '客服下班了,稍后再来吧。'
                    ]
                ]));
            }
        }
    }

    /**
     * 处理常见问题
     * @param $data
     * @param $clientId
     * @param $db
     */
    public static function comQuestion($clientId, $data, $db)
    {
        $data = json_decode($data, true);
        // 查询这条常见问题的答复
        $question = new ComQuestion($db);
        $info = $question->getSellerAnswer($data['data']['seller_code'], $data['data']['question_id']);

        // TODO 常见问题入库
        Gateway::sendToClient($clientId, json_encode([
            'cmd' => 'answerComQuestion',
            'data' => [
                'time' => date('Y-m-d H:i:s'),
                'avatar' => '/static/common/images/robot.jpg',
                'content' => $info['data']['answer'],
                'read_flag' => 2
            ]
        ]));
    }

    /**
     * 处理转接
     * @param $data
     * @param $clientId
     * @param $db
     */
    public static function changeGroup($clientId, $data, $db)
    {
        $message = json_decode($data, true);

        try {

            // 上一次服务的客服设置结束时间，并开启本次服务客服的log
            $service = new Service($db);
            $serviceInfo = $service->getServiceInfo(ltrim($message['data']['from_kefu_id'], 'KF_'),
                $message['data']['customer_id']);

            if(empty($serviceInfo['data'])) {
                Gateway::sendToClient($clientId, json_encode([
                    'cmd' => 'changeGroupCB',
                    'data' => [
                        'code' => 410,
                        'data' => [],
                        'msg' => '转接失败'
                    ]
                ]));

                return ;
            }

            $log = new ServiceLog($db);
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
                'protocol' => 'ws'
            ]);

            if(0 != $logId['code']) {
                Gateway::sendToClient($clientId, json_encode([
                    'cmd' => 'changeGroupCB',
                    'data' => [
                        'code' => 420,
                        'data' => [],
                        'msg' => '转接失败'
                    ]
                ]));

                return ;
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
            $customer = new Customer($db);
            $customer->updateCustomer([
                'customer_id' => $message['data']['customer_id'],
                'seller_code' => $message['data']['seller_code'],
                'pre_kefu_code' => $message['data']['to_kefu_id']
            ]);

            // 通知新客服接收转接用户
            try {

                if (1 == Gateway::isUidOnline('KF_' . $message['data']['to_kefu_id'])) {
                    Gateway::sendToUid('KF_' . $message['data']['to_kefu_id'], json_encode([
                        'cmd' => 'reLink',
                        'data' => [
                            'customer_id' => $message['data']['customer_id'],
                            'customer_name' => $message['data']['customer_name'],
                            'customer_avatar' => $message['data']['customer_avatar'],
                            'customer_ip' => $message['data']['customer_ip'],
                            'seller_code' => $message['data']['seller_code'],
                            'create_time' => date('Y-m-d H:i:s'),
                            'online_status' => 1,
                            'protocol' => 'ws',
                            'log_id' => $logId['data']
                        ]
                    ]));
                }
            } catch (\Exception $e) {
                Gateway::sendToClient($clientId, json_encode([
                    'cmd' => 'changeGroupCB',
                    'data' => [
                        'code' => 430,
                        'data' => [],
                        'msg' => '转接失败'
                    ]
                ]));

                return ;
            }

            // 通知访客，信息被转接
            try {

                if (1 == Gateway::isUidOnline($message['data']['customer_id'])) {
                    Gateway::sendToUid($message['data']['customer_id'], json_encode([
                        'cmd' => 'reLink',
                        'data' => [
                            'kefu_code' => 'KF_' . $message['data']['to_kefu_id'],
                            'kefu_name' => $message['data']['to_kefu_name'],
                            'msg' => '您已被转接'
                        ]
                    ]));
                }
            } catch (\Exception $e) {
                Gateway::sendToClient($clientId, json_encode([
                    'cmd' => 'changeGroupCB',
                    'data' => [
                        'code' => 440,
                        'data' => [],
                        'msg' => '转接失败'
                    ]
                ]));

                return ;
            }

            Gateway::sendToClient($clientId, json_encode([
                'cmd' => 'changeGroupCB',
                'data' => [
                    'code' => 0,
                    'data' => [],
                    'msg' => '转接成功'
                ]
            ]));

        } catch (\Exception $e) {

            Gateway::sendToClient($clientId, json_encode([
                'cmd' => 'changeGroupCB',
                'data' => [
                    'code' => 450,
                    'data' => [],
                    'msg' => '转接失败'
                ]
            ]));

        }
    }

    /**
     * 手动接待访客
     * @param $data
     * @param $clientId
     * @param $db
     */
    public static function linkByKF($clientId, $data, $db)
    {
        $message = json_decode($data, true);
        if (0 == Gateway::isUidOnline($message['data']['customer_id'])) {
            Gateway::sendToClient($clientId, json_encode([
                'cmd' => 'linkKFCB',
                'data' => [
                    'code' => 401,
                    'data' => '',
                    'msg' => '接待失败,该访客不在线或者已经被接待'
                ]
            ]));

            return ;
        }

        if (0 == Gateway::isUidOnline($message['data']['kefu_code'])) {
            Gateway::sendToClient($clientId, json_encode([
                'cmd' => 'linkKFCB',
                'data' => [
                    'code' => 402,
                    'data' => '',
                    'msg' => '您不在线'
                ]
            ]));

            return ;
        }

        try {

            // 检测该访客是否还在线
            $has = $db->select('*')->from('v2_customer_queue')
                ->where('customer_id="' . $message['data']['customer_id'] . '" AND seller_code="' . $message['data']['seller_code'] . '"')
                ->row();
            if(empty($has)) {
                Gateway::sendToClient($clientId, json_encode([
                    'cmd' => 'linkKFCB',
                    'data' => [
                        'code' => 403,
                        'data' => '',
                        'msg' => '接待失败,该访客不在线或者已经被接待'
                    ]
                ]));

                return ;
            }

            // 记录服务日志
            $serviceLog = new ServiceLog($db);
            $logId = $serviceLog->addServiceLog([
                'customer_id' => $message['data']['customer_id'],
                'client_id' => $has['client_id'],
                'customer_name' => $message['data']['customer_name'],
                'customer_avatar' => $message['data']['customer_avatar'],
                'customer_ip' => $message['data']['customer_ip'],
                'kefu_code' => ltrim($message['data']['kefu_code'], 'KF_'),
                'seller_code' => $message['data']['seller_code'],
                'start_time' => date('Y-m-d H:i:s'),
                'protocol' => 'ws'
            ]);

            // 通知客服连接访客
            $message['data']['log_id'] = $logId['data'];

            // 记录服务数据
            $service = new Service($db);
            $service->addServiceCustomer(ltrim($message['data']['kefu_code'], 'KF_'), $message['data']['customer_id'],
                $logId['data'], $has['client_id']);

            // 更新访客表
            $customerModel = new Customer($db);
            $customerModel->updateCustomer([
                'customer_id' => $message['data']['customer_id'],
                'seller_code' => $message['data']['seller_code'],
                'pre_kefu_code' => ltrim($message['data']['kefu_code'], 'KF_')
            ]);

            // 从队列中移除访客
            $queue = new Queue($db);
            $queue->removeCustomerFromQueue($message['data']['customer_id'], $has['client_id']);

            // 通知访客
            try {

                Gateway::sendToUid($message['data']['customer_id'], json_encode([
                    'cmd' => 'linkByKF',
                    'data' => [
                        'kefu_code' => $message['data']['kefu_code'],
                        'kefu_name' => $message['data']['kefu_name']
                    ]
                ]));

                // 通知客服动态删除访客列表
                $onlineKeFu = $db->select('*')->from('v2_kefu')
                    ->where('seller_code="' . $message['data']['seller_code'] . '" AND `online_status`=1')
                    ->query();
                foreach ($onlineKeFu as $key => $vo) {
                    if ($vo['kefu_code'] == $message['data']['kefu_code']) {
                        continue;
                    }
                    Gateway::sendToUid('KF_' . $vo['kefu_code'], json_encode([
                        'cmd' => 'removeQueue',
                        'data' => [
                            'customer_id' => $message['data']['customer_id']
                        ]
                    ]));
                }
            } catch (\Exception $e) {

                Gateway::sendToClient($clientId, json_encode([
                    'cmd' => 'linkKFCB',
                    'data' => [
                        'code' => 404,
                        'data' => '',
                        'msg' => '接待失败'
                    ]
                ]));

                return ;
            }
        } catch (\Exception $e) {

            Gateway::sendToClient($clientId, json_encode([
                'cmd' => 'linkKFCB',
                'data' => [
                    'code' => 405,
                    'data' => $e->getMessage(),
                    'msg' => '接待失败'
                ]
            ]));

            return ;
        }

        Gateway::sendToClient($clientId, json_encode([
            'cmd' => 'linkKFCB',
            'data' => [
                'code' => 0,
                'data' => [
                    'customer_id' => $message['data']['customer_id'],
                    'client_id' => $has['client_id'],
                    'customer_name' => $message['data']['customer_name'],
                    'customer_avatar' => $message['data']['customer_avatar'],
                    'customer_ip' => $message['data']['customer_ip'],
                    'protocol' => 'ws',
                    'create_time' => date('Y-m-d H:i:s'),
                    'log_id' => $logId['data']
                ],
                'msg' => '接待成功'
            ]
        ]));
    }

    /**
     * 评价客服
     * @param $clientId
     * @param $data
     */
    public static function praiseKf($clientId, $data)
    {
        $message = json_decode($data, true);

        if (1 == Gateway::isUidOnline($message['data']['customer_id'])) {
            Gateway::sendToUid($message['data']['customer_id'], json_encode([
                'cmd' => 'praiseKf',
                'data' => [
                    'service_log_id' => $message['data']['service_log_id']
                ]
            ]));

            Gateway::sendToClient($clientId, json_encode([
                'cmd' => 'praiseKfCB',
                'data' => [
                    'code' => 0,
                    'data' => [],
                    'msg' => '发送成功'
                ]
            ]));
        } else {

            Gateway::sendToClient($clientId, json_encode([
                'cmd' => 'praiseKfCB',
                'data' => [
                    'code' => -1,
                    'data' => [],
                    'msg' => '访客已经离线'
                ]
            ]));
        }
    }

    /**
     * 访客正在输入
     * @param $data
     * @param $ws
     */
    public static function typing($data)
    {
        $message = json_decode($data, true);
        if (1 == Gateway::isUidOnline($message['data']['to_id'])) {
            Gateway::sendToUid($message['data']['to_id'], json_encode([
                'cmd' => 'typing',
                'data' => [
                    'name' => $message['data']['from_name'],
                    'avatar' => $message['data']['from_avatar'],
                    'id' => $message['data']['from_id'],
                    'time' => date('Y-m-d H:i:s'),
                    'content' => $message['data']['content']
                ]
            ]));
        }
    }

    /**
     * 客户端退出
     * @param $clientId
     * @param $db
     */
    public static function disConnect($clientId, $db)
    {
        $uid = $_SESSION['id'];
        // 客服刷新不删除客服标识
        if (strstr($uid, "KF_") !== false) {
            return;
        }

        // 通知该访客的客服，置灰头像
        $service = new Service($db);
        $keFu = $service->findNowServiceKeFu($uid, $clientId);
        if(0 != $keFu['code'] || empty($keFu['data'])) {
            // 从队列中移除访客
            $queue = new Queue($db);
            $queue->removeCustomerFromQueue($uid, $clientId);

            $customer = new Customer($db);
            $customer->updateStatusByClient($uid, $clientId);

            return ;
        }

        if (1 == Gateway::isUidOnline('KF_' . $keFu['data']['kefu_code'])) {
            Gateway::sendToUid('KF_' . $keFu['data']['kefu_code'], json_encode([
                'cmd' => 'offline',
                'data' => [
                    'customer_id' => $uid
                ]
            ]));
        }

        // 更新服务时间
        $serviceLog = new ServiceLog($db);
        $serviceLog->updateEndTime($keFu['data']['service_log_id']);

        // 更新访客状态
        $customer = new Customer($db);
        $customer->updateCustomerStatus($uid, $keFu['data']['kefu_code']);

        // 移除正在服务的状态
        $service->removeServiceCustomer($keFu['data']['service_id']);
    }

    /**
     * 消息撤回
     * @param $data
     * @param $db
     */
    public static function rollBackMessage($data, $db)
    {
        $message = json_decode($data, true);

        // TODO 这里消息直接做物理删除，需要软删除的自己扩展
        $chatLog = new Chat($db);
        $chatLog->deleteMsg($message['data']['mid'], $message['data']['kid'], $message['data']['uid']);

        if (1 == Gateway::isUidOnline($message['data']['uid'])) {
            Gateway::sendToUid($message['data']['uid'], json_encode([
                'cmd' => 'rollBackMessage',
                'data' => [
                    'mid' => $message['data']['mid']
                ]
            ]));
        }
    }

    /**
     * 写聊天日志
     * @param $data
     * @param $db
     * @return int|string
     */
    public static function writeChatLog($data, $db)
    {
        $chatLog = new Chat($db);
        return $chatLog->addChatLog([
            'from_id' => $data['from_id'],
            'from_name' => $data['from_name'],
            'from_avatar' => $data['from_avatar'],
            'to_id' => $data['to_id'],
            'to_name' => $data['to_name'],
            'seller_code' => $data['seller_code'],
            'content' => $data['content'],
            'create_time' => date('Y-m-d H:i:s')
        ]);
    }
}