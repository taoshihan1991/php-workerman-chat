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
use app\model\Customer;
use app\model\Service;
use app\model\ServiceLog;
use app\utils\IPLocation;
use GatewayWorker\Lib\Gateway;

class HttpEvents
{
    public static function onMessage($connection, $data, $db)
    {
        $data = $data->post();
        if (empty($data) || !isset($data['cmd'])) {
            return ;
        }

        switch ($data['cmd']) {
            // api访客连接
            case 'link':
                self::link($connection, $data, $db);
                break;

            // api访客聊天
            case 'c2sChat':
                self::c2sChat($connection, $data, $db);
                break;

            // api访客转接
            case 'changeGroup':
                self::httpChangeGroup($connection, $data, $db);
                break;

            // api用户关闭
            case 'closeUser':
                self::httpCloseUser($connection, $data, $db);
                break;
        }
    }

    /**
     * 访客连接
     * @param $connection
     * @param $data
     * @param $db
     * @return mixed
     */
    public static function link($connection, $data, $db)
    {
        // 黑名单过滤
        $black = new BlackList($db);
        $isIn = $black->checkBlackList($data['data']['ip'], $data['seller_code']);
        if (0 == $isIn['code']) {
            return $connection->send(json_encode(['code' => 403, 'data' => '', 'msg' => '黑名单用户']));
        }

        $customerModel = new Customer($db);
        $location = IPLocation::getLocationByIp($data['data']['ip'], 2);
        $customer = [
            'customer_id' => $data['data']['uid'],
            'customer_name' => $data['data']['name'],
            'customer_avatar' => $data['data']['avatar'],
            'customer_ip' => $data['data']['ip'],
            'seller_code' => $data['seller_code'],
            'client_id' => 0,
            'create_time' => date('Y-m-d H:i:s'),
            'online_status' => 1,
            'protocol' => 'http',
            'province' => $location['province'],
            'city' => $location['city']
        ];

        // 记录服务日志
        $serviceLog = new ServiceLog($db);
        $logId = $serviceLog->addServiceLog([
            'customer_id' => $customer['customer_id'],
            'client_id' => 0,
            'customer_name' => $customer['customer_name'],
            'customer_avatar' => $customer['customer_avatar'],
            'customer_ip' => $customer['customer_ip'],
            'kefu_code' => ltrim($data['kefu_code'], 'KF_'),
            'seller_code' => $customer['seller_code'],
            'start_time' => date('Y-m-d H:i:s'),
            'protocol' => 'http'
        ]);

        if (0 == Gateway::isUidOnline($data['kefu_code'])) {
            return $connection->send(json_encode(['code' => 401, 'data' => '', 'msg' => '该客服不在线']));
        }

        // 通知客服连接访客
        try {

            $customer['log_id'] = $logId['data'];
            Gateway::sendToUid($data['kefu_code'], json_encode([
                'cmd' => 'customerLink',
                'data' => $customer
            ]));

            unset($customer['log_id']);
        } catch (\Exception $e) {

            return $connection->send(json_encode(['code' => 400, 'data' => '', 'msg' => '连接客服失败']));
        }

        // 记录服务数据
        $service = new Service($db);
        $service->addServiceCustomer(ltrim($data['kefu_code'], 'KF_'), $customer['customer_id'],
            $logId['data'], 0);

        $customer['pre_kefu_code'] = ltrim($data['kefu_code'], 'KF_');
        // 更新访客表
        $customerModel->updateCustomer($customer);

        return $connection->send(json_encode(['code' => 200, 'data' => '', 'msg' => 'ok']));
    }

    /**
     * 访客发送消息给客服
     * @param $connection
     * @param $data
     * @param $db
     * @return mixed
     */
    public static function c2sChat($connection, $data, $db)
    {
        if (0 == Gateway::isUidOnline($data['data']['to_id'])) {
            return $connection->send(json_encode(['code' => 401, 'data' => '', 'msg' => '该客服不在线']));
        }

        try {

            // 聊天信息入库
            $data['data']['read_flag'] = 2;
            $data['data']['create_time'] = date('Y-m-d H:i:s');
            $chatLogId = SocketEvents::writeChatLog($data['data'], $db);

            Gateway::sendToUid($data['data']['to_id'], json_encode([
                'cmd' => 'chatMessage',
                'data' => [
                    'name' => $data['data']['from_name'],
                    'avatar' => $data['data']['from_avatar'],
                    'id' => $data['data']['from_id'],
                    'time' => date('Y-m-d H:i:s'),
                    'content' => htmlspecialchars($data['data']['content']),
                    'protocol' => 'http',
                    'chat_log_id' => $chatLogId
                ]
            ]));

        } catch (\Exception $e) {
            return $connection->send(json_encode(['code' => 400, 'data' => '', 'msg' => '发送失败']));
        }

        return $connection->send(json_encode(['code' => 200, 'data' => '', 'msg' => 'ok']));
    }

