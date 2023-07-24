<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2019/2/16
 * Time: 8:19 PM
 */
namespace app\service\controller;

use app\model\Cate;
use app\model\KeFuCate;
use app\model\KeFuWord;
use app\model\System;
use app\model\Word;

class Index extends Base
{
    // 客服服务台
    public function index()
    {
        $sellerCode = session('kf_seller_code');

        $time = time();
        $safeToken = md5($sellerCode . config('service.salt') . $time);
        $token =  $sellerCode . '-' . $time  . '-' . $safeToken;

        $cateModel = new Cate();
        $systemModel = new System();
        $wordModel = new Word();

        $this->assign([
            'port' => config('service_socketio.socket_port'),
            'seller' => $sellerCode,
            'socket' => config('service.protocol') . config('service.socket') . '/' . $token,
            'userName' => session('kf_user_name'),
            'userCode' => session('kf_user_id'),
            'userAvatar' => session('kf_user_avatar'),
            'word' => $cateModel->getSellerWord(session('kf_seller_id'), session('kf_seller_code'))['data'],
            'allWord' => json_encode($wordModel->getSellerAllWord(session('kf_seller_code'))['data']),
            'system' => $systemModel->getSellerConfig(session('kf_seller_code'))['data'],
            'model' => config('seller.model')
        ]);

        if (request()->isMobile()) {
            return $this->fetch('mobile');
        }

        return $this->fetch();
    }
}