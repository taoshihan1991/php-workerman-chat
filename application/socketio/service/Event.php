<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2019/6/21
 * Time: 9:10 PM
 */
namespace app\socketio\service;

use app\model\Chat;
use app\model\KeFu;
use app\model\BlackList;
use app\model\ComQuestion;
use app\model\Customer;
use app\model\Queue;
use app\model\Seller;
use app\model\Service;
use app\model\ServiceLog;
use app\model\System;
use think\Db;

class Event
{
    /**
     * 客服初始化
     * @param $onlineKeFu
     * @param $data
     * @param $callback
     * @param $socket
     */
    public static function init(&$onlineKeFu, $data, $callback, $socket)
    {
        $data = json_decode($data, true);

        $socket->addedUser = true;

        // 通知先前登陆的客服下线
        if (isset($onlineKeFu[$data['uid']]) && config('service_socketio.single_login')) {
            $onlineKeFu[$data['uid']]->emit('SSO', '', function ($res){});
        }

        $socket->uid = $data['uid'];
        $onlineKeFu[$data['uid']] = $socket;

        if (is_callable($callback)) {

            // 设置客服在线
            $kefu = new KeFu();
            $kefu->setKeFuStatus(ltrim($data['uid'], 'KF_'));

            $callback(json_encode(['code' => 0, 'data' => '', 'msg' => 'login success']));
        }
    }

    /**
     * 访客进入
     * @param $onlineCustomer
     * @param $data
     * @param $callback
     * @param $socket
     */
    public static function customerIn(&$onlineCustomer, $data, $callback, $socket)
    {
        $data = json_decode($data, true);

        if (empty($data['data']['customer_id']) || empty($data['data']['customer_name']
                || empty($data['data']['customer_avatar']))) {

            if (is_callable($callback)) {
                $callbackData = [
                    'code' => 204,
                    'data' => [],
                    'msg' => '您的浏览器版本过低，或者开启了隐身模式'
                ];

                $callback(json_encode($callbackData));
                return false;
            }
            return false;
        }

        // 黑名单过滤
        $ip = $socket->conn->remoteAddress;
        if (!empty($ip)) {
            $ip = explode(":", $ip)[0];
        }
        $black = new BlackList();

        $isIn = $black->checkBlackList($ip, $data['data']['seller_code']);
        if (0 == $isIn['code']) {

            if (is_callable($callback)) {
                $callbackData = [
                    'code' => 201,
                    'data' => [],
                    'msg' => '黑名单用户'
                ];

                $callback(json_encode($callbackData));
            }
        }

        // 检验秘钥合法性
        $time = $data['data']['t'];
        $token = $data['data']['tk'];

        // token 签发日期大于2天了
        if(time() - $time > 86400 * 2) {
            if (is_callable($callback)) {
                $callbackData = [
                    'code' => 202,
                    'data' => [],
                    'msg' => '非法访问'
                ];

                $callback(json_encode($callbackData));
            }
        }

        $safeToken = md5($data['data']['seller_code'] . config('service.salt') . $time);
        if($token != $safeToken) {
            if (is_callable($callback)) {
                $callbackData = [
                    'code' => 202,
                    'data' => [],
                    'msg' => '非法访问'
                ];

                $callback(json_encode($callbackData));
            }
        }

        // 更新访客队列
        $queue = new Queue();
        $queue->updateCustomer([
            'customer_id' => $data['data']['customer_id'],
            'client_id' => $socket->id,
            'customer_name' => $data['data']['customer_name'],
            'customer_avatar' => $data['data']['customer_avatar'],
            'customer_ip' => $ip,
            'seller_code' => $data['data']['seller_code'],
            'create_time' => date('Y-m-d H:i:s')
        ]);

        // 更新访客信息
        $customer = new Customer();
        $location = getLocationByIp($ip, 2);
        $customer->updateCustomer([
            'customer_id' => $data['data']['customer_id'],
            'client_id' => $socket->id,
            'customer_name' => $data['data']['customer_name'],
            'customer_avatar' => $data['data']['customer_avatar'],
            'customer_ip' => $ip,
            'seller_code' => $data['data']['seller_code'],
            'create_time' => date('Y-m-d H:i:s'),
            'online_status' => 1,
            'province' => $location['province'],
            'city' => $location['city']
        ]);

        // 绑定关系
        $socket->addedUser = true;

        $socket->uid = $data['data']['customer_id'];
        $onlineCustomer[$data['data']['customer_id']] = $socket;

        if (is_callable($callback)) {
            $callback(json_encode(['code' => 0, 'data' => '', 'msg' => 'login success']));
        }
    }

