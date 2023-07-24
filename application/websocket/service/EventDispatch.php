<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Date: 2019/2/21
 * Time: 9:21
 */
namespace app\websocket\service;

use app\model\Analysis;
use app\model\BlackList;
use app\model\Chat;
use app\model\ComQuestion;
use app\model\Customer;
use app\model\KeFu;
use app\model\QuestionConf;
use app\model\Queue;
use app\model\Service;
use app\model\ServiceLog;
use app\model\System;
use GatewayWorker\Lib\Gateway;
use think\Db;

class EventDispatch
{
    /**
     * websocket 连接鉴权
     * @param $data
     * @param $client_id
     */
    public static function auth($data, $client_id)
    {
        // websocket 连接鉴权
        if(!isset($data['server']) || !isset($data['server']['REQUEST_URI'])) {
            Gateway::closeClient($client_id);
        }

        list($sellerCode, $time, $token) = explode('-', ltrim($data['server']['REQUEST_URI'], '/'));
        if(time() - $time > 86400 * 2) {
            Gateway::closeClient($client_id);
        }

        if($token != md5($sellerCode . config('service.salt') . $time)) {
            Gateway::closeClient($client_id);
        }

        // 黑名单过滤
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
        $black = new BlackList();

        $isIn = $black->checkBlackList($ip, $sellerCode);
        if (0 == $isIn['code']) {
            Gateway::closeClient($client_id);
        }
    }

