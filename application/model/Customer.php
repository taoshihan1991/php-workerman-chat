<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2019/2/17
 * Time: 11:29 AM
 */
namespace app\model;

use think\facade\Log;
use think\Model;

class Customer extends Model
{
    protected $table = 'v2_customer';

    /**
     * 更新访客信息
     * @param $param
     * @return array
     */
    public function updateCustomer($param)
    {
        try {

            $has = $this->where('customer_id', $param['customer_id'])
                ->where('seller_code', $param['seller_code'])->findOrEmpty()->toArray();

            if(!empty($has)) {

                $this->where('customer_id', $param['customer_id'])
                    ->where('seller_code', $param['seller_code'])->update($param);
            }else {

                $this->insert($param);
            }
        } catch (\Exception $e) {

            Log::error($e->getMessage());
            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => '', 'msg' => 'ok'];
    }

    /**
     * 获取访客信息
     * @param $customerId
     * @param $sellerCode
     * @return array
     */
    public function getCustomerInfoById($customerId, $sellerCode)
    {
        try {

            $info = $this->where('customer_id', $customerId)
                ->where('seller_code', $sellerCode)->findOrEmpty()->toArray();

        } catch (\Exception $e) {

            Log::error($e->getMessage());
            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $info, 'msg' => 'ok'];
    }

    /**
     * 获取指定客服服务的访客列表
     * @param $customerIds
     * @param $keFuCode
     * @return array
     */
    public function getCustomerListByIds($customerIds, $keFuCode)
    {
        try {

            $info = $this->whereIn('customer_id', $customerIds)->where('pre_kefu_code', $keFuCode)
                ->select()->toArray();
        } catch (\Exception $e) {

            Log::error($e->getMessage());
            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $info, 'msg' => 'ok'];
    }

    /**
     * 更新访客离线状态
     * @param $customerId
     * @param $keFuCode
     * @return array
     */
    public function updateCustomerStatus($customerId, $keFuCode)
    {
        try {

            $this->where('customer_id', $customerId)->where('pre_kefu_code', $keFuCode)->setField('online_status', 0);
        } catch (\Exception $e) {

            Log::error($e->getMessage());
            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => '', 'msg' => 'ok'];
    }

    /**
     * 更新访客离线状态
     * @param $customerId
     * @param $client
     * @return array
     */
    public function updateStatusByClient($customerId, $client)
    {
        try {

            $this->where('customer_id', $customerId)->where('client_id', $client)->setField('online_status', 0);
        } catch (\Exception $e) {

            Log::error($e->getMessage());
            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => '', 'msg' => 'ok'];
    }

    /**
     * 获取过去1天到现在的历史访客信息
     * @param $keFuCode
     * @param $sellerCode
     * @return array|\PDOStatement|string|\think\Collection
     */
    public function getHistoryChatList($keFuCode, $sellerCode)
    {
        try {

            $list = $this->where('create_time', '>', date("Y-m-d", strtotime("-1day")))
                ->where('seller_code', $sellerCode)->where('online_status', 0)->where('pre_kefu_code', $keFuCode)
                ->select();
        } catch (\Exception $e) {

            return ['code' => -1, 'data' => [], 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $list, 'msg' => 'ok'];
    }
}