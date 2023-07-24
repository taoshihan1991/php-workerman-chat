<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Date: 2019/3/1
 * Time: 14:22
 */
namespace app\seller\model;

use think\Model;

class Knowledge extends Model
{
    protected $table = 'v2_knowledge_store';

    /**
     * 获取知识库列表
     * @param $limit
     * @param $where
     * @return array
     */
    public function getKnowledgeList($limit, $where = [])
    {
        try {

            $res = $this->where('seller_id', session('seller_user_id'))
                ->where($where)
                ->order('knowledge_id', 'desc')
                ->paginate($limit);
        }catch (\Exception $e) {

            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $res, 'msg' => 'ok'];
    }

    /**
     * 增加知识库
     * @param $param
     * @return array
     */
    public function addKnowledge($param)
    {
        try {

            $has = $this->where('question', $param['question'])
                ->where('seller_id', session('seller_user_id'))
                ->findOrEmpty()->toArray();
            if(!empty($has)) {
                return ['code' => -2, 'data' => '', 'msg' => '该知识库已经存在'];
            }

            $id = $this->insertGetId($param);
        }catch (\Exception $e) {

            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $id, 'msg' => '添加成功'];
    }

    /**
     * 编辑知识库
     * @param $param
     * @return array
     */
    public function editKnowledge($param)
    {
        try {

            $has = $this->where('question', $param['question'])
                ->where('seller_id', session('seller_user_id'))
                ->where('knowledge_id', '<>', $param['knowledge_id'])
                ->findOrEmpty()->toArray();
            if(!empty($has)) {
                return ['code' => -2, 'data' => '', 'msg' => '问题已经存在'];
            }

            $this->where('knowledge_id', $param['knowledge_id'])->update($param);
        }catch (\Exception $e) {

            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => '', 'msg' => '编辑成功'];
    }

    /**
     * 删除知识库
     * @param $knowledgeId
     * @return array
     */
    public function delKnowledge($knowledgeId)
    {
        try {

            $this->where('knowledge_id', $knowledgeId)->where('seller_id', session('seller_user_id'))->delete();
        }catch (\Exception $e) {

            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => '', 'msg' => '删除成功'];
    }

    /**
     * 获取分类信息
     * @param $knowledgeId
     * @return array
     */
    public function getKnowledgeInfoByCateId($knowledgeId)
    {
        try {

            $res = $this->where('knowledge_id', $knowledgeId)->where('seller_id', session('seller_user_id'))->find();
        }catch (\Exception $e) {

            return ['code' => -1, 'data' => [], 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $res, 'msg' => 'success'];
    }

    /**
     * 获取商户的可用分类
     * @return array
     */
    public function getSellerCate()
    {
        try {

            $res = $this->where('seller_id', session('seller_user_id'))->where('status', 1)->select();
        }catch (\Exception $e) {

            return ['code' => -1, 'data' => [], 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $res, 'msg' => 'success'];
    }
}