    /**
     * 访客初始化
     * @param $message
     * @param $client_id
     */
    public static function userInit($message, $client_id)
    {
        $customerModel = new Customer();
        $customer = [
            'customer_id' => $message['data']['uid'],
            'customer_name' => $message['data']['name'],
            'customer_avatar' => $message['data']['avatar'],
            'customer_ip' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '',
            'seller_code' => $message['data']['seller'],
            'client_id' => $client_id,
            'create_time' => date('Y-m-d H:i:s'),
            'online_status' => 1,
            'protocol' => 'ws'
        ];

        // 写入累计接入量
        $analysis = new Analysis();
        $analysis->updateTotalData();

        // 绑定 client_id 和 uid
        Gateway::bindUid($client_id, $message['data']['uid']);
        $_SESSION['uid'] = $message['data']['uid'];

        // 尝试分配新访客进入服务
        $dsInfo = Distribution::customerDistribution($customer);
        if(200 == $dsInfo['code']) {

            // 记录服务日志
            $serviceLog = new ServiceLog();
            $logId = $serviceLog->addServiceLog([
                'customer_id' => $customer['customer_id'],
                'client_id' => $client_id,
                'customer_name' => $customer['customer_name'],
                'customer_avatar' => $customer['customer_avatar'],
                'customer_ip' => $customer['customer_ip'],
                'kefu_code' => ltrim($dsInfo['data']['kefu_code'], 'KF_'),
                'seller_code' => $customer['seller_code'],
                'start_time' => date('Y-m-d H:i:s'),
                'protocol' => 'ws'
            ]);

            // 通知客服连接访客
            $client = Gateway::getClientIdByUid($dsInfo['data']['kefu_code']);
            if(!empty($client)){

                $customer['log_id'] = $logId['data'];
                Gateway::sendToClient($client[0], json_encode([
                    'cmd' => 'customerLink',
                    'data' => $customer
                ]));

                unset($customer['log_id']);
            }

            // notice service log 可能会出现写入错误
            $dsInfo['data']['log_id'] = $logId['data'];

            //  通知访客连接客服
            Gateway::sendToClient($client_id, json_encode([
                'cmd' => 'kfLink',
                'data' => $dsInfo['data']
            ]));

            // 获取商户的配置
            $system = new System();
            $sysConfig = $system->getSellerConfig($customer['seller_code']);

            // 对该访客的问候标识
            $preHelloWord = cache($customer['seller_code'] . '-' . $customer['customer_id'] . 'hello_word');
            if(0 == $sysConfig['code'] && !empty($sysConfig['data']) && 1 == $sysConfig['data']['hello_status'] && empty($preHelloWord)) {

                // 聊天信息入库
                /*$chatLog = new Chat();
                $chatLog->addChatLog([
                    'from_id' => $dsInfo['data']['kefu_code'],
                    'from_name' => $dsInfo['data']['kefu_name'],
                    'from_avatar' => $dsInfo['data']['kefu_avatar'],
                    'to_id' => $customer['customer_id'],
                    'to_name' => $customer['customer_name'],
                    'seller_code' => $customer['seller_code'],
                    'content' => $sysConfig['data']['hello_word'],
                    'create_time' => date('Y-m-d H:i:s')
                ]);*/

                // 20分钟 之内不重复发送欢迎语
                cache($customer['seller_code'] . '-' . $customer['customer_id'] . 'hello_word', 1, 1200);
            }

            // 常见问题检测
            $preComQuestion = cache($customer['seller_code'] . '-' . $customer['customer_id'] . 'common_question');
            if (empty($preComQuestion)) {

                $questionCof = new QuestionConf();
                $configInfo = $questionCof->getSellerQuestionConfig($customer['seller_code']);
                if (0 == $configInfo['code'] && !empty($configInfo['data']) && 1 == $configInfo['data']['status']) {

                    // 查询要发送的常见问题
                    $question = new ComQuestion();
                    $comQInfo = $question->getSellerQuestion($customer['seller_code']);

                    $content = '[p]' . $configInfo['data']['question_title'] . '[/p]';
                    if (!empty($comQInfo['data'])) {

                        foreach ($comQInfo['data'] as $vo) {
                            $content .= '[p style=cursor:pointer;color:#1E9FFF; onclick=autoAnswer(this) data-id=' . $vo['question_id'] . ']' .
                                $vo['question'] . '[/p]';
                        }

                        Gateway::sendToClient($client_id, json_encode([
                            'cmd' => 'comQuestion',
                            'data' => [
                                'avatar' => '/static/common/images/robot.jpg',
                                'time' => date('Y-m-d H:i:s'),
                                'content' => $content
                            ]
                        ]));

                        // 120分钟 之内不重复发送常见问题
                        // cache($customer['seller_code'] . '-' . $customer['customer_id'] . 'common_question', 1, 120 * 60);
                    }
                }
            }

            // 记录服务数据
            $service = new Service();
            $service->addServiceCustomer(ltrim($dsInfo['data']['kefu_code'], 'KF_'), $customer['customer_id'],
                $dsInfo['data']['log_id'], $client_id);

            $customer['pre_kefu_code'] = ltrim($dsInfo['data']['kefu_code'], 'KF_');
            // 更新访客表
            $customerModel->updateCustomer($customer);

            // 从队列中移除访客
            $queue = new Queue();
            $queue->removeCustomerFromQueue($customer['customer_id'], $client_id);

        } else if(201 == $dsInfo['code']){

            // 通知访客没有客服在线
            Gateway::sendToClient($client_id, json_encode([
                'cmd' => 'noKefu',
                'data' => [
                    'msg' => '暂无客服在线，请稍后再来'
                ]
            ]));
        } else if(202 == $dsInfo['code']){

            // 通知访客客服全忙
            Gateway::sendToClient($client_id, json_encode([
                'cmd' => 'kefuBusy',
                'data' => [
                    'msg' => '客服全忙，请稍后再来'
                ]
            ]));
        }else {

            Gateway::sendToClient($client_id, json_encode([
                'cmd' => 'error',
                'data' => [
                    'msg' => '系统异常，无法提供服务'
                ]
            ]));
        }

        unset($customerModel, $customer, $analysis, $dsInfo);
    }

    /**
     * 客服初始化
     * @param $message
     * @param $client_id
     */
    public static function keFuInit($message, $client_id)
    {
        // 绑定 client_id 和 uid
        Gateway::bindUid($client_id, $message['data']['uid']);
        $_SESSION['uid'] = $message['data']['uid'];

        // 设置客服在线
        $kefu = new KeFu();
        $kefu->setKeFuStatus(ltrim($message['data']['uid'], 'KF_'));
    }

