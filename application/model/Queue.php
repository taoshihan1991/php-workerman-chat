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

class Queue extends Model
{
    protected $table = 'v2_customer_queue';

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
            return ['code' => -1, 'data' => [], 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $info, 'msg' => 'ok'];
    }

    /**
     * 获取访客信息
     * @param $customerId
     * @param $clientId
     * @return array
     */
    public function getCustomerInfoByClientId($customerId, $clientId)
    {
        try {

            $info = $this->where('customer_id', $customerId)
                ->where('client_id', $clientId)->findOrEmpty()->toArray();

        } catch (\Exception $e) {

            Log::error($e->getMessage());
            return ['code' => -1, 'data' => [], 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $info, 'msg' => 'ok'];
    }

    /**
     * 移除队列中的访客
     * @param $customerId
     * @param $clientId
     * @return array
     */
    public function removeCustomerFromQueue($customerId, $clientId)
    {
        try {

            $info = $this->where('customer_id', $customerId)->where('client_id', $clientId)->delete();
        } catch (\Exception $e) {

            Log::error($e->getMessage());
            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $info, 'msg' => 'ok'];
    }

    /**
     * 获取在线未咨询访客列表
     * @param $customerId
     * @param $sellerCode
     * @return array
     */
    public function getCustomerList($sellerCode)
    {
        try {

            $info = $this->where('seller_code', $sellerCode)->select()->toArray();
        } catch (\Exception $e) {

            Log::error($e->getMessage());
            return ['code' => -1, 'data' => [], 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $info, 'msg' => 'ok'];
    }

    /**
     * 在线访客
     * @param $sellerCode
     * @return array
     */
    public function getOnlineCustomer($sellerCode)
    {
        try {

            $total = $this->where('seller_code', $sellerCode)->count();
        } catch (\Exception $e) {

            Log::error($e->getMessage());
            return ['code' => -1, 'data' => 0, 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $total, 'msg' => 'ok'];
    }
}