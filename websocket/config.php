<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2020/5/17
 * Time: 8:39 PM
 */

return [

    // websocket 对外服务端口
    'ws_port' => 2020,

    // http api 服务对外端口
    'api_port' => 2945,

    // gateway worker number
    'gateway_worker' => 2,

    // business worker number
    'business_worker' => 2,

    // 是否开启客服只允许单点登录 0 不开启 1 开启,
    // 切换需要重启
    'single_login' => 1,

    // 是否开启 ssl
    'is_open_ssl' => false,

    'context' => [
        'ssl' => [
            // 请使用绝对路径
            'local_cert'  => 'D:\software\goworkspace\test\php-kefu\websocket\fullchain.pem', // 也可以是crt文件
            'local_pk'  => 'D:\software\goworkspace\test\php-kefu\websocket\privkey.pem',
            'verify_peer'   => false,
            'allow_self_signed' => true,// 是否允许自签名证书
        ]
    ],

    'database' => [
        'host'        => '127.0.0.1',
        'user'        => 'phpkefu',
        'password'    => 'phpkefu',
        'database'    => 'phpkefu',
        'port'        => '3306',
        'charset'     => 'utf8',
    ]
];