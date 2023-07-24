<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2019/3/17
 * Time: 4:24 PM
 */
namespace app\admin\controller;

use app\admin\model\Admin;
use app\model\LoginLog;
use think\Controller;

class Login extends Controller
{
    // 登录页面
    public function index()
    {
        return $this->fetch();
    }

    // 处理登录
    public function doLogin()
    {
        if(request()->isPost()) {

            $param = input('post.');

            if(!captcha_check($param['vercode'])){
                return json(['code' => -3, 'data' => '', 'msg' => '验证码错误']);
            }

            $admin = new Admin();
            $adminInfo = $admin->getAdminByName($param['username']);

            $log = new LoginLog();

            if(0 != $adminInfo['code'] || empty($adminInfo['data'])) {

                $log->writeLoginLog($param['username'], 2);
                return json(['code' => -1, 'data' => '', 'msg' => '用户名密码错误']);
            }

            if(md5($param['password'] . config('service.salt')) != $adminInfo['data']['admin_password']){

                $log->writeLoginLog($param['username'], 2);
                return json(['code' => -2, 'data' => '', 'msg' => '用户名密码错误']);
            }

            // 设置session标识状态
            session('admin_user_name', $adminInfo['data']['admin_name']);
            session('admin_user_id', $adminInfo['data']['admin_id']);

            // 维护上次登录时间
            db('admin')->where('admin_id', $adminInfo['data']['admin_id'])->setField('last_login_time', date('Y-m-d H:i:s'));

            $log->writeLoginLog($param['username'], 1);

            return json(['code' => 0, 'data' => url('index/index'), 'msg' => '登录成功']);
        }
    }

    public function loginOut()
    {
        session('admin_user_name', null);
        session('admin_user_id', null);

        $this->redirect(url('login/index'));
    }
}