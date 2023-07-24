<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2020/4/22
 * Time: 9:34 PM
 */
namespace app\model;

class Seller extends BaseModel
{
    protected $table = 'v2_seller';

    /**
     * 根据商户的标识获取商户信息
     * @param $sellerCode
     * @return array
     */
    public function getSellerInfo($sellerCode)
    {
        try {

            $res = $this->db->select('*')->from($this->table)->where('seller_code="' . $sellerCode . '"')->row();
        } catch (\Exception $e) {

            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $res, 'msg' => 'ok'];
    }
}