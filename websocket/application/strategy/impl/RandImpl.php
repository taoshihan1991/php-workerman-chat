<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2020/6/30
 * Time: 9:48 PM
 */
namespace app\strategy\impl;

use app\strategy\DistributionInterface;

/**
 * 纯随机的分配模式
 * Class CircleImpl
 * @package app\strategy\impl
 */
class RandImpl implements DistributionInterface
{
    private $db = null;

    public function setDb($db)
    {
        $this->db = $db;
    }

    public function doDistribute(array $kefuMap)
    {
        $len = count($kefuMap);
        $nowStep = 0;
        $randomKey = array_rand($kefuMap);

        // ！！！ 随机分配最少最多随机的次数和现在在线客服人数相同，可能会有空闲的随机不到  ！！！
        $returnKF = [];
        while ($nowStep <= $len) {

            $nowStep++;
            if ($kefuMap[$randomKey]['now_service_num'] < $kefuMap[$randomKey]['max_service_num']) {
                $returnKF = $kefuMap[$randomKey];
                break;
            }
        }

        return $returnKF;
    }
}