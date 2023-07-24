<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2020/6/30
 * Time: 9:54 PM
 */
namespace app\utils;

use app\strategy\impl\CircleImpl;
use app\strategy\impl\FreeDegreeImpl;
use app\strategy\impl\RandImpl;

class Factory
{
    public static function getObject($objStr)
    {
        switch ($objStr) {

            case 'circle':
                return new CircleImpl();
                break;

            case 'freeDegree':
                return new FreeDegreeImpl();
                break;

            case 'rand':
                return new RandImpl();
                break;
        }
    }
}