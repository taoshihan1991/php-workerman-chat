<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2020/4/22
 * Time: 9:57 PM
 */
namespace app\model;

class Service extends BaseModel
{
    protected $table = 'v2_now_service';

    /**
     * 获取当前客服服务的用户数
     * @param $kefuCode
     * @return array
     */
    public function getNowServiceNum($kefuCode)
    {
        try {

            $num = $this->db->select('count(1) as t_num')
                ->from($this->table)->where('kefu_code="' . $kefuCode . '"')->row()['t_num'];
        } catch (\Exception $e) {

            return ['code' => -1, 'data' => 0, 'msg' => $e->getMessage()];
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
            $has = $this->db->select('*')->from($this->table)
                ->where('kefu_code="' . $kefuCode . '" AND customer_id="' . $customerId . '"')
                ->row();
            if(!empty($has)) {

                $this->db->update($this->table)
                    ->cols([
                        'kefu_code' => (0 == $toKeFuCode) ? $kefuCode : $toKeFuCode,
                        'client_id' => (0 == $toKeFuCode) ? $clientId : $has['client_id'],
                        'service_log_id' => $logId,
                        'create_time' => time()
                    ])->where('service_id=' . $has['service_id'])->query();

                return ['code' => 0, 'data' => $has['service_id'], 'msg' => 'ok'];
            }

            $serviceId = $this->db->insert($this->table)->cols([
                'kefu_code' => (0 == $toKeFuCode) ? $kefuCode : $toKeFuCode,
                'customer_id' => $customerId,
                'client_id' => $clientId,
                'service_log_id' => $logId,
                'create_time' => time()
            ])->query();
        } catch (\Exception $e) {

            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $serviceId, 'msg' => 'ok'];
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

            $info = $this->db->select('*')->from($this->table)
                ->where('kefu_code="' . $keFuCode . '" AND customer_id="' . $customerId . '"')
                ->row();
        } catch (\Exception $e) {

            return ['code' => -1, 'data' => [], 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $info, 'msg' => 'ok'];
    }

    /**
     * 移除访客服务数据
     * @param $serviceId
     * @return array
     */
    public function removeServiceCustomer($serviceId)
    {
        try {

            $this->db->delete($this->table)->where('service_id=' . $serviceId)->query();
        } catch (\Exception $e) {

            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => 1, 'msg' => 'ok'];
    }

    /**
     * 获取尚未服务完的客服信息
     * @param $customerId
     * @param $clientId
     * @return array
     */
    public function findNowServiceKeFu($customerId, $clientId)
    {
        try {

            $keFu = $this->db->select('*')->from($this->table)
                ->where('customer_id="' . $customerId . '" AND client_id="' . $clientId . '"')
                ->row();
        } catch (\Exception $e) {

            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $keFu, 'msg' => 'ok'];
    }
}