    /**
     * 用户初始化
     * @param $onlineCustomer
     * @param $onlineKeFu
     * @param $data
     * @param $callback
     * @param $socket
     * @return bool
     */
    public static function userInit(&$onlineCustomer, &$onlineKeFu, $data, $callback, $socket)
    {
        $customerModel = new Customer();
        $data = json_decode($data, true);

        if (empty($data['uid']) || empty($data['name'] || empty($data['avatar']))) {

            if (is_callable($callback)) {
                $callbackData = [
                    'code' => 204,
                    'data' => [],
                    'msg' => '您的浏览器版本过低，或者开启了隐身模式'
                ];

                $callback(json_encode($callbackData));
                return false;
            }
            return false;
        }

        $ip = $socket->conn->remoteAddress;
        if (!empty($ip)) {
            $ip = explode(":", $ip)[0];
        }

        $blackFlag = Common::checkBlackList($ip, $data, $callback);
        if (!$blackFlag) {
            return false;
        }

        // 固定链接维护用户
        if (isset($data['type']) && 2 == $data['type']) {

            $socket->addedUser = true;

            $socket->uid = $data['uid'];
            $onlineCustomer[$data['uid']] = $socket;
        }

        $location = getLocationByIp($ip, 2);
        $customer = [
            'customer_id' => $data['uid'],
            'customer_name' => $data['name'],
            'customer_avatar' => $data['avatar'],
            'customer_ip' => $ip,
            'seller_code' => $data['seller'],
            'client_id' => $socket->id,
            'create_time' => date('Y-m-d H:i:s'),
            'online_status' => 1,
            'protocol' => 'ws',
            'province' => $location['province'],
            'city' => $location['city']
        ];


        Db::startTrans();
        try {

            // 尝试分配新访客进入服务
            $dsInfo = Distribution::customerDistribution($customer);

            switch ($dsInfo['code']) {

                case 200:

                    // 记录服务日志
                    $serviceLog = new ServiceLog();
                    $logId = $serviceLog->addServiceLog([
                        'customer_id' => $customer['customer_id'],
                        'client_id' => $socket->id,
                        'customer_name' => $customer['customer_name'],
                        'customer_avatar' => $customer['customer_avatar'],
                        'customer_ip' => $customer['customer_ip'],
                        'kefu_code' => ltrim($dsInfo['data']['kefu_code'], 'KF_'),
                        'seller_code' => $customer['seller_code'],
                        'start_time' => date('Y-m-d H:i:s'),
                        'protocol' => 'ws'
                    ]);

                    try {

                        if (!isset($onlineKeFu[$dsInfo['data']['kefu_code']])) {
                            throw new \Exception();
                        }

                        $customer['log_id'] = $logId['data'];
                        // 通知客服链接访客
                        $onlineKeFu[$dsInfo['data']['kefu_code']]->emit('customerLink', $customer, function ($res) {});

                        if (is_callable($callback)) {
                            // 确定客服收到消息, 通知访客连接成功,完成闭环
                            $callbackData = [
                                'code' => 0,
                                'data' => $dsInfo['data'],
                                'msg' => '分配客服成功'
                            ];

                            unset($customer['log_id']);
                            $callback(json_encode($callbackData));
                        }

                    } catch (\Exception $e) {

                        Db::rollback();

                        if (is_callable($callback)) {
                            // 收到客服测接待失败,通知访客端，重新连接
                            $callbackData = [
                                'code' => 400,
                                'data' => [],
                                'msg' => '请重新尝试分配客服'
                            ];

                            $callback(json_encode($callbackData));

                            if (!isset($onlineKeFu[$dsInfo['data']['kefu_code']])) {
                                // 将当前异常客服状态重置
                                (new KeFu())->keFuOffline(ltrim($dsInfo['data']['kefu_code'], 'KF_'));
                            }

                            return false;
                        }
                    }

                    // notice service log 可能会出现写入错误
                    $dsInfo['data']['log_id'] = $logId['data'];

                    // 获取商户的配置
                    $system = new System();
                    $sysConfig = $system->getSellerConfig($customer['seller_code']);

                    // 对该访客的问候标识
                    Common::checkHelloWord($onlineCustomer, $customer, $sysConfig, $callback, $dsInfo);

                    // 常见问题检测
                    Common::checkCommonQuestion($customer, $onlineCustomer);

                    // 记录服务数据
                    $service = new Service();
                    $service->addServiceCustomer(ltrim($dsInfo['data']['kefu_code'], 'KF_'), $customer['customer_id'],
                        $dsInfo['data']['log_id'], $socket->id);

                    $customer['pre_kefu_code'] = ltrim($dsInfo['data']['kefu_code'], 'KF_');
                    // 更新访客表
                    $customerModel->updateCustomer($customer);

                    // 从队列中移除访客
                    $queue = new Queue();
                    $queue->removeCustomerFromQueue($customer['customer_id'], $socket->id);

                    break;

                case 201:

                    // 通知访客没有客服在线
                    if (is_callable($callback)) {
                        $callbackData = [
                            'code' => 201,
                            'data' => [],
                            'msg' => '暂无客服在线，请稍后再来'
                        ];

                        $callback(json_encode($callbackData));
                    }

                    break;

                case 202:

                    // 通知访客客服全忙
                    if (is_callable($callback)) {
                        $callbackData = [
                            'code' => 202,
                            'data' => [],
                            'msg' => '客服全忙，请稍后再来'
                        ];

                        $callback(json_encode($callbackData));
                    }

                    break;

                case 203:

                    // 通知访客客服全忙
                    if (is_callable($callback)) {
                        $callbackData = [
                            'code' => 500,
                            'data' => [],
                            'msg' => '系统异常，无法提供服务'
                        ];

                        $callback(json_encode($callbackData));
                    }

                    break;
            }

            Db::commit();
        } catch (\Exception $e) {

            Db::rollback();

            if (is_callable($callback)) {
                // 收到客服测接待失败,通知访客端，重新连接
                $callbackData = [
                    'code' => 401,
                    'data' => [],
                    'msg' => '请重新尝试分配客服'
                ];

                $callback(json_encode($callbackData));
            }
        }

        unset($customerModel, $customer, $analysis, $dsInfo);
    }

