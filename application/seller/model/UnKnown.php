<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2019/12/1
 * Time: 9:39 PM
 */
namespace app\seller\model;

use think\Model;

class UnKnown extends Model
{
    protected $table = 'v2_unknown_question';

    /**
     * 获取商户未知问题列表
     * @param $limit
     * @param array $where
     * @return array
     */
    public function getQuestionList($limit, $where = [])
    {
        try {

            $res = $this->where('seller_id', session('seller_user_id'))
                ->where($where)
                ->order('question_id', 'desc')
                ->paginate($limit);
        }catch (\Exception $e) {

            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $res, 'msg' => 'ok'];
    }

    /**
     * 删除未知问题
     * @param $questionId
     * @return array
     */
    public function delQuestion($questionId)
    {
        try {

            $this->where('question_id', $questionId)->where('seller_id', session('seller_user_id'))->delete();
        }catch (\Exception $e) {

            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => '', 'msg' => '删除成功'];
    }
}