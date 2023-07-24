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

class KeFuWord extends Model
{
    protected $table = 'v2_kefu_word';

    /**
     * 根据分类获取客服的常用语
     * @param $cateId
     * @param $kefuId
     * @return array
     */
    public function getKeFuWordByCate($cateId, $kefuId)
    {
        try {

            $res = $this->where('cate_id', $cateId)
                ->where('kefu_id', $kefuId)->order('word_id desc')->select()->toArray();
        } catch (\Exception $e) {

            return ['code' => -1, 'data' => [], 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $res, 'msg' => 'ok'];
    }

    /**
     * 删除分类下的常用语
     * @param $where
     * @return array
     */
    public function delKeFuWordByCate($where)
    {
        try {

            $res = $this->where($where)->delete();
        } catch (\Exception $e) {

            return ['code' => -1, 'data' => [], 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $res, 'msg' => 'ok'];
    }

    /**
     * 添加分类下的常用语
     * @param $param
     * @return array
     */
    public function addKeFuWord($param)
    {
        try {

            $this->insert($param);
        } catch (\Exception $e) {

            return ['code' => -1, 'data' => [], 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => [], 'msg' => '添加常用语成功'];
    }

    /**
     * 删除常用语
     * @param $where
     * @return array
     */
    public function delKeFuWord($where)
    {

        try {

            $this->where($where)->delete();
        } catch (\Exception $e) {

            return ['code' => -1, 'data' => [], 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => [], 'msg' => '删除常用语成功'];
    }
}