    /**
     * 转接接口
     * @param $connection
     * @param $data
     * @param $db
     * @return mixed
     */
    public static function httpChangeGroup($connection, $data, $db)
    {
        $db->beginTrans();
        try {

            // 上一次服务的客服设置结束时间，并开启本次服务客服的log
            $service = new Service($db);
            $serviceInfo = $service->getServiceInfo(ltrim($data['data']['from_kefu_id'], 'KF_'),
                $data['data']['customer_id']);

            if(empty($serviceInfo['data'])) {
                return $connection->send(json_encode(['code' => 401, 'data' => '', 'msg' => '转接失败']));
            }

            $log = new ServiceLog($db);
            $log->updateEndTime($serviceInfo['data']['service_log_id']);

            $logId = $log->addServiceLog([
                'customer_id' => $data['data']['customer_id'],
                'client_id' => $serviceInfo['data']['client_id'],
                'customer_name' => $data['data']['customer_name'],
                'customer_avatar' => $data['data']['customer_avatar'],
                'customer_ip' => $data['data']['customer_ip'],
                'kefu_code' => $data['data']['to_kefu_id'],
                'seller_code' => $data['data']['seller_code'],
                'start_time' => date('Y-m-d H:i:s'),
                'protocol' => 'http'
            ]);

            if(0 != $logId['code']) {
                $db->rollBackTrans();
                return $connection->send(json_encode(['code' => 402, 'data' => '', 'msg' => '转接失败']));
            }

            // 更新当前服务的客服id 为转接的客服id 和 新的 log id
            $service->addServiceCustomer(
                ltrim($data['data']['from_kefu_id'], 'KF_'),
                $data['data']['customer_id'],
                $logId['data'],
                $serviceInfo['data']['client_id'],
                $data['data']['to_kefu_id']
            );

            // 访客的上次服务客服改为新的客服
            $customer = new Customer($db);
            $customer->updateCustomer([
                'customer_id' => $data['data']['customer_id'],
                'seller_code' => $data['data']['seller_code'],
                'pre_kefu_code' => $data['data']['to_kefu_id']
            ]);

            // 通知新客服接收转接用户
            if(1 == Gateway::isUidOnline('KF_' . $data['data']['to_kefu_id'])) {

                try {

                    Gateway::sendToUid('KF_' . $data['data']['to_kefu_id'], json_encode([
                        'cmd' => 'reLink',
                        'data' => [
                            'customer_id' => $data['data']['customer_id'],
                            'customer_name' => $data['data']['customer_name'],
                            'customer_avatar' => $data['data']['customer_avatar'],
                            'customer_ip' => $data['data']['customer_ip'],
                            'seller_code' => $data['data']['seller_code'],
                            'create_time' => date('Y-m-d H:i:s'),
                            'online_status' => 1,
                            'protocol' => 'http',
                            'log_id' => $logId['data']
                        ]
                    ]));
                } catch (\Exception $e) {
                    $db->rollBackTrans();
                    return $connection->send(json_encode(['code' => 403, 'data' => '', 'msg' => '转接失败']));
                }
            }

            $db->commitTrans();
            // 通知访客，信息被转接
            return $connection->send(json_encode(['code' => 200, 'data' => [
                'cmd' => 'relink',
                'data' => [
                    'kefu_code' => 'KF_' . $data['data']['to_kefu_id'],
                    'kefu_name' => $data['data']['to_kefu_name'],
                    'msg' => '您已被转接'
                ]
            ], 'msg' => 'ok']));
        } catch (\Exception $e) {

            $db->rollBackTrans();
            return $connection->send(json_encode(['code' => 500, 'data' => $e->getMessage(), 'msg' => '转接失败']));
        }
    }

    /**
     * 主动关闭访客
     * @param $connection
     * @param $data
     * @param $db
     * @return mixed
     */
    public static function httpCloseUser($connection, $data, $db)
    {
        $service = new Service($db);

        $serviceInfo = $service->getServiceInfo(ltrim($data['data']['kefu_code'], 'KF_'),
            $data['data']['customer_id']);

        if(!empty($serviceInfo['data'])) {

            $log = new ServiceLog($db);
            $log->updateEndTime($serviceInfo['data']['service_log_id']);

            $service->removeServiceCustomer($serviceInfo['data']['service_id']);
        }

        return $connection->send(json_encode(['code' => 200, 'data' => [
            'cmd' => 'isClose',
            'data' => [
                'msg' => '客服下班了,稍后再来吧。'
            ]
        ], 'msg' => 'ok']));
    }
}