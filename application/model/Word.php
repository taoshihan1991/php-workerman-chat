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

class Word extends Model
{
    protected $table = 'v2_word';

    /**
     * 根据分类获取商户的常用语
     * @param $cateId
     * @param $sellerCode
     * @return array
     */
    public function getSellerWordByCate($cateId, $sellerCode)
    {
        try {

            $res = $this->where('cate_id', $cateId)
                ->where('seller_code', $sellerCode)->select()->toArray();
        } catch (\Exception $e) {

            return ['code' => -1, 'data' => [], 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $res, 'msg' => 'ok'];
    }

    /**
     * 获取商户所有的常用语
     * @param $sellerCode
     * @return array
     */
    public function getSellerAllWord($sellerCode)
    {
        try {

            $res = $this->field('title as data,word as value')
                ->where('seller_code', $sellerCode)->select()->toArray();
        } catch (\Exception $e) {

            return ['code' => -1, 'data' => [], 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $res, 'msg' => 'ok'];
    }
}