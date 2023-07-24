<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2020/4/22
 * Time: 9:56 PM
 */
namespace app\model;

class Group extends BaseModel
{
    protected $table = 'v2_group';

    /**
     * 获取前置服务组信息
     * @param $sellerId
     * @return array
     */
    public function getFirstServiceGroup($sellerId)
    {
        try {

            $res = $this->db->select('*')->from($this->table)
                ->where('seller_id=' . $sellerId . ' AND first_service=1 AND group_status=1')
                ->row();
        } catch (\Exception $e) {

            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $res, 'msg' => 'ok'];
    }

    /**
     * 获取分组信息
     * @param $groupId
     * @return array
     */
    public function getGroupInfoById($groupId)
    {
        try {

            $res = $this->db->select('*')->from($this->table)
                ->where('group_id=' . $groupId . ' AND group_status=1')
                ->row();
        } catch (\Exception $e) {

            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $res, 'msg' => 'ok'];
    }

    /**
     * 查询群组下，在线的客服信息
     * @param $groupId
     * @return array
     */
    public function getOnlineKeFuByGroup($groupId)
    {
        try {

            $kefuInfo = $this->db->select('*')->from($this->table)
                ->where('group_id=' . $groupId . ' AND kefu_status=1 AND online_status=1')
                ->query();
        } catch (\Exception $e) {

            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $kefuInfo, 'msg' => 'ok'];
    }
}