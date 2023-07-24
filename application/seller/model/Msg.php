<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2019/3/17
 * Time: 3:49 PM
 */
namespace app\seller\model;

use think\Model;

class Msg extends Model
{
    protected $table = 'v2_leave_msg';
    /**
     * 获取离线留言列表
     * @param $limit
     * @return array
     */
    public function getLeaveMsgList($limit)
    {
        try {

            $res = $this->where('seller_code', session('seller_code'))
                ->order('id', 'desc')->paginate($limit)->each(function ($item, $key) {
                $item->add_time = date('Y-m-d H:i:s', $item->add_time);
            });
        }catch (\Exception $e) {

            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $res, 'msg' => 'ok'];
    }

    /**
     * 获取未读消息数量
     * @return array
     */
    public function getNoReadMsgCount()
    {
        try {

            $res = $this->where('seller_code', session('seller_code'))
                ->where('status', 1)->count();
        }catch (\Exception $e) {

            return ['code' => -1, 'data' => 0, 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $res, 'msg' => 'ok'];
    }

    /**
     * 处理消息状态
     * @param $id
     * @param int $status
     * @return array
     */
    public function updateMsgStatus($id, $status = 2)
    {
        try {

            $this->where('seller_code', session('seller_code'))
                ->where('id', $id)->update([
                    'status' => $status,
                    'update_time' => date('Y-m-d H:i:s')
                ]);
        }catch (\Exception $e) {

            return ['code' => -1, 'data' => [], 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => [], 'msg' => '处理成功'];
    }

    /**
     * 批量标记已读
     * @return array
     */
    public function updateMsgStatusBatch()
    {
        try {

            $this->where('seller_code', session('seller_code'))
                ->where('status', 1)->update([
                    'status' => 2,
                    'update_time' => date('Y-m-d H:i:s')
                ]);
        }catch (\Exception $e) {

            return ['code' => -1, 'data' => [], 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => [], 'msg' => '处理成功'];
    }
}