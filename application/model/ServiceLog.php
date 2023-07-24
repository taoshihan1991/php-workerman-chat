<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Date: 2019/2/26
 * Time: 9:56
 */
namespace app\model;

use think\facade\Log;
use think\Model;

class ServiceLog extends Model
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

            $logId = $this->insertGetId($param);
        } catch (\Exception $e) {

            Log::error($e->getMessage());
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

            $this->where('service_log_id', $logId)->update(['end_time' => date('Y-m-d H:i:s')]);
        } catch (\Exception $e) {

            Log::error($e->getMessage());
            return ['code' => -1, 'data' => 0, 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => '', 'msg' => 'ok'];
    }
}