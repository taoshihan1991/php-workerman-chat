<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2020/4/21
 * Time: 9:39 PM
 */
namespace app\model;

class Customer extends BaseModel
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

            $has = $this->db->select('cid')->from($this->table)
                ->where('customer_id="' . $param['customer_id'] . '" AND seller_code="' . $param['seller_code'] . '"')
                ->row();

            if(!empty($has)) {

                $this->db->update($this->table)->cols($param)
                    ->where('customer_id="' . $param['customer_id'] . '" AND seller_code="' . $param['seller_code'] . '"')
                    ->query();
            }else {

                $this->db->insert($this->table)->cols($param)->query();
            }
        } catch (\Exception $e) {

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

            $info = $this->db->select('*')->from($this->table)
                ->where('customer_id="' . $customerId . '" AND seller_code="' . $sellerCode . '"')
                ->row();
        } catch (\Exception $e) {

            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $info, 'msg' => 'ok'];
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

            $this->db->update($this->table)->cols(['online_status' => 0])
                ->where('customer_id="' . $customerId . '" AND client_id="' . $client . '"')
                ->query();
        } catch (\Exception $e) {

            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => '', 'msg' => 'ok'];
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

            $this->db->update($this->table)->cols(['online_status' => 0])
                ->where('customer_id="' . $customerId . '" AND pre_kefu_code="' . $keFuCode . '"')
                ->query();

        } catch (\Exception $e) {

            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => '', 'msg' => 'ok'];
    }
}