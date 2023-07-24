<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2019/10/24
 * Time: 9:56 PM
 */
namespace app\model;

use think\Model;

class KeFuCate extends Model
{
    protected $table = 'v2_kefu_word_cate';

    /**
     * 根据获取商户客服的常用语
     * @param $sellerId
     * @param $kefuId
     * @return array
     */
    public function getKeFuWord($sellerId, $kefuId)
    {
        try {

            $res = $this->field('cate_id,cate_name')
                ->where('seller_id', $sellerId)
                ->where('kefu_id', $kefuId)
                ->select()->toArray();
            if (!empty($res)) {

                $wordModel = new KeFuWord();

                foreach ($res as $key => $vo) {
                    $res[$key]['word'] = $wordModel->getKeFuWordByCate($vo['cate_id'], $kefuId)['data'];
                }
            }
        } catch (\Exception $e) {

            return ['code' => -1, 'data' => [], 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $res, 'msg' => 'ok'];
    }

    /**
     * 根据名称获取客服分类名称
     * @param $cateName
     * @param $sellerId
     * @param $keFuId
     * @return array
     */
    public function getKeFuCateInfoByName($cateName, $sellerId, $keFuId)
    {
        try {

            $res = $this->where('cate_name', $cateName)->where('seller_id', $sellerId)
                ->where('kefu_id', $keFuId)->find();
        } catch (\Exception $e) {

            return ['code' => -1, 'data' => [], 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $res, 'msg' => 'ok'];
    }

    /**
     * 添加分类
     * @param $param
     * @return array
     */
    public function addKeFuCate($param)
    {
        try {

            $this->insert($param);
        } catch (\Exception $e) {

            return ['code' => -1, 'data' => [], 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => [], 'msg' => '添加分类成功'];
    }

    /**
     * 编辑分类
     * @param $param
     * @param $where
     * @return array
     */
    public function editKeFuCate($param, $where)
    {
        try {

            $this->where($where)->update($param);
        } catch (\Exception $e) {

            return ['code' => -1, 'data' => [], 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => [], 'msg' => '编辑分类成功'];
    }

    /**
     * 删除客服分类
     * @param $where
     * @return array
     */
    public function delKeFuCate($where)
    {
        try {

            $this->where($where)->delete();

            $wordModel = new KeFuWord();
            unset($where['seller_id']);
            $wordModel->delKeFuWordByCate($where);
        } catch (\Exception $e) {

            return ['code' => -1, 'data' => [], 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => [], 'msg' => '删除分类成功'];
    }

    /**
     * 获取所有客服的分类
     * @param $where
     * @return array
     */
    public function getAllKeFuCate($where)
    {
        try {

            $res = $this->field('cate_id,cate_name')->where($where)->select();
        } catch (\Exception $e) {

            return ['code' => -1, 'data' => [], 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $res, 'msg' => 'ok'];
    }
}