<?php
namespace app\service\controller;

use app\model\KeFu;
use app\model\Seller;
use app\model\System;
use app\model\Cate;
use think\Controller;

class Login extends Controller
{
    public function index()
    {
        $this->assign([
            'seller' => input('param.u'),
            'version' => config('version.version')
        ]);
		
		if(request()->isMobile()){
			return $this->fetch('mobile');
        }

        return $this->fetch();
    }

    // web 登录
    public function doLogin()
    {
        if(request()->isAjax()){

            $userName = input('post.username');
            $password = input('post.password');
             $captcha = input("post.validcode");
            $seller = input('post.seller');

            if(!captcha_check($captcha)){
                return json(['code' => -3, 'data' => '', 'msg' => '验证码错误']);
            }

            $keFu = new KeFu();
            $keFuInfo = $keFu->getKeFuInfo($userName, $seller);

            if(0 != $keFuInfo['code'] || empty($keFuInfo['data'])){
                return json(['code' => -1, 'data' => '', 'msg' => '客服不存在']);
            }

            if (0 == $keFuInfo['data']['kefu_status']) {
            	return json(['code' => -3, 'data' => '', 'msg' => '该客服已经被禁用']);
            }

            if(md5($password . config('service.salt')) != $keFuInfo['data']['kefu_password']){
                return json(['code' => -2, 'data' => '', 'msg' => '用户名密码错误']);
            }

            // 检测客服所属商户的有效期
            $seller = new Seller();
            $sellerInfo = $seller->getSellerInfo($keFuInfo['data']['seller_code']);

            if (empty($sellerInfo['data'])) {
                return json(['code' => -4, 'data' => '', 'msg' => '客服所属商户不存在']);
            }

            if (0 == $sellerInfo['data']['seller_status']) {
                return json(['code' => -5, 'data' => '', 'msg' => '商户尚未激活']);
            }

            if (date("Y-m-d H:i:s") > $sellerInfo['data']['valid_time']) {
                return json(['code' => -6, 'data' => '', 'msg' => '商户使用期已过']);
            }

            // 设置session标识状态
            session('kf_user_name', $keFuInfo['data']['kefu_name']);
            session('kf_user_id', $keFuInfo['data']['kefu_code']);
            session('kf_id', $keFuInfo['data']['kefu_id']);
            session('kf_user_avatar', $keFuInfo['data']['kefu_avatar']);
            session('kf_seller_id', $keFuInfo['data']['seller_id']);
            session('kf_seller_code', $keFuInfo['data']['seller_code']);

            return json(['code' => 0, 'data' => url('index/index', ['u' => $keFuInfo['data']['seller_code']]), 'msg' => '登录成功']);
        }

        $this->error('非法访问');
    }

    // 正常业务退出
    public function loginOut()
    {
        db('kefu')->where('kefu_code', session('kf_user_id'))->setField('online_status', 0);
        $sellerCode = session('kf_seller_code');

        session('kf_user_name', null);
        session('kf_user_id', null);
        session('kf_user_avatar', null);
        session('kf_seller_id', null);
        session('kf_seller_code', null);

        $this->redirect(url('login/index', ['u' => $sellerCode]));
    }

    // 单点登录被挤下线退出
    public function ssoLoginOut()
    {
        $sellerCode = session('kf_seller_code');

        session('kf_user_name', null);
        session('kf_user_id', null);
        session('kf_user_avatar', null);
        session('kf_seller_id', null);
        session('kf_seller_code', null);

        $this->redirect(url('login/index', ['u' => $sellerCode]));
    }

    public function loginError()
    {
        return $this->fetch('error');
    }

    // pc client 登录接口
    public function clientLogin()
    {
        header('Content-Type: text/html;charset=utf-8');
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Methods:POST,GET,OPTIONS,DELETE');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Headers: Content-Type,Content-Length,Accept-Encoding,X-Requested-with, Origin');

