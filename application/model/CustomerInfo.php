<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Date: 19-5-23
 * Time: 下午4:08
 */
namespace app\model;

use think\facade\Log;
use think\Model;

class CustomerInfo extends Model
{
    protected $table = 'v2_customer_info';

    /**
     * 更新访客信息
     * @param $param
     * @return array
     */
    public function updateCustomerInfo($param)
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
     * 获取访客详情信息
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
     * 获取访客标注姓名
     * @param $customerIds
     * @param $sellerCode
     * @return array
     */
    public function getCustomerNameByIds($customerIds, $sellerCode)
    {
        try {

            $info = $this->field('customer_id,real_name')->whereIn('customer_id', $customerIds)
                ->where('seller_code', $sellerCode)->select()->toArray();

        } catch (\Exception $e) {

            Log::error($e->getMessage());
            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $info, 'msg' => 'ok'];
    }
}