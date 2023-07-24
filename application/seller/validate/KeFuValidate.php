<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Date: 2019/3/1
 * Time: 14:02
 */
namespace app\seller\validate;

use think\Validate;

class KeFuValidate extends Validate
{
    protected $rule =   [
        'kefu_name'  => 'require',
        'kefu_password'   => 'require',
        'group_id'   => 'require|number',
        'max_service_num' => 'require|number'
    ];

    protected $message  =   [
        'kefu_name.require' => '客服名称不能为空',
        'kefu_password.require'   => '客服密码不能为空',
        'group_id.require'  => '请选择分组',
        'max_service_num.require'  => '请填写服务人数',
        'max_service_num.number'  => '服务人数必须是数字',
    ];

    protected $scene = [
        'edit'  =>  ['kefu_name', 'group_id', 'max_service_num']
    ];
}