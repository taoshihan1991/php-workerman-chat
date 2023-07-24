<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Date: 2019/3/1
 * Time: 14:02
 */
namespace app\seller\validate;

use think\Validate;

class GroupValidate extends Validate
{
    protected $rule =   [
        'group_name'  => 'require'
    ];

    protected $message  =   [
        'group_name.require' => '分组名称不能为空'
    ];
}