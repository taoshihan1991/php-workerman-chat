<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2019/6/21
 * Time: 9:54 PM
 */
return [

    // socket.io 端口
    'socket_port' => 2020,

    // http api 端口
    'http_port' => 2945,

    // api接口
    'api_url' => 'http://www.phpkefu.com/index/test/receive',

    // 当前系统域名
    'domain' => 'http://www.phpkefu.com',

    // 弹层模式是否校验域名 0 不校验 1 校验
    'default_box_link_flag' => 0,

    // 直连模式校验接入域名 0 不校验 1 校验
    'default_link_flag' => 0,

    // 是否开启客服只允许单点登录 0 不开启 1 开启,
    // 切换需要重启 socket.io
    'single_login' => 0,

    // 是否开启 ssl
    'is_open_ssl' => false,

    // ssl 上下文
    'context' => [
        'ssl' => [
            'local_cert'  => '/your/path/of/server.pem', // 服务器的证书绝对路径
            'local_pk'    => '/your/path/of/server.key', // 服务器的证书绝对路径
            'verify_peer' => false,
        ]
    ],
];