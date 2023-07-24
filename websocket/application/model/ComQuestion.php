<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2020/4/22
 * Time: 10:36 PM
 */
namespace app\model;

class ComQuestion extends BaseModel
{
    protected $table = 'v2_question';

    /**
     * 获取商户的常见问题
     * @param $sellerCode
     * @return array
     */
    public function getSellerQuestion($sellerCode)
    {
        try {

            $info = $this->db->select('*')->from($this->table)->where('seller_code="' . $sellerCode . '"')->query();
        } catch (\Exception $e) {

            return ['code' => -1, 'data' => [], 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $info, 'msg' => ''];
    }

    /**
     * 获取商户的常见问题答复
     * @param $sellerCode
     * @param $questionId
     * @return array
     */
    public function getSellerAnswer($sellerCode, $questionId)
    {
        try {

            $res = $this->db->select('*')->from($this->table)
                ->where('seller_code="' . $sellerCode . '" AND question_id=' . $questionId)
                ->row();
        } catch (\Exception $e) {

            return ['code' => -1, 'data' => [], 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $res, 'msg' => 'success'];
    }
}