    /**
     * 聊天事件
     * @param $message
     */
    public static function chatMessage($message)
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
                    'protocol' => 'ws'
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
    }

    /**
     * 处理客户端连接断开
     * @param $clientId
     */
    public static function closeClient($clientId)
    {
        $uid = $_SESSION['uid'];
        if(empty($uid)) {
            return ;
        }

        // 客服刷新不处理
        if(false !== strpos($uid, 'KF_')) {
            return ;
        }

        // 通知该访客的客服，置灰头像
        $service = new Service();
        $keFu = $service->findNowServiceKeFu($uid, $clientId);
        if(0 != $keFu['code'] || empty($keFu['data'])) {
            // 从队列中移除访客
            $queue = new Queue();
            $queue->removeCustomerFromQueue($uid, $clientId);

            $customer = new Customer();
            $customer->updateStatusByClient($uid, $clientId);

            return;
        }

        $kfClientId = Gateway::getClientIdByUid('KF_' . $keFu['data']['kefu_code']);
        if(!empty($kfClientId)) {

            Gateway::sendToClient($kfClientId['0'], json_encode([
                'cmd' => 'offline',
                'data' => [
                    'customer_id' => $uid
                ]
            ]));
        }

        // 更新服务时间
        $serviceLog = new ServiceLog();
        $serviceLog->updateEndTime($keFu['data']['service_log_id']);

        // 更新访客状态
        $customer = new Customer();
        $customer->updateCustomerStatus($uid, $keFu['data']['kefu_code']);

        // 移除正在服务的状态
        $service->removeServiceCustomer($keFu['data']['service_id']);
    }

    /**
     * 记录访客队列
     * @param $message
     * @param $clientId
     */
    public static function customerIn($message, $clientId)
    {
        // 黑名单过滤
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
        $black = new BlackList();

        $isIn = $black->checkBlackList($ip, $message['data']['seller_code']);
        if (0 == $isIn['code']) {
            Gateway::closeClient($clientId);
        }

        // 更新访客队列
        $queue = new Queue();
        $queue->updateCustomer([
            'customer_id' => $message['data']['customer_id'],
            'client_id' => $clientId,
            'customer_name' => $message['data']['customer_name'],
            'customer_avatar' => $message['data']['customer_avatar'],
            'customer_ip' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '',
            'seller_code' => $message['data']['seller_code'],
            'create_time' => date('Y-m-d H:i:s')
        ]);

        // 更新访客信息
        $customer = new Customer();
        $customer->updateCustomer([
            'customer_id' => $message['data']['customer_id'],
            'client_id' => $clientId,
            'customer_name' => $message['data']['customer_name'],
            'customer_avatar' => $message['data']['customer_avatar'],
            'customer_ip' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '',
            'seller_code' => $message['data']['seller_code'],
            'create_time' => date('Y-m-d H:i:s'),
            'online_status' => 1
        ]);

        // 绑定 client_id 和 uid
        Gateway::bindUid($clientId, $message['data']['customer_id']);
        $_SESSION['uid'] = $message['data']['customer_id'];
    }

    /**
     * 转接访客
     * @param $message
     * @param $clientId
     * @return bool
     */
    public static function reLink($message, $clientId)
    {
        Db::startTrans();
        try {

            // 上一次服务的客服设置结束时间，并开启本次服务客服的log
            $service = new Service();
            $serviceInfo = $service->getServiceInfo(ltrim($message['data']['from_kefu_id'], 'KF_'),
                $message['data']['customer_id']);

            if(empty($serviceInfo['data'])) {
                Gateway::sendToClient($clientId, json_encode([
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
                'protocol' => 'ws'
            ]);

            if(0 != $logId['code']) {
                Gateway::sendToClient($clientId, json_encode([
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
                        'protocol' => 'ws',
                        'log_id' => $logId['data']
                    ]
                ]));
            }

            // 通知访客，信息被转接
            Gateway::sendToClient($serviceInfo['data']['client_id'], json_encode([
                'cmd' => 'relink',
                'data' => [
                    'kefu_code' => 'KF_' . $message['data']['to_kefu_id'],
                    'kefu_name' => $message['data']['to_kefu_name'],
                    'msg' => '您已被转接'
                ]
            ]));

            Db::commit();
        } catch (\Exception $e) {

            Db::rollback();
        }
    }

    /**
     * 主动关闭用户
     * @param $message
     * @param $clientId
     * @return bool
     */
    public static function closeUser($message, $clientId)
    {
        $service = new Service();

        $serviceInfo = $service->getServiceInfo(ltrim($message['data']['kefu_code'], 'KF_'), $message['data']['customer_id']);
        if(0 != $serviceInfo['code']) {

            Gateway::sendToClient($clientId, json_encode([
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

            // 通知访客
            Gateway::sendToClient($serviceInfo['data']['client_id'], json_encode([
                'cmd' => 'isClose',
                'data' => [
                    'msg' => '客服下班了,稍后再来吧。'
                ]
            ]));
        }

        Gateway::sendToClient($clientId, json_encode([
            'cmd' => 'customerCloseOk',
            'data' => [
                'msg' => '关闭成功'
            ]
        ]));
    }

    /**
     * 主动接待访客
     * @param $message
     * @param $clientId
     * @param $flag;
     */
    public static function linkCustomer($message, $clientId, $flag)
    {
        $customerClientId = Gateway::getClientIdByUid($message['data']['customer_id']);

        if(empty($customerClientId)) {
            Gateway::sendToClient($clientId, json_encode([
                'cmd' => 'linkError',
                'data' => [
                    'msg' => '接待失败,该访客不在线或者已经被接待'
                ]
            ]));

            return ;
        }

        // 检测该访客是否还在线
        $queue = new Queue();
        $has = $queue->getCustomerInfoByClientId($message['data']['customer_id'], $customerClientId['0']);

        if(empty($has['data'])) {
            Gateway::sendToClient($clientId, json_encode([
                'cmd' => 'linkError',
                'data' => [
                    'msg' => '接待失败,该访客不在线或者已经被接待'
                ]
            ]));

            return ;
        }

        // 记录服务日志
        $serviceLog = new ServiceLog();
        $logId = $serviceLog->addServiceLog([
            'customer_id' => $message['data']['customer_id'],
            'client_id' => $customerClientId['0'],
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

        // 获取商户的配置
        $system = new System();
        $sysConfig = $system->getSellerConfig($message['data']['seller_code']);

        if(0 == $sysConfig['code'] && !empty($sysConfig['data']) && 1 == $sysConfig['data']['hello_status']) {

            // 聊天信息入库
            $chatLog = new Chat();
            $chatLog->addChatLog([
                'from_id' => $message['data']['kefu_code'],
                'from_name' => $message['data']['kefu_name'],
                'from_avatar' => $message['data']['kefu_avatar'],
                'to_id' => $message['data']['customer_id'],
                'to_name' => $message['data']['customer_name'],
                'seller_code' => $message['data']['seller_code'],
                'content' => $sysConfig['data']['hello_word'],
                'create_time' => date('Y-m-d H:i:s')
            ]);
        }

        // 记录服务数据
        $service = new Service();
        $service->addServiceCustomer(ltrim($message['data']['kefu_code'], 'KF_'), $message['data']['customer_id'],
            $logId['data'], $customerClientId['0']);

        // 更新访客表
        $customerModel = new Customer();
        $customerModel->updateCustomer([
            'customer_id' => $message['data']['customer_id'],
            'seller_code' => $message['data']['seller_code'],
            'pre_kefu_code' => ltrim($message['data']['kefu_code'], 'KF_')
        ]);

        // 从队列中移除访客
        $queue->removeCustomerFromQueue($message['data']['customer_id'], $customerClientId['0']);

        // 通知客服
        $cmd = (0 == $flag) ? 'linkAutoOk' : 'linkOk';
        Gateway::sendToClient($clientId, json_encode([
            'cmd' => $cmd,
            'data' => [
                'customer_id' => $message['data']['customer_id'],
                'client_id' => $customerClientId['0'],
                'customer_name' => $message['data']['customer_name'],
                'customer_avatar' => $message['data']['customer_avatar'],
                'customer_ip' => $message['data']['customer_ip'],
                'protocol' => 'ws',
                'create_time' => date('Y-m-d H:i:s'),
                'log_id' => $logId['data']
            ]
        ]));

        // 通知访客
        Gateway::sendToClient($customerClientId['0'], json_encode([
            'cmd' => 'linkByKF',
            'data' => [
                'kefu_code' => $message['data']['kefu_code'],
                'kefu_name' => $message['data']['kefu_name']
            ]
        ]));
    }


    public static function commonQuestion($message, $clientId)
    {
        // 查询这条常见问题的答复
        $question = new ComQuestion();
        $info = $question->getSellerAnswer($message['data']['seller_code'], $message['data']['question_id']);

        if (!empty($info['data'])) {

            Gateway::sendToClient($clientId, json_encode([
                'cmd' => 'chatMessage',
                'data' => [
                    'time' => date('Y-m-d H:i:s'),
                    'avatar' => '/static/common/images/robot.jpg',
                    'content' => $info['data']['answer']
                ]
            ]));
        }
    }
}