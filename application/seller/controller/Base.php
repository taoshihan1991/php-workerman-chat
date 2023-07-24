<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Date: 2019/3/1
 * Time: 10:03
 */
namespace app\seller\controller;

use think\Controller;

class Base extends Controller
{
    public function initialize()
    {
        if(empty(session('seller_user_name'))){
            $this->redirect(url('login/index'));
        }

        $this->assign([
            'seller_name' => session('seller_user_name'),
            'seller_id' => session('seller_user_id'),
            'seller_code' => session('seller_code'),
            'version' => config('version.version')
        ]);
    }
}