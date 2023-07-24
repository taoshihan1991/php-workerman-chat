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

class Cate extends Model
{
    protected $table = 'v2_word_cate';

    /**
     * 根据分类获取商户的常用语
     * @param $sellerId
     * @param $sellerCode
     * @return array
     */
    public function getSellerWord($sellerId, $sellerCode)
    {
        try {

            $res = $this->field('cate_id,cate_name')->where('seller_id', $sellerId)->select()->toArray();
            if (!empty($res)) {

                $wordModel = new Word();

                foreach ($res as $key => $vo) {
                    $res[$key]['word'] = $wordModel->getSellerWordByCate($vo['cate_id'], $sellerCode)['data'];
                }
            }
        } catch (\Exception $e) {

            return ['code' => -1, 'data' => [], 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $res, 'msg' => 'ok'];
    }
}