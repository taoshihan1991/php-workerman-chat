<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <服务器购买去wyyidc.cn>
// +----------------------------------------------------------------------

// 应用公共文件
/**
 * 获取mysql 版本
 * @return string
 */
function getMysqlVersion() {

    $conn = mysqli_connect(
        config('database.hostname') . ":" .  config('database.hostport'),
        config('database.username'),
        config('database.password'),
        config('database.database')
    );

    return mysqli_get_server_info($conn);
}

/**
 * 获取磁盘空间
 * @return string
 */
function getDiskSpace() {

    $isM = true;
    if (strstr(PHP_OS, 'WIN')) {

        $disk = disk_free_space("C:") / 1024 / 1024;
    } else {

        $disk = disk_free_space("/") / 1024 / 1024;
    }

    if ($disk > 1024) {
        $isM = false;
        $disk = $disk / 1024;
    }

    if ($disk > 1) {
        $diskDesc = number_format(ceil($disk));
    } else {
        $diskDesc = round($disk, 2);
    }

    $diskDesc .= ($isM) ? 'M' : 'G';

    return $diskDesc;
}

/**
 * 根据ip定位
 * @param $ip
 * @param $type
 * @return string | array
 * @throws Exception
 */
function getLocationByIp($ip, $type = 1)
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

/**
 * 计算时长
 * @param $seconds
 * @return string
 */
function changeTimeType($seconds)
{
    if ($seconds > 3600) {
        $hours = intval($seconds / 3600);
        $minutes = $seconds % 3600;
        $time = $hours . ":" . gmstrftime('%M:%S', $minutes);
    } else {
        $time = gmstrftime('%H:%M:%S', $seconds);
    }

    return $time;
}

/**
 * 计算有效期天数
 * @param $validDate
 * @return float
 */
function getValidDays($validDate)
{
    return floor((strtotime($validDate) - strtotime(date('Y-m-d H:i:s'))) / 86400);
}

/**
 * 是否全是中文
 * @param $str
 * @return bool
 */
function isAllChinese($str)
{
    // 新疆等少数民族可能有·
    if(strpos($str,'·')){
        // 将·去掉，看看剩下的是不是都是中文
        $str=str_replace("·",'',$str);
        if(preg_match('/^[\x7f-\xff]+$/', $str)){
            return true; // 全是中文
        }else{
            return false; //不全是中文
        }
    }else{
        if(preg_match('/^[\x7f-\xff]+$/', $str)){
            return true; // 全是中文
        }else{
            return false; // 不全是中文
        }
    }
}

/**
 * 获取设备信息
 * @param $ua
 * @return array
 */
function getDeviceInfo($ua)
{
    $deviceOs = '未知设备';
    $deviceVersion = '未知版本';
    // $ua = $_SERVER['HTTP_USER_AGENT'];
    if (strpos($ua, 'Android') !== false) {

        preg_match("/(?<=Android )[\d\.]{1,}/", $ua, $version);
        $deviceOs = 'Android';
        $deviceVersion = $version[0];
    } elseif (strpos($ua, 'iPhone') !== false) {

        preg_match("/(?<=CPU iPhone OS )[\d\_]{1,}/", $ua, $version);
        $deviceOs = 'iPhone';
        $deviceVersion = str_replace('_', '.', $version[0]);
    } elseif (strpos($ua, 'iPad') !== false) {

        preg_match("/(?<=CPU OS )[\d\_]{1,}/", $ua, $version);
        $deviceOs = 'iPad';
        $deviceVersion = str_replace('_', '.', $version[0]);

    } elseif (preg_match('/OmniWeb\/(v*)([^\s|;]+)/i', $ua, $regs)) {

        $deviceOs  = 'OmniWeb';
        $deviceVersion   = $regs[2];
    }elseif (preg_match('/Netscape([\d]*)\/([^\s]+)/i', $ua, $regs)) {

        $deviceOs = 'Netscape';
        $deviceVersion = $regs[2];
    }elseif (preg_match('/safari\/([^\s]+)/i', $ua, $regs) && !preg_match('/Chrome\/([^\s]+)/i', $ua, $regs2)) {

        $deviceOs = 'Safari';
        $deviceVersion = $regs[1];
    }elseif (preg_match('/MSIE\s([^\s|;]+)/i', $ua, $regs)) {

        $deviceOs = 'Internet Explorer';
        $deviceVersion = $regs[1];
    }elseif (preg_match('/Opera[\s|\/]([^\s]+)/i', $ua, $regs)) {

        $deviceOs = 'Opera';
        $deviceVersion = $regs[1];
    }elseif (preg_match('/NetCaptor\s([^\s|;]+)/i', $ua, $regs)) {

        $deviceOs  = '(Internet Explorer) NetCaptor';
        $deviceVersion  = $regs[1];
    }elseif (preg_match('/Maxthon/i', $ua, $regs)) {

        $deviceOs = '(Internet Explorer) Maxthon';
        $deviceVersion = '';
    } elseif (preg_match('/360SE/i', $ua, $regs)) {

        $deviceOs = '(Internet Explorer) 360SE';
        $deviceVersion   = '';
    } elseif (preg_match('/SE 2.x/i', $ua, $regs)) {

        $deviceOs = '(Internet Explorer) 搜狗';
        $deviceVersion = '';
    }elseif (preg_match('/FireFox\/([^\s]+)/i', $ua, $regs)) {

        $deviceOs  = 'FireFox';
        $deviceVersion   = $regs[1];
    }elseif (preg_match('/Lynx\/([^\s]+)/i', $ua, $regs)) {

        $deviceOs  = 'Lynx';
        $deviceVersion   = $regs[1];
    }elseif(preg_match('/Chrome\/([^\s]+)/i', $ua, $regs)) {

        $deviceOs  = 'Chrome';
        $deviceVersion   = $regs[1];
    }

    return [
        'deviceOs' => $deviceOs,
        'deviceVersion' => $deviceVersion
    ];
}

/**
 * 获取基础样式
 * @param $type
 * @return string
 */
function getBaseCss($type)
{
    $css = '';
    if (1 == $type) {
        $css = 'position:fixed;z-index:201902151030;bottom:50px;padding:0px 15px 0px 15px;margin:0;min-width:120px;height:40px;line-height:40px;text-align:center;color:#fff;font-size:13px;cursor:pointer;';
    } else if (2 == $type) {
        $css = 'right: 0px;min-height: 150px;width: 40px !important;overflow-x: visible;overflow-y: visible;overflow-wrap: normal;position: fixed;z-index: 9999999;text-align: center;box-sizing: border-box;border-width: initial;border-style: none;border-color: initial;border-image: initial;margin: 0px;padding: 15px;box-shadow: rgba(0, 0, 0, 0.16) 0px 5px 14px;cursor: pointer;color: #fff;line-height:17px;';
    }

    return $css;
}
