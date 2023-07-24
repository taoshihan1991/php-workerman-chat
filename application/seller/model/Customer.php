<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Date: 2019/3/4
 * Time: 14:46
 */
namespace app\seller\model;

use think\Model;

class Customer extends Model
{
    protected $table = 'v2_customer';

    /**
     * 今日在线访客数
     * @return array
     */
    public function getTodayOnlineCustomerNum()
    {
        try {

            $res = $this->where('seller_code', session('seller_code'))->where('online_status', 1)->count();
        }catch (\Exception $e) {

            return ['code' => -1, 'data' => 0, 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $res, 'msg' => 'ok'];
    }

    /**
     * 湖区访客地域信息
     * @return array
     */
    public function getCustomerAreaInfo()
    {
        try {

            $res = $this->field('province as name,count(*) as value')->where('seller_code', session('seller_code'))
                ->group('province')->select();

            foreach ($res as $key => $vo) {
                if (!isAllChinese($vo['name'])) {
                    unset($res[$key]);
                } else {
                    $res[$key]['name'] = str_replace('省', '', $vo['name']);
                }
            }

        } catch (\Exception $e) {

            return ['code' => -1, 'data' => [], 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $res, 'msg' => 'success'];
    }
}