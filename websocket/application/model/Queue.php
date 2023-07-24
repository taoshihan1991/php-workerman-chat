<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2020/4/22
 * Time: 10:41 PM
 */
namespace app\model;

class Queue extends BaseModel
{
    protected $table = 'v2_customer_queue';

    /**
     * 移除队列中的访客
     * @param $customerId
     * @param $clientId
     * @return array
     */
    public function removeCustomerFromQueue($customerId, $clientId)
    {
        try {

            $this->db->delete($this->table)->where('customer_id="' . $customerId . '" AND client_id="' . $clientId . '"')->query();
        } catch (\Exception $e) {

            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => [], 'msg' => 'ok'];
    }
}