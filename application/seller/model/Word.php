<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Date: 2019/3/1
 * Time: 14:22
 */
namespace app\seller\model;

use think\Model;

class Word extends Model
{
    protected $table = 'v2_word';
    protected $autoWriteTimestamp = 'datetime';

    /**
     * 获取常用语列表
     * @param $limit
     * @param $where
     * @return array
     */
    public function getWordList($limit, $where = [])
    {
        try {

            $res = $this->field('v2_word.*,v2_word_cate.cate_name')
                ->leftJoin('v2_word_cate', 'v2_word_cate.cate_id = v2_word.cate_id')
                ->where('v2_word.seller_code', session('seller_code'))
                ->where($where)
                ->order('word_id', 'desc')
                ->paginate($limit);
        }catch (\Exception $e) {
            echo $e->getMessage();die;
            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $res, 'msg' => 'ok'];
    }

    /**
     * 增加常用语
     * @param $param
     * @return array
     */
    public function addWord($param)
    {
        try {

            $has = $this->where('word', $param['word'])
                ->where('cate_id', $param['cate_id'])
                ->where('seller_code', session('seller_code'))
                ->findOrEmpty()->toArray();
            if(!empty($has)) {
                return ['code' => -2, 'data' => '', 'msg' => '该常用语已经存在'];
            }

            $this->save($param);
        }catch (\Exception $e) {

            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => '', 'msg' => '添加成功'];
    }

    /**
     * 编辑常用语
     * @param $param
     * @return array
     */
    public function editWord($param)
    {
        try {

            $has = $this->where('word', $param['word'])
                ->where('cate_id', $param['cate_id'])
                ->where('seller_code', session('seller_code'))
                ->where('word_id', '<>', $param['word_id'])
                ->findOrEmpty()->toArray();
            if(!empty($has)) {
                return ['code' => -2, 'data' => '', 'msg' => '常用语已经存在'];
            }

            $this->where('word_id', $param['word_id'])->update($param);
        }catch (\Exception $e) {

            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => '', 'msg' => '编辑成功'];
    }

    /**
     * 删除常用语
     * @param $wordId
     * @return array
     */
    public function delWord($wordId)
    {
        try {

            $this->where('word_id', $wordId)->where('seller_code', session('seller_code'))->delete();
        }catch (\Exception $e) {

            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => '', 'msg' => '删除成功'];
    }

    /**
     * 检测商户某个分类下是否有常用语
     * @param $cateId
     * @return array
     */
    public function checkHasWordByCateId($cateId)
    {
        try {

            $has = $this->where('cate_id', $cateId)->where('seller_code', session('seller_code'))
                ->count();
        }catch (\Exception $e) {

            return ['code' => -1, 'data' => 0, 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $has, 'msg' => 'success'];
    }

    /**
     * 获取常用语详情
     * @param $wordId
     * @return array
     */
    public function getWordInfoById($wordId)
    {
        try {

            $has = $this->where('word_id', $wordId)->where('seller_code', session('seller_code'))
                ->findOrEmpty();
        }catch (\Exception $e) {

            return ['code' => -1, 'data' => 0, 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $has, 'msg' => 'success'];
    }

    /**
     * 批量导入常用语
     * @param $cateId
     * @param $words
     * @return array
     */
    public function batchAddWord($cateId, $words)
    {
        try {

            $words = explode(PHP_EOL, $words);
            $addParam = [];

            $total = 0;
            foreach ($words as $vo) {

                $has = $this->where('word', $vo)
                    ->where('cate_id', $cateId)
                    ->where('seller_code', session('seller_code'))
                    ->findOrEmpty()->toArray();
                if(!empty($has)) {
                    continue;
                }

                $addParam[] = [
                    'word' => $vo,
                    'seller_code' => session('seller_code'),
                    'cate_id' => $cateId,
                    'create_time' => date('Y-m-d H:i:s'),
                    'update_time' => date('Y-m-d H:i:s'),
                ];
                $total++;
            }

            if (!empty($addParam)) {
                $this->insertAll($addParam);
            }

        }catch (\Exception $e) {

            return ['code' => -1, 'data' => 0, 'msg' => '添加失败'];
        }

        return ['code' => 0, 'data' => [], 'msg' => '成功导入 ' . $total . ' 条'];
    }
}