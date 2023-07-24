<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Date: 2019/2/20
 * Time: 16:21
 */
namespace app\model;

use think\facade\Log;
use think\Model;

class Group extends Model
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

            $res = $this->where('seller_id', $sellerId)->where('first_service', 1)
                ->where('group_status', 1)->findOrEmpty()->toArray();
        } catch (\Exception $e) {

            Log::error($e->getMessage());
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

            $res = $this->where('group_id', $groupId)
                ->where('group_status', 1)->findOrEmpty()->toArray();
        } catch (\Exception $e) {

            Log::error($e->getMessage());
            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $res, 'msg' => 'ok'];
    }

    /**
     * 获取商户已建立的分组数量
     * @param $sellerId
     * @return array
     */
    public function getSellerGroupNum($sellerId)
    {
        try {

            $res = $this->where('seller_id', $sellerId)->count();
        } catch (\Exception $e) {

            Log::error($e->getMessage());
            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $res, 'msg' => 'ok'];
    }

    /**
     * 删除商户分组
     * @param $sellerId
     * @return array
     */
    public function delGroupBySellerId($sellerId)
    {
        try {

            $this->where('seller_id', $sellerId)->delete();
        } catch (\Exception $e) {

            Log::error($e->getMessage());
            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => '', 'msg' => 'ok'];
    }
}