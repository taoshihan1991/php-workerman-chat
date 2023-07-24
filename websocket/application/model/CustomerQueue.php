<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2020/4/21
 * Time: 9:39 PM
 */
namespace app\model;

class CustomerQueue extends BaseModel
{
    protected $table = 'v2_customer_queue';

    /**
     * 更新访客队列
     * @param $param
     */
    public function updateQueue($param)
    {
        try {

            $has = $this->db->select('qid')->from($this->table)->row();
            if (empty($has)) {

                $this->db->insert($this->table)->cols($param)->query();
            } else {

                $this->db->update($this->table)->cols($param)
                    ->where('customer_id="' . $param['customer_id'] . '" AND seller_code="' . $param['seller_code'] . '"')
                    ->query();
            }
        } catch (\Exception $e) {
            echo $e->getMessage();
        }

    }
}