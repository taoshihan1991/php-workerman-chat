<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2020/4/22
 * Time: 10:26 PM
 */
namespace app\model;

class System extends BaseModel
{
    protected $table = 'v2_system';

    /**
     * 获取商家的配置
     * @param $sellerCode
     * @return array
     */
    public function getSellerConfig($sellerCode)
    {
        try {

            $info = $this->db->select('*')->from($this->table)->where('seller_code="' . $sellerCode . '"')->row();
        } catch (\Exception $e) {

            return ['code' => -1, 'data' => [], 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $info, 'msg' => 'ok'];
    }
}