    /**
     * 直接咨询指定客服
     * @param $onlineCustomer
     * @param $onlineKeFu
     * @param $data
     * @param $callback
     * @param $socket
     * @return bool
     */
    public static function directLinkKF(&$onlineCustomer, &$onlineKeFu, $data, $callback, $socket)
    {
        $data = json_decode($data, true);

        $customerModel = new Customer();
        $kefuModel = new KeFu();

        $ip = $socket->conn->remoteAddress;
        if (!empty($ip)) {
            $ip = explode(":", $ip)[0];
        }

        $blackFlag = Common::checkBlackList($ip, $data, $callback);
        if (!$blackFlag) {
            return false;
        }

        $location = getLocationByIp($ip, 2);
        $customer = [
            'customer_id' => $data['uid'],
            'customer_name' => $data['name'],
            'customer_avatar' => $data['avatar'],
            'customer_ip' => $ip,
            'seller_code' => $data['seller'],
            'client_id' => $socket->id,
            'create_time' => date('Y-m-d H:i:s'),
            'online_status' => 1,
            'protocol' => 'ws',
            'province' => $location['province'],
            'city' => $location['city']
        ];

        // 检测客服账号的合法性
        $kefuInfo = $kefuModel->getKeFuInfoByCode($data['kefu_code']);
        if (empty($kefuInfo['data'])) {

            if (is_callable($callback)) {
                $callbackData = [
                    'code' => 201,
                    'data' => [],
                    'msg' => '暂无客服在线，请稍后再来 - 001'
                ];

                $callback(json_encode($callbackData));
            }

            return false;
        }

        // 检测连接客服是否在线
        if (0 == $kefuInfo['data']['online_status']) {
            if (is_callable($callback)) {
                $callbackData = [
                    'code' => 201,
                    'data' => [],
                    'msg' => '暂无客服在线，请稍后再来 - 002'
                ];

                $callback(json_encode($callbackData));
            }

            return false;
        }

        // 检测客服的是否达到了最大服务限制
        $nowServiceModel = new Service();
        $serviceNum = $nowServiceModel->getNowServiceNum($data['kefu_code']);

        if (0 != $serviceNum['code']) {
            if (is_callable($callback)) {
                $callbackData = [
                    'code' => 201,
                    'data' => [],
                    'msg' => '暂无客服在线，请稍后再来 - 003'
                ];

                $callback(json_encode($callbackData));
            }

            return false;
        }

        if ($serviceNum['data'] >= $kefuInfo['data']['max_service_num']) {
            if (is_callable($callback)) {
                $callbackData = [
                    'code' => 202,
                    'data' => [],
                    'msg' => '客服全忙，请稍后再来'
                ];

                $callback(json_encode($callbackData));
            }
            return false;
        }

        // 固定链接维护用户
        if (isset($data['type']) && 2 == $data['type']) {

            $socket->addedUser = true;

            $socket->uid = $data['uid'];
            $onlineCustomer[$data['uid']] = $socket;
        }

        // 记录服务日志
        $serviceLog = new ServiceLog();
        $logId = $serviceLog->addServiceLog([
            'customer_id' => $customer['customer_id'],
            'client_id' => $socket->id,
            'customer_name' => $customer['customer_name'],
            'customer_avatar' => $customer['customer_avatar'],
            'customer_ip' => $customer['customer_ip'],
            'kefu_code' => $data['kefu_code'],
            'seller_code' => $customer['seller_code'],
            'start_time' => date('Y-m-d H:i:s'),
            'protocol' => 'ws'
        ]);

        try {

            if (!isset($onlineKeFu['KF_' . $data['kefu_code']])) {
                throw new \Exception();
            }

            $customer['log_id'] = $logId['data'];
            // 通知客服链接访客
            $onlineKeFu['KF_' . $data['kefu_code']]->emit('customerLink', $customer, function ($res) {});

            if (is_callable($callback)) {
                // 确定客服收到消息, 通知访客连接成功,完成闭环
                $callbackData = [
                    'code' => 0,
                    'data' => [
                        'kefu_avatar' => $kefuInfo['data']['kefu_avatar'],
                        'kefu_code' => 'KF_' . $kefuInfo['data']['kefu_code'],
                        'kefu_name' => $kefuInfo['data']['kefu_name']
                    ],
                    'msg' => '分配客服成功'
                ];

                unset($customer['log_id']);
                $callback(json_encode($callbackData));
            }

        } catch (\Exception $e) {

            Db::rollback();

            if (is_callable($callback)) {
                // 收到客服测接待失败,通知访客端，重新连接
                $callbackData = [
                    'code' => 400,
                    'data' => [],
                    'msg' => '请重新尝试分配客服'
                ];

                $callback(json_encode($callbackData));

                if (!isset($onlineKeFu['KF_' . $kefuInfo['data']['kefu_code']])) {
                    // 将当前异常客服状态重置
                    (new KeFu())->keFuOffline($data['kefu_code']);
                }

                return false;
            }
        }

        // notice service log 可能会出现写入错误
        $dsInfo['data']['log_id'] = $logId['data'];
        $dsInfo['data']['kefu_avatar'] = $kefuInfo['data']['kefu_avatar'];

        // 获取商户的配置
        $system = new System();
        $sysConfig = $system->getSellerConfig($customer['seller_code']);

        // 对该访客的问候标识
        Common::checkHelloWord($onlineCustomer, $customer, $sysConfig, $callback, $dsInfo);

        // 常见问题检测
        Common::checkCommonQuestion($customer, $onlineCustomer);

        // 记录服务数据
        $service = new Service();
        $service->addServiceCustomer($data['kefu_code'], $customer['customer_id'],
            $dsInfo['data']['log_id'], $socket->id);

        $customer['pre_kefu_code'] = $data['kefu_code'];
        // 更新访客表
        $customerModel->updateCustomer($customer);

        // 从队列中移除访客
        $queue = new Queue();
        $queue->removeCustomerFromQueue($customer['customer_id'], $socket->id);
    }

