<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2020/6/30
 * Time: 9:48 PM
 */
namespace app\strategy\impl;

use app\model\Service;
use app\strategy\DistributionInterface;

/**
 * 空限度 + 权重的分配策略
 * NOTICE v1.1版本暂未加入权重
 * Class CircleImpl
 * @package app\strategy\impl
 */
class FreeDegreeImpl implements DistributionInterface
{
    private $db = null;

    public function setDb($db)
    {
        $this->db = $db;
    }

    public function doDistribute(array $kefuMap)
    {
        $serviceKefu = [];
        $service = new Service($this->db);
        foreach($kefuMap as $key => $vo) {

            $num = $service->getNowServiceNum($vo['kefu_code']);
            if(0 != $num['code']) {
                return ['code' => -7, 'data' => '', 'msg' => '获取当前服务数据失败'];
                break;
            }

            $serviceKefu[$key] = [
                'kefu_code' => $vo['kefu_code'],
                'kefu_name' => $vo['kefu_name'],
                'kefu_avatar' => $vo['kefu_avatar'],
                'free_degree' => round(($vo['max_service_num'] - $num['data']) / $vo['max_service_num'], 10) // 空闲度 0.xx
            ];
        }

        // 寻找最空闲的客服
        $returnKefu = [];
        if(!empty($serviceKefu)) {

            $returnKefu = $serviceKefu[0];
            foreach($serviceKefu as $key => $vo) {

                if(0 == $vo['free_degree']) {
                    continue;
                }

                if($vo['free_degree'] > $returnKefu['free_degree']) {
                    $returnKefu = $vo;
                }
            }
        }

        if($returnKefu['free_degree'] <= 0) {
            return ['code' => 202, 'data' => '', 'msg' => '客服全忙'];
        }
        unset($returnKefu['free_degree']);

        $returnKefu['kefu_code'] = 'KF_' . $returnKefu['kefu_code'];

        return ['code' => 0, 'data' => $returnKefu, '分配成功'];
    }
}