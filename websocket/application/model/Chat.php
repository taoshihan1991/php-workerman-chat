<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2020/4/22
 * Time: 11:05 PM
 */
namespace app\model;

class Chat extends BaseModel
{
    protected $table = 'v2_chat_log';

    /**
     * 记录聊天日志
     * @param $param
     * @return int|string
     */
    public function addChatLog($param)
    {
        try {

            return $this->db->insert($this->table)->cols($param)->query();
        } catch (\Exception $e) {

        }

        return 0;
    }

    /**
     * 批量更新阅读状态
     * @param $ids
     * @return array
     */
    public function updateReadStatusBatch($ids)
    {
        try {

            $sql = 'UPDATE `' . $this->table . '` SET `read_flag`=2 WHERE `log_id` in(' . $ids . ');';
            // echo $sql . PHP_EOL;
            $this->db->query($sql);

            return ['code' => 0, 'data' => [], 'msg' => "成功"];
        }  catch (\Exception $e) {

            return ['code' => -1, 'data' => [], 'msg' => $e->getMessage()];
        }
    }

    /**
     * 删除消息
     * @param $mid
     * @param $kid
     * @param $uid
     * @return array
     */
    public function deleteMsg($mid, $kid, $uid)
    {
        try {

            $sql = 'UPDATE `' . $this->table . '` set `valid`=0 WHERE log_id=' . $mid . ' and from_id="'. $kid .'" and to_id="' . $uid . '"';
            $this->db->query($sql);

            return ['code' => 0, 'data' => [], 'msg' => "成功"];
        }  catch (\Exception $e) {

            return ['code' => -1, 'data' => [], 'msg' => $e->getMessage()];
        }
    }
}