<?php
namespace app\service\controller;

use think\Controller;

class Base extends Controller
{
    public function initialize()
    {
        $sellerCode = input('param.u');
        if(empty($sellerCode)) {
            $this->redirect(url('login/loginError'));
        }

        if(empty(session('kf_user_name'))){

            $this->redirect(url('login/index', ['u' => $sellerCode]));
        }

        $this->assign([
            'version' => config('version.version')
        ]);
    }
}