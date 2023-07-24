<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Date: 2019/3/1
 * Time: 14:02
 */
namespace app\seller\validate;

use think\Validate;

class SystemValidate extends Validate
{
    protected $rule =   [
        'hello_word'  => 'require'
    ];

    protected $message  =   [
        'hello_word.require' => '问候语不能为空'
    ];
}