<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2020/4/22
 * Time: 10:34 PM
 */
namespace app\model;

class QuestionConf extends BaseModel
{
    protected $table = 'v2_question_conf';

    /**
     * 获取商户的问题配置
     * @param $sellerCode
     * @return array
     */
    public function getSellerQuestionConfig($sellerCode)
    {
        try {

            $info = $this->db->select('*')->from($this->table)
                ->where('seller_code="' . $sellerCode . '"')
                ->row();
        } catch (\Exception $e) {

            return ['code' => -1, 'data' => [], 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $info, 'msg' => ''];
    }
}