        if (request()->isPost()) {

            $userName = input('post.kefuName');
            $password = input('post.password');
            $seller = input('post.sellerName');

            // 检测商户信息
            $sellerModel = new Seller();
            $sellerInfo = $sellerModel->getSellerInfoByName($seller);

            if (empty($sellerInfo['data'])) {
                return json(['code' => -1, 'data' => '', 'msg' => '客服所属商户不存在']);
            }

            if (0 == $sellerInfo['data']['seller_status']) {
                return json(['code' => -2, 'data' => '', 'msg' => '商户尚未激活']);
            }

            if (date("Y-m-d H:i:s") > $sellerInfo['data']['valid_time']) {
                return json(['code' => -3, 'data' => '', 'msg' => '商户使用期已过']);
            }

            $keFu = new KeFu();
            $keFuInfo = $keFu->getKeFuInfo($userName, $sellerInfo['data']['seller_code']);

            if(0 != $keFuInfo['code'] || empty($keFuInfo['data'])){
                return json(['code' => -4, 'data' => '', 'msg' => '客服不存在']);
            }

            if (0 == $keFuInfo['data']['kefu_status']) {
                return json(['code' => -5, 'data' => '', 'msg' => '该客服已经被禁用']);
            }

            if(md5($password . config('service.salt')) != $keFuInfo['data']['kefu_password']){
                return json(['code' => -6, 'data' => '', 'msg' => '用户名密码错误']);
            }

            $time = time();
            $safeToken = md5($keFuInfo['data']['seller_code'] . config('service.salt') . $time);
            $token =  $keFuInfo['data']['seller_code'] . '-' . $time  . '-' . $safeToken;

            $cateModel = new Cate();
            $systemModel = new System();

            $response = [
                'port' => config('service_socketio.socket_port'),
                'seller_id' => $keFuInfo['data']['seller_id'],
                'seller' => $keFuInfo['data']['seller_code'],
                'socket' => config('service.protocol') . config('service.socket') . '/' . $token,
                'userId' => $keFuInfo['data']['kefu_id'],
                'userName' => $keFuInfo['data']['kefu_name'],
                'userCode' => $keFuInfo['data']['kefu_code'],
                'userAvatar' => $keFuInfo['data']['kefu_avatar'],
                'word' => $cateModel->getSellerWord($sellerInfo['data']['seller_id'], $keFuInfo['data']['seller_code'])['data'],
                'system' => $systemModel->getSellerConfig($keFuInfo['data']['seller_code'])['data'],
                'token' => $token
            ];

            cache($response['seller'] . '-' . 'KF_' . $response['userCode'], $token, 86400);

            return json(['code' => 0, 'data' => $response, 'msg' => '登录成功']);
        }
    }

    // app登录接口
    public function appLogin()
    {
        header('Content-Type: text/html;charset=utf-8');
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Methods:POST,GET,OPTIONS,DELETE');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Headers: Content-Type,Content-Length,Accept-Encoding,X-Requested-with, Origin');

        if (request()->isPost()) {

            $userName = input('post.username');
            $password = input('post.password');
            $seller = input('post.seller');

            // 检测商户信息
            $sellerModel = new Seller();
            $sellerInfo = $sellerModel->getSellerInfoByName($seller);

            if (empty($sellerInfo['data'])) {
                return json(['code' => -1, 'data' => '', 'msg' => '客服所属商户不存在']);
            }

            if (0 == $sellerInfo['data']['seller_status']) {
                return json(['code' => -2, 'data' => '', 'msg' => '商户尚未激活']);
            }

            if (date("Y-m-d H:i:s") > $sellerInfo['data']['valid_time']) {
                return json(['code' => -3, 'data' => '', 'msg' => '商户使用期已过']);
            }

            $keFu = new KeFu();
            $keFuInfo = $keFu->getKeFuInfo($userName, $sellerInfo['data']['seller_code']);

            if(0 != $keFuInfo['code'] || empty($keFuInfo['data'])){
                return json(['code' => -4, 'data' => '', 'msg' => '客服不存在']);
            }

            if (0 == $keFuInfo['data']['kefu_status']) {
                return json(['code' => -5, 'data' => '', 'msg' => '该客服已经被禁用']);
            }

            if(md5($password . config('service.salt')) != $keFuInfo['data']['kefu_password']){
                return json(['code' => -6, 'data' => '', 'msg' => '用户名密码错误']);
            }

            $time = time();
            $safeToken = md5($keFuInfo['data']['seller_code'] . config('service.salt') . $time);
            $token =  $keFuInfo['data']['seller_code'] . '-' . $time  . '-' . $safeToken;

            $response = [
                'port' => config('service_socketio.socket_port'),
                'seller_id' => $keFuInfo['data']['seller_id'],
                'seller' => $keFuInfo['data']['seller_code'],
                'socket' => config('service.protocol') . config('service.socket') . '/' . $token,
                'userId' => $keFuInfo['data']['kefu_id'],
                'userName' => $keFuInfo['data']['kefu_name'],
                'userCode' => $keFuInfo['data']['kefu_code'],
                'userAvatar' => $keFuInfo['data']['kefu_avatar'],
                'token' => $token
            ];

            cache($response['seller'] . '-' . 'KF_' . $response['userCode'], $token, 86400);

            return json(['code' => 0, 'data' => $response, 'msg' => '登录成功']);
        }
    }

    // 客户端退出
    public function clientLoginOut()
    {
        $sellerCode = input('param.seller_code');
        $keFuCode = ltrim(input('param.kefu_code'), 'KF_');

        db('kefu')->where('seller_code', $sellerCode)
            ->where('kefu_code', $keFuCode)
            ->setField('online_status', 0);

        return json(['code' => 0, 'data' => '', 'msg' => '退出成功']);
    }

    // app退出登录
    public function appLoginOut()
    {
        $sellerCode = input('param.seller_code');
        $keFuCode = ltrim(input('param.kefu_code'), 'KF_');

        db('kefu')->where('seller_code', $sellerCode)
            ->where('kefu_code', $keFuCode)
            ->setField('online_status', 0);

        cache($sellerCode . '-' . 'KF_' . $keFuCode, null);

        return json(['code' => 0, 'data' => '', 'msg' => '退出成功']);
    }
}