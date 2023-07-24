<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2019/2/20
 * Time: 9:02 PM
 */
namespace app\model;

use think\facade\Log;
use think\Model;

class Service extends Model
{
    protected $table = 'v2_now_service';

    /**
     * 获取尚未服务完的客服信息
     * @param $customerId
     * @param $clientId
     * @return array
     */
    public function findNowServiceKeFu($customerId, $clientId)
    {
        try {

            $keFu = $this->where('customer_id', $customerId)->where('client_id', $clientId)->findOrEmpty()->toArray();
        } catch (\Exception $e) {

            Log::error($e->getMessage());
            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $keFu, 'msg' => 'ok'];
    }

    /**
     * 获取当前客服服务的用户数
     * @param $kefuCode
     * @return array
     */
    public function getNowServiceNum($kefuCode)
    {
        try {

            $num = $this->where('kefu_code', $kefuCode)->count();
        } catch (\Exception $e) {

            Log::error($e->getMessage());
            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $num, 'msg' => 'ok'];
    }

    /**
     * 添加访客服务数据
     * @param $kefuCode
     * @param $customerId
     * @param $logId
     * @param $clientId
     * @param $toKeFuCode
     * @return array
     */
    public function addServiceCustomer($kefuCode, $customerId, $logId, $clientId, $toKeFuCode = 0)
    {
        try {

            // 检测服务数据是否存在
            $has = $this->where('kefu_code', $kefuCode)->where('customer_id', $customerId)->findOrEmpty()->toArray();
            if(!empty($has)) {

                $this->where('service_id', $has['service_id'])->update([
                    'kefu_code' => (0 == $toKeFuCode) ? $kefuCode : $toKeFuCode,
                    'client_id' => (0 == $toKeFuCode) ? $clientId : $has['client_id'],
                    'service_log_id' => $logId,
                    'create_time' => time()
                ]);

                return ['code' => 0, 'data' => $has['service_id'], 'msg' => 'ok'];
            }

            $serviceId = $this->insertGetId([
                'kefu_code' => (0 == $toKeFuCode) ? $kefuCode : $toKeFuCode,
                'customer_id' => $customerId,
                'client_id' => $clientId,
                'service_log_id' => $logId,
                'create_time' => time()
            ]);
        } catch (\Exception $e) {

            Log::error($e->getMessage());
            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $serviceId, 'msg' => 'ok'];
    }

    /**
     * 移除访客服务数据
     * @param $serviceId
     * @return array
     */
    public function removeServiceCustomer($serviceId)
    {
        try {

            $flag = $this->where('service_id', $serviceId)->delete();
        } catch (\Exception $e) {

            Log::error($e->getMessage());
            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $flag, 'msg' => 'ok'];
    }

    /**
     * 根据客服标识获取他在服务的客户列表
     * @param $keFuCode
     * @return array
     */
    public function getServiceList($keFuCode)
    {
        try {

            $list = $this->where('kefu_code', $keFuCode)->select()->toArray();
        } catch (\Exception $e) {

            Log::error($e->getMessage());
            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $list, 'msg' => 'ok'];
    }

    /**
     * 根据 客服信息 和 访客信息 获取服务信息
     * @param $keFuCode
     * @param $customerId
     * @return array
     */
    public function getServiceInfo($keFuCode, $customerId)
    {
        try {

            $info = $this->where('kefu_code', $keFuCode)->where('customer_id', $customerId)->findOrEmpty()->toArray();
        } catch (\Exception $e) {

            Log::error($e->getMessage());
            return ['code' => -1, 'data' => [], 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $info, 'msg' => 'ok'];
    }
}