<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2019/3/3
 * Time: 11:54 AM
 */
namespace app\seller\model;

use think\Model;

class System extends Model
{
    protected $table = 'v2_system';

    /**
     * 获取商家的配置
     * @return array
     */
    public function getSellerConfig()
    {
        try {

            $res = $this->where('seller_id', session('seller_user_id'))
                ->findOrEmpty()->toArray();
        }catch (\Exception $e) {

            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $res, 'msg' => 'ok'];
    }

    /**
     * 编辑系统设置
     * @param $param
     * @return array
     */
    public function editSystem($param)
    {
        try {
            $this->save($param, ['seller_id' => session('seller_user_id')]);
        }catch (\Exception $e) {

            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => '', 'msg' => '编辑成功'];
    }
}