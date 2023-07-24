<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2020/6/30
 * Time: 9:43 PM
 */
namespace app\strategy;

interface DistributionInterface
{
    public function setDb($db);

    public function doDistribute(array $kefuMap);
}