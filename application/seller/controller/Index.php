<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Date: 2019/3/1
 * Time: 10:04
 */
namespace app\seller\controller;

use app\model\Queue;
use app\model\Seller;
use app\seller\model\Msg;
use app\seller\model\ServiceLog;
use app\seller\model\KeFu as KeFuModel;

class Index extends Base
{
    public function index()
    {
        // 获取未读留言消息
        $noRead = (new Msg())->getNoReadMsgCount()['data'];

        $this->assign([
            'no_read' => $noRead
        ]);

        return $this->fetch();
    }

    public function home()
    {
        // 累计接待量
        $log = new ServiceLog();
        $totalNum = $log->getTotalServiceNum()['data'];

        // 今日接待量
        $todayNum = $log->getTodayServiceNum()['data'];

        // 在线客服
        $keFu = new KeFuModel();
        $onlineKeFu = $keFu->getOnlineKeFu()['data'];

        // 今日在线访客数
        $customer = new Queue();
        $onlineCustomerNum = $customer->getOnlineCustomer(session('seller_code'))['data'];

        // 商户信息
        $seller = new Seller();
        $sellerInfo = $seller->getSellerInfo(session('seller_code'))['data'];

        // 15天接待统计
        $days15 = [];
        for ($i = 15; $i > 0; $i--) {
            $days15[] = date('Y-m-d', strtotime('-' . $i . ' days'));
        }

        $start = $days15[0];
        $end = $days15[14] . ' 23:59:59';

        $fifteenNum = $this->census($start, $end, $days15);

        $this->assign([
            'total_num' => number_format($totalNum),
            'today_num' => number_format($todayNum),
            'online_kefu' => number_format(count($onlineKeFu)),
            'kefu' => $onlineKeFu,
            'customer_num' => $onlineCustomerNum,
            'fifteenDays' => json_encode($days15),
            'fifteenNum' => json_encode(array_values($fifteenNum)),
            'seller' => $sellerInfo
        ]);

        return $this->fetch();
    }

    // 如何接入
    public function howToUse()
    {
        $kefuModel = new \app\seller\model\KeFu();
        $kefuList = $kefuModel->getSellerKeFu()['data'];

        $this->assign([
            'domain' => config('service_socketio.domain'),
            'seller_code' => session('seller_code'),
            'kefu' => $kefuList
        ]);

        return $this->fetch('doc');
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
            $seller = new Seller();
            $sellerInfo = $seller->getSellerInfo(session('seller_code'));

            if(0 != $sellerInfo['code'] || empty($sellerInfo['data'])){
                return json(['code' => -2, 'data' => '', 'msg' => '商户不存在']);
            }

            if(md5($param['password'] . config('service.salt')) != $sellerInfo['data']['seller_password']){
                return json(['code' => -3, 'data' => '', 'msg' => '旧密码错误']);
            }

            try {

                db('seller')->where('seller_id', session('seller_user_id'))->setField('seller_password',
                    md5($param['new_password'] . config('service.salt')));
            } catch (\Exception $e) {
                return json(['code' => -4, 'data' => '', 'msg' => $e->getMessage()]);
            }

            return json(['code' => 0, 'data' => '', 'msg' => '修改密码成功']);
        }

        return $this->fetch('pwd');
    }

    private function census($start, $end, $days)
    {
        $sql = "SELECT DATE_FORMAT(start_time, '%Y-%m-%d') as create_time2,count(service_log_id) as s_num from v2_customer_service_log WHERE start_time > '"
            . $start . "' and start_time < '" . $end . "' and seller_code = '" . session('seller_code') . "' GROUP BY create_time2;";

        $all = db('v2_customer_service_log')->query($sql);

        $num = [];
        foreach ($days as $vo) {
            $num[$vo] = 0;
        }

        foreach ($all as $key => $vo) {
            if (isset($num[$vo['create_time2']])) {
                $num[$vo['create_time2']] = $vo['s_num'];
            }
        }

        return $num;
    }
}