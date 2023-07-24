<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2019/2/28
 * Time: 9:33 PM
 */
namespace app\admin\validate;

use think\Validate;

class SellerValidate extends Validate
{
    protected $rule =   [
        'seller_name'  => 'require',
        'seller_password'   => 'require',
        'access_url' => 'require',
    ];

    protected $message  =   [
        'seller_name.require' => '商户名称不能为空',
        'seller_password.require'   => '商户密码不能为空',
        'access_url.require'  => '接入域名不能为空'
    ];

    protected $scene = [
        'edit'  =>  ['seller_name', 'access_url']
    ];
}