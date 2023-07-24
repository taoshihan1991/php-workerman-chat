<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2020/2/1
 * Time: 3:55 PM
 */
namespace app\model;

use think\Model;

class AckQueue extends Model
{
    protected $table = 'v2_ack_queue';

    /**
     * 获取所有的检测ack队列
     * @return array|mixed
     */
    public function getAllAckQueue()
    {
        try {

            return $this->select()->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * 写入等待队列
     * @param $param
     */
    public function writeAckQueue($param)
    {
        try {

            $param['send_time'] = time();
            $param['try_times'] = 0;

            $this->insert($param);
        } catch (\Exception $e) {

        }
    }

    /**
     * 移除ack反馈队列的数据
     * @param $qid
     */
    public function removeAckQueueByQid($qid)
    {
        try {

            $this->where('queue_id', $qid)->delete();
        } catch (\Exception $e) {
            echo "---删除ack消息失败---" . PHP_EOL;
        }
    }

    /**
     * 更新ack队列的数据
     * @param $queueId
     * @param $param
     */
    public function updateAckQueue($queueId, $param)
    {
        try {

            $this->where('queue_id', $queueId)->update($param);
        } catch (\Exception $e) {
            echo "---更新ack推送次数失败---" . PHP_EOL;
        }
    }
}