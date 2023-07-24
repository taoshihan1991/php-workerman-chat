<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Date: 2019/3/4
 * Time: 10:56
 */
namespace app\admin\model;

use think\facade\Log;
use think\Model;

class System extends Model
{
    protected $table = 'v2_system';

    /**
     * 初始化商户
     * @param $param
     * @return array
     */
    public function initSellerConfig($param)
    {
        try {

            $has = $this->where('seller_id', $param['seller_id'])->findOrEmpty()->toArray();
            if(!empty($has)) {
                return ['code' => 0, 'data' => [], 'msg' => 'ok'];
            }

            $this->insert($param);

        } catch (\Exception $e) {

            Log::error($e->getMessage());
            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => [], 'msg' => 'ok'];
    }

    /**
     * 移除商户配置项
     * @param $sellerId
     * @return array
     */
    public function removeConfig($sellerId)
    {
        try {

           $this->where('seller_id', $sellerId)->delete();
        } catch (\Exception $e) {

            Log::error($e->getMessage());
            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => [], 'msg' => 'ok'];
    }
}