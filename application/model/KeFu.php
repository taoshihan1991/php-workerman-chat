<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Date: 2019/2/20
 * Time: 16:36
 */
namespace app\model;

use think\facade\Log;
use think\Model;

class KeFu extends Model
{
    protected $table = 'v2_kefu';

    /**
     * 通过客服code获取客服信息
     * @param $code
     * @return array
     */
    public function getKeFuInfoByCode($code)
    {
        try {

            $kefuInfo = $this->where('kefu_code', $code)->where('kefu_status', 1)->findOrEmpty()->toArray();
        } catch (\Exception $e) {

            Log::error($e->getMessage());
            return ['code' => -1, 'data' => [], 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $kefuInfo, 'msg' => 'ok'];
    }

    /**
     * 查询群组下，在线的客服信息
     * @param $groupId
     * @return array
     */
    public function getOnlineKeFuByGroup($groupId)
    {
        try {

            $kefuInfo = $this->where('group_id', $groupId)->where('kefu_status', 1)
                ->where('online_status', 1)->select()->toArray();
        } catch (\Exception $e) {

            Log::error($e->getMessage());
            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $kefuInfo, 'msg' => 'ok'];
    }

    /**
     * 设置客服在线状态
     * @param $kefuCode
     * @return array
     */
    public function setKeFuStatus($keFuCode)
    {
        try {

            $this->where('kefu_code', $keFuCode)->update([
                'online_status' => 1,
                'last_login_time' => date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {

            Log::error($e->getMessage());
            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => [], 'msg' => 'ok'];
    }

    /**
     * 获取客服信息
     * @param $name
     * @param $seller
     * @return array
     */
    public function getKeFuInfo($name, $seller)
    {
        try {

            $keFuInfo = $this->where('kefu_name', $name)->where('seller_code', $seller)
                ->findOrEmpty()->toArray();
        } catch (\Exception $e) {

            Log::error($e->getMessage());
            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $keFuInfo, 'msg' => 'ok'];
    }

    /**
     * 设置客服离线状态
     * @param $kefuCode
     * @return array
     */
    public function keFuOffline($keFuCode)
    {
        try {

            $this->where('kefu_code', $keFuCode)->update([
                'online_status' => 0
            ]);
        } catch (\Exception $e) {

            Log::error($e->getMessage());
            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => [], 'msg' => 'ok'];
    }

    /**
     * 获取商户客服数量
     * @param $sellerId
     * @return array
     */
    public function getSellerKeFuNum($sellerId)
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
     * 删除商户客服
     * @param $sellerId
     * @return array
     */
    public function delKefuBySellerId($sellerId)
    {
        try {

            $this->where('seller_id', $sellerId)->delete();
        } catch (\Exception $e) {

            Log::error($e->getMessage());
            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => '', 'msg' => 'ok'];
    }

    /**
     * 获取商户下的指定分组客服
     * @param $sellerCode
     * @param $groupId
     * @return array
     */
    public function getSellerKeFuByGroup($sellerCode, $groupId)
    {
        try {

            $res = $this->field('kefu_id,kefu_code,kefu_name,kefu_avatar,group_id,online_status')
                ->where('seller_code', $sellerCode)
                ->where('group_id', $groupId)->select();
        } catch (\Exception $e) {

            Log::error($e->getMessage());
            return ['code' => -1, 'data' => [], 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $res, 'msg' => 'ok'];
    }

    /**
     * 更新客服信息
     * @param $where
     * @param $param
     * @return array
     */
    public function updateKeFuInfo($where, $param)
    {
        try {

            $this->where($where)->update($param);
        } catch (\Exception $e) {

            Log::error($e->getMessage());
            return ['code' => -1, 'data' => [], 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => '', 'msg' => 'ok'];
    }
}