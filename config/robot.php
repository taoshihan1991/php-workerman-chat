<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2019/11/28
 * Time: 7:50 PM
 */
return [

    // 是否开始机器人问答 1 开启 0 关闭
    'robot_service' => 1,

    // elasticsearch 服务地址,请确保可用
    'es_host' => [
        'http://103.45.105.75:9200'
    ],

    // 是否记录未知问题 1 记录 0 不记录
    'save_unknown_question' => 1,

    // 机器人初始问候语
    'robot_hello_word' => 'AI智能客服为您服务',

    // 机器人服务标题
    'robot_title' => '智能客服',

    //  最多显示相关问题数量
    'default_think_tips' => 5,

    // 接口请求过于频繁限制
    'forbid_word' => '您问的太快了，访问歇一会再问吧',

    // 接口请求限制时间 3 秒
    'request_limit_time' => 3,

    // 疑似引导问答语
    'show_like_title' => '您是不是要问如下的问题：',

    // 默认回答
    'default_answer' => [
        '这个问题很有趣',
        '我不太明白您的意思,您可以换一个问题试试',
        '不太明白您的问题，不过我正在努力的学习中',
        '您的问题我已经拿着小本本记下了',
        '我不理解您的意思，不过我们可以再聊10块钱的'
    ]
];