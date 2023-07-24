<?php
namespace app\seller\controller;

use app\model\KeFu;
use app\model\Seller;
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

    public function doLogin()
    {
        if(request()->isAjax()){

            $userName = input('post.username');
            $password = input('post.password');
            $captcha = input("post.captcha");

            if(!captcha_check($captcha)){
                return json(['code' => -3, 'data' => '', 'msg' => '验证码错误']);
            }

            $seller = new Seller();
            $sellerInfo = $seller->getSellerInfoByName($userName);

            if(0 != $sellerInfo['code'] || empty($sellerInfo['data'])){
                return json(['code' => -1, 'data' => '', 'msg' => '商户不存在']);
            }

            if (0 == $sellerInfo['data']['seller_status']) {
                return json(['code' => -4, 'data' => '', 'msg' => '商户尚未激活']);
            }

            if(md5($password . config('service.salt')) != $sellerInfo['data']['seller_password']){
                return json(['code' => -2, 'data' => '', 'msg' => '用户名密码错误']);
            }

            if (date("Y-m-d H:i:s") > $sellerInfo['data']['valid_time']) {
                return json(['code' => -5, 'data' => '', 'msg' => '商户使用期已过']);
            }

            // 设置session标识状态
            session('seller_user_name', $sellerInfo['data']['seller_name']);
            session('seller_user_id', $sellerInfo['data']['seller_id']);
            session('seller_code', $sellerInfo['data']['seller_code']);
            return json(['code' => 0, 'data' => url('index/index'), 'msg' => '登录成功']);
        }

        $this->error('非法访问');
    }

    // 商户注册
    public function reg()
    {
        if(session('seller_user_name')){
            $this->redirect(url('/seller/index'));
        }

        if(!config('service.reg_flag')) {
            $this->error('禁止商户注册');
        }

        if(request()->isPost()) {

            $param = input('post.');

            if(!captcha_check($param['vercode'])) {
                return json(['code' => -1, 'data' => '', 'msg' => '验证码错误']);
            }

            if(empty($param['username']) || empty($param['password']) || empty($param['access_url'])
                || empty($param['vercode'])) {

                return json(['code' => -2, 'data' => '', 'msg' => '必填项必须填写']);
            }

            if(substr($param['access_url'], 0, 4) != 'http') {

                return json(['code' => -3, 'data' => '', 'msg' => '接入域名格式错误']);
            }

            $param = [
                'seller_code' => uniqid(),
                'seller_name' => $param['username'],
                'seller_password' => md5($param['password'] . config('service.salt')),
                'seller_avatar' => '',
                'seller_status' => 1,
                'access_url' => $param['access_url'],
                'valid_time' => date('Y-m-d H:i:s', strtotime("+" . config('seller.default_reg_day') . " days")),
                'max_kefu_num' => config('seller.default_max_kefu_num'),
                'max_group_num' => config('seller.default_max_group_num'),
                'create_time' => date('Y-m-d H:i:s'),
                'update_time' => date('Y-m-d H:i:s')
            ];

            try {

                $has = db('seller')->where('seller_name', $param['seller_name'])->find();
                if(!empty($has)) {
                    return json(['code' => -5, 'data' => '', 'msg' => '该商户名已经存在']);
                }

                /*$has = db('seller')->where('access_url', $param['access_url'])->find();
                if(!empty($has)) {
                    return json(['code' => -6, 'data' => '', 'msg' => '该域名已经注册了']);
                }*/

                $sellerId = db('seller')->insertGetId($param);

                db('system')->insert([
                    'hello_word' => config('service.hello_word'),
                    'seller_id' => $sellerId,
                    'seller_code' => $param['seller_code'],
                    'hello_status' => 1,
                    'relink_status' => 1,
                    'auto_link' => 0,
                    'auto_link_time' => 30
                ]);
            } catch (\Exception $e) {

                return json(['code' => -4, 'data' => $e->getMessage(), 'msg' => '注册失败']);
            }

            return json(['code' => 0, 'data' => '', 'msg' => '注册']);
        }

        return $this->fetch();
    }

    public function loginOut()
    {
        session('seller_user_name', null);
        session('seller_user_id', null);
        session('seller_code', null);

        $this->redirect(url('login/index'));
    }

    public function loginError()
    {
        return $this->fetch('error');
    }
}