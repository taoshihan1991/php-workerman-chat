<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2019/9/29
 * Time: 10:39 PM
 */
namespace app\model;

use think\facade\Log;
use think\Model;
use think\facade\Request;

class OperateLog extends Model
{
    protected $table = 'v2_operate_log';

    /**
     * 写操作日志
     * @param $param
     */
    public function writeOperateLog($param)
    {
        try {

            $this->insert($param);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }

    /**
     * 操作日志明细
     * @param $limit
     * @return array
     */
    public function operateLogList($limit)
    {
        try {

            $log = $this->order('log_id', 'desc')->paginate($limit);
        } catch (\Exception $e) {

            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $log, 'msg' => 'ok'];
    }
}