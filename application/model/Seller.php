<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2019/2/16
 * Time: 11:26 PM
 */
namespace app\model;

use think\facade\Log;
use think\Model;

class Seller extends Model
{
    protected $table = 'v2_seller';

    /**
     * 根据商户的标识获取商户信息
     * @param $sellerCode
     * @return array
     */
    public function getSellerInfo($sellerCode)
    {
        try {

            $res = $this->where('seller_code', $sellerCode)->findOrEmpty()->toArray();
        } catch (\Exception $e) {

            Log::error($e->getMessage());
            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $res, 'msg' => 'ok'];
    }

    /**
     * 根据商户名称 获取商户信息
     * @param $name
     * @return array
     */
    public function getSellerInfoByName($name)
    {
        try {

            $res = $this->where('seller_name', $name)->findOrEmpty()->toArray();
        } catch (\Exception $e) {

            Log::error($e->getMessage());
            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $res, 'msg' => 'ok'];
    }

    /**
     * 检测商户是否可以再建分组
     * @param $sellerId
     * @return array
     */
    public function checkCanAddGroup($sellerId)
    {
        $flag = 0; // 不可建
        try {

            $maxGroupNum = $this->field('max_group_num')->where('seller_id', $sellerId)->find();

            // 目前的已经建的分组数
            $nowGroupNumData = (new Group())->getSellerGroupNum($sellerId);
            if (0 != $nowGroupNumData['code']) {
                return $nowGroupNumData;
            }

            if ($maxGroupNum['max_group_num'] > $nowGroupNumData['data']) {
                $flag = 1;
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $flag, 'msg' => 'ok'];
    }

    /**
     * 检测是否可以再添加客服坐席
     * @param $sellerId
     * @return array
     */
    public function checkCanAddKeFu($sellerId)
    {
        $flag = 0; // 不可建
        try {

            $maxKefuNum = $this->field('max_kefu_num')->where('seller_id', $sellerId)->find();

            // 目前的已经建的客服数
            $nowKefuNumData = (new KeFu())->getSellerKeFuNum($sellerId);
            if (0 != $nowKefuNumData['code']) {
                return $nowKefuNumData;
            }

            if ($maxKefuNum['max_kefu_num'] > $nowKefuNumData['data']) {
                $flag = 1;
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $flag, 'msg' => 'ok'];
    }

    /**
     * 更新商户信息
     * @param $sellerId
     * @param $param
     * @return array
     */
    public function updateSellerInfo($sellerId, $param)
    {
        try {

            $this->where('seller_id', $sellerId)->update($param);
        } catch (\Exception $e) {
            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => '', 'msg' => 'ok'];
    }
}