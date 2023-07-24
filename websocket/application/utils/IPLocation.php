<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2020/5/17
 * Time: 9:21 PM
 */
namespace app\utils;

class IPLocation
{
    public static function getLocationByIp($ip, $type = 1)
    {
        $ip2region = new \Ip2Region();
        $info = $ip2region->btreeSearch($ip);

        $info = explode('|', $info['region']);

        $address = '';
        foreach($info as $vo) {
            if('0' !== $vo) {
                $address .= $vo . '-';
            }
        }

        if (2 == $type) {
            return ['province' => $info['2'], 'city' => $info['3']];
        }

        return rtrim($address, '-');
    }
}