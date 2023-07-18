<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2019/9/28
 * Time: 4:57 PM
 */
return [

    // 默认后台添加商户有效时间 30 天
    'default_admin_add_day' => 30,

    // 默认注册商户有效时间 3 天
    'default_reg_day' => 3,

    // 默认商户最大坐席数 1 个
    'default_max_kefu_num' => 1,

    // 默认商户最大分组数 1 个
    'default_max_group_num' => 1,

    // 是否开启提前预览用户输入 0 关闭 1 开启
    'open_pre_see' => 0,

    // socket.io 还是 websocket 模式
    // 1: socket.io  2: websocket
    'model' => 2
];