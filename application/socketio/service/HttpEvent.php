<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2019/10/19
 * Time: 4:20 PM
 */
namespace app\socketio\service;

use app\model\BlackList;
use app\model\Customer;
use app\model\Service;
use app\model\ServiceLog;
use think\Db;

class HttpEvent
{
    /**
     * 访客连接
     * @param $onlineKeFu
     * @param $message
     * @param $connection
     * @return mixed
     */
    public static function link($onlineKeFu, $message, $connection)
    {
        // 黑名单过滤
        $black = new BlackList();
        $isIn = $black->checkBlackList($message['data']['ip'], $message['seller_code']);
        if (0 == $isIn['code']) {
            return $connection->send(json_encode(['code' => 403, 'data' => '', 'msg' => '黑名单用户']));
        }

        $customerModel = new Customer();
        $location = getLocationByIp($message['data']['ip'], 2);
        $customer = [
            'customer_id' => $message['data']['uid'],
            'customer_name' => $message['data']['name'],
            'customer_avatar' => $message['data']['avatar'],
            'customer_ip' => $message['data']['ip'],
            'seller_code' => $message['seller_code'],
            'client_id' => 0,
            'create_time' => date('Y-m-d H:i:s'),
            'online_status' => 1,
            'protocol' => 'http',
            'province' => $location['province'],
            'city' => $location['city']
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

        if (!isset($onlineKeFu[$message['kefu_code']])) {
            return $connection->send(json_encode(['code' => 401, 'data' => '', 'msg' => '该客服不在线']));
        }

        // 通知客服连接访客
        try {

            $customer['log_id'] = $logId['data'];
            $onlineKeFu[$message['kefu_code']]->emit('customerLink', $customer,
                function ($res) {});

            unset($customer['log_id']);
        } catch (\Exception $e) {

            return $connection->send(json_encode(['code' => 400, 'data' => '', 'msg' => '连接客服失败']));
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
     * 访客向客服发送消息
     * @param $onlineKeFu
     * @param $message
     * @param $connection
     * @return mixed
     */
    public static function c2sChat($onlineKeFu, $message, $connection)
    {
        if (!isset($onlineKeFu[$message['data']['to_id']])) {
            return $connection->send(json_encode(['code' => 401, 'data' => '', 'msg' => '该客服不在线']));
        }

        try {

            // 聊天信息入库
            $message['data']['read_flag'] = 2;
            $message['data']['create_time'] = date('Y-m-d H:i:s');
            $chatLogId = Event::writeChatLog($message['data']);

            $onlineKeFu[$message['data']['to_id']]->emit('chatMessage', [
                'data' => [
                    'name' => $message['data']['from_name'],
                    'avatar' => $message['data']['from_avatar'],
                    'id' => $message['data']['from_id'],
                    'time' => date('Y-m-d H:i:s'),
                    'content' => htmlspecialchars($message['data']['content']),
                    'protocol' => 'http',
                    'chat_log_id' => $chatLogId
                ]
            ], function ($res) {});

        } catch (\Exception $e) {
            return $connection->send(json_encode(['code' => 400, 'data' => '', 'msg' => '发送失败']));
        }

        return $connection->send(json_encode(['code' => 200, 'data' => '', 'msg' => 'ok']));
    }

    /**
     * 转移分组
     * @param $onlineKeFu
     * @param $message
     * @param $connection
     * @return mixed
     */
    public static function changeGroup($onlineKeFu, $message, $connection)
    {
        Db::startTrans();
        try {

            // 上一次服务的客服设置结束时间，并开启本次服务客服的log
            $service = new Service();
            $serviceInfo = $service->getServiceInfo(ltrim($message['data']['from_kefu_id'], 'KF_'),
                $message['data']['customer_id']);

            if(empty($serviceInfo['data'])) {
                return $connection->send(json_encode(['code' => 401, 'data' => '', 'msg' => '转接失败']));
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

            if(0 != $logId['code']) {
                Db::rollback();

                return $connection->send(json_encode(['code' => 402, 'data' => '', 'msg' => '转接失败']));
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
            if(isset($onlineKeFu['KF_' . $message['data']['to_kefu_id']])) {

                try {

                    $onlineKeFu['KF_' . $message['data']['to_kefu_id']]
                        ->emit("reLink", [
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
                        ], function ($res) {});
                } catch (\Exception $e) {

                    Db::rollback();
                    return $connection->send(json_encode(['code' => 403, 'data' => '', 'msg' => '转接失败']));
                }
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
     * 关闭访客
     * @param $message
     * @param $connection
     * @return mixed
     */
    public static function closeUser($message, $connection)
    {
        $service = new Service();

        $serviceInfo = $service->getServiceInfo(ltrim($message['data']['kefu_code'], 'KF_'),
            $message['data']['customer_id']);

        if(!empty($serviceInfo['data'])) {

            $log = new ServiceLog();
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