    /**
     * 处理聊天
     * @param $onlineCustomer
     * @param $onlineKeFu
     * @param $data
     * @param $callback
     * @param $socket
     * @return bool
     */
    public static function chatMessage(&$onlineCustomer, &$onlineKeFu, $data, $callback, $socket)
    {
        $data = json_decode($data, true);

        try {

            // 聊天信息入库
            $chatLogId = self::writeChatLog($data);

            $chatMessage = [
                'data' => [
                    'name' => $data['from_name'],
                    'avatar' => $data['from_avatar'],
                    'id' => $data['from_id'],
                    'time' => date('Y-m-d H:i:s'),
                    'content' => htmlspecialchars($data['content']),
                    'protocol' => 'ws',
                    'chat_log_id' => $chatLogId
                ]
            ];

            // 客服发送的消息
            if (strstr($socket->uid, "KF_") !== false) {

                // 检测自己的标识是否存在，不在则可能是断线丢失了
                if (!isset($onlineKeFu[$socket->uid])) {
                    $onlineKeFu[$socket->uid] = $socket;
                }

                // 访客离线
                if (!isset($onlineCustomer[$data['to_id']])) {

                    if (is_callable($callback)) {

                        // 确定客服收到消息, 通知访客连接成功,完成闭环
                        $callbackData = [
                            'code' => 0,
                            'data' => $chatLogId,
                            'msg' => '消息发送成功'
                        ];

                        $callback(json_encode($callbackData));
                    }

                    return false;
                } else {

                    $onlineCustomer[$data['to_id']]->emit('chatMessage', $chatMessage, function ($res) {});
                }

            } else { // 访客发送的消息

                // 检测自己的标识是否存在，不在则可能是断线丢失了
                if (!isset($onlineCustomer[$socket->uid])) {
                    $onlineCustomer[$socket->uid] = $socket;
                }

                $onlineKeFu[$data['to_id']]->emit('chatMessage', $chatMessage, function ($res) {});
            }

            if (is_callable($callback)) {

                // 确定客服收到消息, 通知访客连接成功,完成闭环
                $callbackData = [
                    'code' => 0,
                    'data' => $chatLogId,
                    'msg' => '消息发送成功'
                ];

                $callback(json_encode($callbackData));
            }

        } catch (\Exception $e) {
            if (is_callable($callback)) {
                // 收到客服测接待失败,通知访客端，重新连接
                $callbackData = [
                    'code' => 400,
                    'data' => 0,
                    'msg' => '消息发送失败'
                ];

                $callback(json_encode($callbackData));
            }
        }
    }

