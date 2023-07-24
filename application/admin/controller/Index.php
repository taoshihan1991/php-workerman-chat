<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2019/2/17
 * Time: 11:33 AM
 */
namespace app\admin\controller;

use app\admin\model\Admin;
use think\App;

class Index extends Base
{
    public function index()
    {
        return $this->fetch();
    }

    public function home()
    {
        // 注册商户数
        $sellerNum = db('seller')->count();
        // 客服总数
        $KeFuNum = db('kefu')->count();
        // 累计服务人数
        $serviceNum = db('customer_service_log')->count();
        // 正在服务人数
        $nowServiceNum = db('now_service')->count();

        $this->assign([
            'seller_num' => $sellerNum,
            'kefu_num' => $KeFuNum,
            'service_num' => $serviceNum,
            'now_service_num' => $nowServiceNum,
            'tp_version' => App::VERSION
        ]);

        return $this->fetch();
    }

    // 修改密码
    public function editPwd()
    {
        if (request()->isPost()) {

            $param = input('post.');

            if ($param['new_password'] != $param['rep_password']) {
                return json(['code' => -1, 'data' => '', 'msg' => '两次密码输入不一致']);
            }

            // 检测旧密码
            $admin = new Admin();
            $sellerInfo = $admin->getAdminInfo(session('admin_user_id'));

            if(0 != $sellerInfo['code'] || empty($sellerInfo['data'])){
                return json(['code' => -2, 'data' => '', 'msg' => '管理员不存在']);
            }

            if(md5($param['password'] . config('service.salt')) != $sellerInfo['data']['admin_password']){
                return json(['code' => -3, 'data' => '', 'msg' => '旧密码错误']);
            }

            try {

                db('admin')->where('admin_id', session('admin_user_id'))->setField('admin_password',
                    md5($param['new_password'] . config('service.salt')));
            } catch (\Exception $e) {
                return json(['code' => -4, 'data' => '', 'msg' => $e->getMessage()]);
            }

            return json(['code' => 0, 'data' => '', 'msg' => '修改密码成功']);
        }

        return $this->fetch('pwd');
    }
}