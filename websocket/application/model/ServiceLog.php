<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2020/4/22
 * Time: 10:07 PM
 */
namespace app\model;

class ServiceLog extends BaseModel
{
    protected $table = 'v2_customer_service_log';

    /**
     * 添加服务日志
     * @param $param
     * @return array
     */
    public function addServiceLog($param)
    {
        try {

            $logId = $this->db->insert($this->table)->cols($param)->query();
        } catch (\Exception $e) {

            return ['code' => -1, 'data' => 0, 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $logId, 'msg' => 'ok'];
    }

    /**
     * 更新服务结束时间
     * @param $logId
     * @return array
     */
    public function updateEndTime($logId)
    {
        try {

            $sql = 'UPDATE ' . $this->table . ' SET end_time="' . date('Y-m-d H:i:s') . '" WHERE service_log_id = ' . $logId;
            $this->db->query($sql);
        } catch (\Exception $e) {

            return ['code' => -1, 'data' => 0, 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => '', 'msg' => 'ok'];
    }
}