    /**
     * 处理已读未读
     * @param $onlineCustomer
     * @param $onlineKeFu
     * @param $data
     * @param $callback
     * @param $socket
     */
    public static function readMessage(&$onlineCustomer, &$onlineKeFu, $data, $callback, $socket)
    {
        $data = json_decode($data, true);

        $chat = new Chat();
        $res = $chat->updateReadStatusBatch($data['mid']);

        if (0 == $res['code']) {

            if (strstr($socket->uid, "KF_") !== false) {
                if (isset($onlineCustomer[$data['uid']])) {

                    $onlineCustomer[$data['uid']]->emit('readMessage', [
                        'mid' => $data['mid']
                    ], function ($res) {});
                }
            } else {
                if (isset($onlineKeFu[$data['uid']])) {

                    $onlineKeFu[$data['uid']]->emit('readMessage', [
                        'mid' => $data['mid']
                    ], function ($res) {});
                }
            }
        }

        if (is_callable($callback)) {
            $callbackData = [
                'code' => 0,
                'data' => 0,
                'msg' => '已读成功'
            ];

            $callback(json_encode($callbackData));
        }
    }

    /**
     * 处理关闭访客
     * @param $onlineCustomer
     * @param $data
     * @param $callback
     * @param $socket
     * @return bool
     */
    public static function closeUser(&$onlineCustomer, $data, $callback)
    {
        $data = json_decode($data, true);

        $service = new Service();
        $serviceInfo = $service->getServiceInfo(ltrim($data['data']['kefu_code'], 'KF_'), $data['data']['customer_id']);
        if(0 != $serviceInfo['code']) {

            if (is_callable($callback)) {
                $callback(json_encode(['code' => 400, 'data' => '', 'msg' => '关闭失败']));
            }

            return false;
        }

        if(!empty($serviceInfo['data'])) {

            $log = new ServiceLog();
            $log->updateEndTime($serviceInfo['data']['service_log_id']);

            $service->removeServiceCustomer($serviceInfo['data']['service_id']);
            try {

                // 通知访客
                $onlineCustomer[$serviceInfo['data']['customer_id']]->emit("isClose", [
                    'data' => [
                        'msg' => '客服下班了,稍后再来吧。'
                    ]
                ], function ($res) {});
            } catch (\Exception $e) {
                if (is_callable($callback)) {
                    $callback(json_encode(['code' => 401, 'data' => '', 'msg' => '关闭失败']));
                }

                return false;
            }
        }

        if (is_callable($callback)) {
            $callback(json_encode(['code' => 0, 'data' => '', 'msg' => '关闭成功']));
        }
    }

