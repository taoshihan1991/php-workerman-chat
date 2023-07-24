<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2019/6/2
 * Time: 1:40 PM
 */
namespace app\model;

use think\Model;

class QuestionConf extends Model
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

            $info = $this->where('seller_code', $sellerCode)->findOrEmpty()->toArray();
        } catch (\Exception $e) {

            return ['code' => -1, 'data' => [], 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $info, 'msg' => ''];
    }

    /**
     * 编辑商户的常见问题设置
     * @param $param
     * @return array
     */
    public function editSellerQuestionConfig($param)
    {
        try {

            $has = $this->where('seller_code', session('seller_code'))->findOrEmpty()->toArray();
            if (empty($has)) {

                $param['seller_code'] = session('seller_code');
                $this->insert($param);
            } else {

                $this->where('seller_code', session('seller_code'))->update($param);
            }
        } catch (\Exception $e) {

            return ['code' => -1, 'data' => [], 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => [], 'msg' => '设置成功'];
    }

    /**
     * 删除商户常见问题设置
     * @param $sellerCode
     * @return array
     */
    public function delQuestionConfBySellerCode($sellerCode)
    {
        try {

            $this->where('seller_code', $sellerCode)->delete();
        } catch (\Exception $e) {

            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => '', 'msg' => 'ok'];
    }
}