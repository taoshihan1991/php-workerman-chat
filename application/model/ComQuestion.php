<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Date: 2019/3/4
 * Time: 14:22
 */
namespace app\model;

use think\Model;

class ComQuestion extends Model
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

            $info = $this->where('seller_code', $sellerCode)->select()->toArray();
        } catch (\Exception $e) {

            return ['code' => -1, 'data' => [], 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $info, 'msg' => ''];
    }

    /**
     * 添加商户问题成功
     * @param $param
     * @return array
     */
    public function addSellerQuestion($param)
    {
        try {

            $has = $this->where('seller_code', session('seller_code'))->where('question', $param['question'])->findOrEmpty();
            if (empty($has)) {
                return ['code' => -1, 'data' => [], 'msg' => '该问题已经存在'];
            }

            $param['seller_code'] = session('seller_code');
            $param['add_time'] = date('Y-m-d H:i:s');

            $this->insert($param);
        } catch (\Exception $e) {

            return ['code' => -1, 'data' => [], 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => [], 'msg' => '添加成功'];
    }

    /**
     * 删除商户问题
     * @param $qId
     * @return array
     */
    public function delSellerQuestion($qId)
    {
        try {

            $this->where('seller_code', session('seller_code'))
                ->where('question_id', $qId)->delete();
        } catch (\Exception $e) {

            return ['code' => -1, 'data' => [], 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => [], 'msg' => '删除成功'];
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

            $res = $this->where('seller_code', $sellerCode)
                ->where('question_id', $questionId)->findOrEmpty();
        } catch (\Exception $e) {

            return ['code' => -1, 'data' => [], 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $res, 'msg' => 'success'];
    }

    /**
     * 删除商户常见问题
     * @param $sellerCode
     * @return array
     */
    public function delQuestionBySellerCode($sellerCode)
    {
        try {

            $this->where('seller_code', $sellerCode)->delete();
        } catch (\Exception $e) {

            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => '', 'msg' => 'ok'];
    }
}