    /**
     * 处理常见问题
     * @param $data
     * @param $callback
     * @return bool
     */
    public static function comQuestion($data, $callback)
    {
        $data = json_decode($data, true);
        // 查询这条常见问题的答复
        $question = new ComQuestion();
        $info = $question->getSellerAnswer($data['data']['seller_code'], $data['data']['question_id']);

        if (empty($info['data'])) {

            if (is_callable($callback)) {
                $callback(json_encode(['code' => 400, 'data' => '', 'msg' => '发送失败']));
            }

            return false;
        }

        if (is_callable($callback)) {
            $callback(json_encode(['code' => 0, 'data' => [
                'time' => date('Y-m-d H:i:s'),
                'avatar' => '/static/common/images/robot.jpg',
                'content' => $info['data']['answer'],
                'read_flag' => 2
            ], 'msg' => '发送成功']));
        }
    }

    /**
     * 处理分组转接
     * @param $onlineCustomer
     * @param $onlineKeFu
     * @param $data
     * @param $callback
     * @param $socket
     * @return bool
     */
    public static function changeGroup(&$onlineCustomer, &$onlineKeFu, $data, $callback, $socket)
    {
        $message = json_decode($data, true);

        Db::startTrans();
        try {

            // 上一次服务的客服设置结束时间，并开启本次服务客服的log
            $service = new Service();
            $serviceInfo = $service->getServiceInfo(ltrim($message['data']['from_kefu_id'], 'KF_'),
                $message['data']['customer_id']);

            if(empty($serviceInfo['data'])) {

                if (is_callable($callback)) {
                    $callback(json_encode(['code' => 400, 'data' => [], 'msg' => '转接失败']));
                }

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
                if (is_callable($callback)) {
                    $callback(json_encode(['code' => 400, 'data' => [], 'msg' => '转接失败']));
                }

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
            try {

                if (isset($onlineKeFu['KF_' . $message['data']['to_kefu_id']])) {

                    $onlineKeFu['KF_' . $message['data']['to_kefu_id']]->emit("reLink", [
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
                    ], function ($res) {});
                }
            } catch (\Exception $e) {
                if (is_callable($callback)) {
                    $callback(json_encode(['code' => 400, 'data' => [], 'msg' => '转接失败']));
                }

                Db::rollback();
                return false;
            }

            // 通知访客，信息被转接
            try {

                if (isset($onlineCustomer[$message['data']['customer_id']])) {

                    $onlineCustomer[$message['data']['customer_id']]->emit("relink", [
                        'data' => [
                            'kefu_code' => 'KF_' . $message['data']['to_kefu_id'],
                            'kefu_name' => $message['data']['to_kefu_name'],
                            'msg' => '您已被转接'
                        ]
                    ], function ($res) {});
                }
            } catch (\Exception $e) {
                if (is_callable($callback)) {
                    $callback(json_encode(['code' => 400, 'data' => [], 'msg' => '转接失败']));
                }

                Db::rollback();
                return false;
            }

            if (is_callable($callback)) {
                $callback(json_encode(['code' => 0, 'data' => [], 'msg' => '转接成功']));
            }

            Db::commit();
        } catch (\Exception $e) {

            Db::rollback();
        }
    }

    /**
     * 处理退出断开连接
     * @param $onlineCustomer
     * @param $onlineKeFu
     * @param $socket
     * @return bool
     */
    public static function disconnect(&$onlineCustomer, &$onlineKeFu, $socket)
    {
        if($socket->addedUser) {
            if (strstr($socket->uid, "KF_") !== false) {
                if (isset($onlineKeFu[$socket->uid])) {

                    // 兼容客服多端登陆
                    if ($socket->id != $onlineKeFu[$socket->uid]->id) {
                        return false;
                    }

                    unset($onlineKeFu[$socket->uid]);
                }
            } else {

                unset($onlineCustomer[$socket->uid]);

                // 通知该访客的客服，置灰头像
                $service = new Service();
                $keFu = $service->findNowServiceKeFu($socket->uid, $socket->id);
                if(0 != $keFu['code'] || empty($keFu['data'])) {
                    // 从队列中移除访客
                    $queue = new Queue();
                    $queue->removeCustomerFromQueue($socket->uid, $socket->id);

                    $customer = new Customer();
                    $customer->updateStatusByClient($socket->uid, $socket->id);

                    return false;
                }

                if (isset($onlineKeFu['KF_' . $keFu['data']['kefu_code']])) {

                    try {
                        $onlineKeFu['KF_' . $keFu['data']['kefu_code']]->emit("offline", [
                            'data' => [
                                'customer_id' => $socket->uid
                            ]
                        ], function ($res) {});
                    } catch (\Exception $e) {

                    }
                }

                // 更新服务时间
                $serviceLog = new ServiceLog();
                $serviceLog->updateEndTime($keFu['data']['service_log_id']);

                // 更新访客状态
                $customer = new Customer();
                $customer->updateCustomerStatus($socket->uid, $keFu['data']['kefu_code']);

                // 移除正在服务的状态
                $service->removeServiceCustomer($keFu['data']['service_id']);
            }
        }
    }

    /**
     * 处理主动接待
     * @param $onlineCustomer
     * @param $onlineKeFu
     * @param $data
     * @param $callback
     * @param $socket
     * @return bool
     */
    public static function linkByKF(&$onlineCustomer, &$onlineKeFu, $data, $callback, $socket)
    {
        $message = json_decode($data, true);

        if (!isset($onlineCustomer[$message['data']['customer_id']])) {
            if (is_callable($callback)) {
                $callback(json_encode(['code' => 401, 'data' => '', 'msg' => '接待失败,该访客不在线或者已经被接待']));
            }

            return false;
        }

        if (!isset($onlineKeFu[$message['data']['kefu_code']])) {
            if (is_callable($callback)) {
                $callback(json_encode(['code' => 402, 'data' => '', 'msg' => '客服不在线']));
            }

            return false;
        }

        Db::startTrans();
        try {

            // 检测该访客是否还在线
            $has = db('customer_queue')->where('customer_id', $message['data']['customer_id'])
                ->where('seller_code', $message['data']['seller_code'])->find();

            if(empty($has)) {
                if (is_callable($callback)) {
                    $callback(json_encode(['code' => 403, 'data' => '', 'msg' => '接待失败,该访客不在线或者已经被接待']));
                }
                return false;
            }

            // 记录服务日志
            $serviceLog = new ServiceLog();
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
            $service = new Service();
            $service->addServiceCustomer(ltrim($message['data']['kefu_code'], 'KF_'), $message['data']['customer_id'],
                $logId['data'], $has['client_id']);

            // 更新访客表
            $customerModel = new Customer();
            $customerModel->updateCustomer([
                'customer_id' => $message['data']['customer_id'],
                'seller_code' => $message['data']['seller_code'],
                'pre_kefu_code' => ltrim($message['data']['kefu_code'], 'KF_')
            ]);

            // 从队列中移除访客
            $queue = new Queue();
            $queue->removeCustomerFromQueue($message['data']['customer_id'], $has['client_id']);

            // 通知访客
            try {

                $onlineCustomer[$message['data']['customer_id']]->emit('linkByKF', [
                    'kefu_code' => $message['data']['kefu_code'],
                    'kefu_name' => $message['data']['kefu_name']
                ], function($res) {});

                // 通知客服动态删除访客列表
                foreach ($onlineKeFu as $key => $vo) {
                    if ($key == $message['data']['kefu_code']) {
                        continue;
                    }

                    $vo->emit('removeQueue', [
                        'customer_id' => $message['data']['customer_id']
                    ], function (){});
                }
            } catch (\Exception $e) {

                Db::rollback();

                if (is_callable($callback)) {
                    $callback(json_encode(['code' => 404, 'data' => '', 'msg' => '接待失败']));
                }

                return false;
            }
        } catch (\Exception $e) {

            Db::rollback();

            if (is_callable($callback)) {
                $callback(json_encode(['code' => 405, 'data' => '', 'msg' => '接待失败']));
            }

            return false;
        }

        Db::commit();

        if (is_callable($callback)) {

            $callback(json_encode(['code' => 0, 'data' => [
                'customer_id' => $message['data']['customer_id'],
                'client_id' => $has['client_id'],
                'customer_name' => $message['data']['customer_name'],
                'customer_avatar' => $message['data']['customer_avatar'],
                'customer_ip' => $message['data']['customer_ip'],
                'protocol' => 'ws',
                'create_time' => date('Y-m-d H:i:s'),
                'log_id' => $logId['data']
            ], 'msg' => '接待成功']));
        }
    }

    /**
     * 评价客服
     * @param $onlineCustomer
     * @param $data
     * @param $callback
     */
    public static function praiseKf(&$onlineCustomer, $data, $callback)
    {
        $message = json_decode($data, true);

        if (isset($onlineCustomer[$message['data']['customer_id']])) {

            $onlineCustomer[$message['data']['customer_id']]->emit("praiseKf", [
                'data' => [
                    'service_log_id' => $message['data']['service_log_id']
                ]
            ], function ($res) {});

            if (is_callable($callback)) {
                $callback(json_encode(['code' => 0, 'data' => [], 'msg' => '发送成功']));
            }
        } else {

            if (is_callable($callback)) {
                $callback(json_encode(['code' => -1, 'data' => [], 'msg' => '访客已经离线']));
            }
        }
    }

    /**
     * 访客端正在输入
     * @param $onlineKeFu
     * @param $data
     */
    public static function typing($onlineKeFu, $data)
    {
        $message = json_decode($data, true);
        if (isset($onlineKeFu[$message['to_id']])) {
            $onlineKeFu[$message['to_id']]->emit("typing", [
                'name' => $message['from_name'],
                'avatar' => $message['from_avatar'],
                'id' => $message['from_id'],
                'time' => date('Y-m-d H:i:s'),
                'content' => $message['content']
            ]);
        }
    }

    /**
     * 写聊天日志
     * @param $data
     * @return int|string
     */
    public static function writeChatLog($data)
    {
        $chatLog = new Chat();
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