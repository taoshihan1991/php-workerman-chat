<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Date: 2019/2/28
 * Time: 10:35
 */
namespace app\service\controller;

use app\model\BlackList;
use app\model\Chat;
use app\model\Customer;
use app\model\CustomerInfo;
use app\model\KeFu;
use app\model\KeFuCate;
use app\model\KeFuWord;
use app\model\Queue;
use app\model\Service as ServiceModel;
use app\model\System;
use app\seller\model\ServiceLog;

class Service extends Base
{
    // 获取待服务的访客列表
    public function getNowServiceList()
    {
        if(request()->isAjax()) {

            $service = new ServiceModel();
            $newService = $service->getServiceList(session('kf_user_id'));

            if(0 != $newService['code'] || empty($newService['data'])) {
                return json(['code' => 0, 'data' => [], 'msg' => 'empty list']);
            }

            $ids = [];
            foreach($newService['data'] as $key => $vo) {
                $ids[$vo['customer_id']] = $vo['service_log_id'];
            }

            $customer = new Customer();
            $list = $customer->getCustomerListByIds(array_keys($ids), session('kf_user_id'));

            // 查询该商户下,访客被标注的名称
            $infoMap = [];
            $info = new CustomerInfo();
            $customerInfo = $info->getCustomerNameByIds(array_keys($ids), session('kf_seller_code'));

            if (0 == $customerInfo['code'] && !empty($customerInfo['data'])) {
                foreach ($customerInfo['data'] as $key => $vo) {
                    $infoMap[$vo['customer_id']] = $vo['real_name'];
                }
            }

            if(0 == $list['code'] && !empty($list['data'])) {

                foreach($list['data'] as $key => $vo) {
                    $list['data'][$key]['log_id'] = isset($ids[$vo['customer_id']]) ? $ids[$vo['customer_id']] : -1;
                    $list['data'][$key]['real_name'] = isset($infoMap[$vo['customer_id']]) ? $infoMap[$vo['customer_id']] : '';
                }
            }

            return json($list);
        }
    }

    // 获取历史聊天列表
    public function getHistoryChatList()
    {
        if(request()->isAjax()) {

            $customerModel = new Customer();
            $list = $customerModel->getHistoryChatList(session('kf_user_id'), session('kf_seller_code'));

            $ids = [];
            foreach($list['data'] as $key => $vo) {
                $ids[] = $vo['customer_id'];
            }

            // 查询该商户下,访客被标注的名称
            $infoMap = [];
            $info = new CustomerInfo();
            $customerInfo = $info->getCustomerNameByIds($ids, session('kf_seller_code'));

            if (0 == $customerInfo['code'] && !empty($customerInfo['data'])) {
                foreach ($customerInfo['data'] as $key => $vo) {
                    $infoMap[$vo['customer_id']] = $vo['real_name'];
                }
            }

            if(0 == $list['code'] && !empty($list['data'])) {

                foreach($list['data'] as $key => $vo) {
                    $list['data'][$key]['real_name'] = isset($infoMap[$vo['customer_id']]) ? $infoMap[$vo['customer_id']] : '';
                }
            }

            return json($list);
        }
    }

    // ip 定位
    public function getCity()
    {
        if(request()->isAjax()) {
            $ip = input('param.ip');

            $address = getLocationByIp($ip);

            return json(['code' => 0, 'data' => $address, 'msg' => 'ok']);
        }
    }

    // 获取当前商户在线的未咨询的客户
    public function getCustomerQueue()
    {
        if(request()->isAjax()) {

            $queue = new Queue();
            $list = $queue->getCustomerList(session('kf_seller_code'));

            foreach ($list['data'] as $key => $vo) {
                $location = getLocationByIp($vo['customer_ip'], 2);
                $list['data'][$key]['province'] = $location['province'];
                $list['data'][$key]['city'] = $location['city'];
            }

            return json($list);
        }
    }

    // 获取聊天记录
    public function getChatLog()
    {
        if(request()->isAjax()){

            $param = input('param.');

            $log = new Chat();
            $list = $log->getChatLog($param);

            return json($list);
        }
    }

    // 转接
    public function reLink()
    {
        if(request()->isAjax()) {

            try {

                $groups = db('group')->where('group_status', 1)->where('seller_id', session('kf_seller_id'))->select();
                if(!empty($groups)) {

                    foreach($groups as $key => $vo) {
                        $groups[$key]['users'] = db('kefu')->alias('a')
                            ->field('a.kefu_code,a.kefu_name,a.kefu_avatar,a.group_id,a.max_service_num,count(b.service_id) as service_num')
                            ->leftJoin('v2_now_service b', 'a.kefu_code = b.kefu_code')
                            ->where('a.group_id', $vo['group_id'])->where('a.online_status', 1)
                            ->where('a.kefu_code', '<>', session('kf_user_id'))
                            ->group('a.kefu_id')
                            ->select();
                    }
                }

            } catch (\Exception $e) {

                return json(['code' => -1, 'data' => [], 'msg' => $e->getMessage()]);
            }

            return json(['code' => 0, 'data' => $groups, 'msg' => 'online info']);
        }
    }

    // 获取用户详情
    public function getCustomerInfo()
    {
        if (request()->isAjax()) {

            $customerId = input('param.customer_id');

            $info = new CustomerInfo();
            $detail = $info->getCustomerInfoById($customerId, session('kf_seller_code'));

            if (0 == $detail['code'] && !empty($detail['data']) && empty($detail['data']['real_name'])) {

                // 是否开启了自动备注
                $systemModel = new System();
                $systemInfo = $systemModel->getSellerConfig($detail['data']['seller_code']);
                if (!empty($systemInfo['data']) && 1 == $systemInfo['data']['auto_remark']) {

                    $customerModel = new Customer();
                    $customerInfo = $customerModel->getCustomerInfoById($customerId, session('kf_seller_code'));
                    if (0 == $customerInfo['code']) {
                        $upData['real_name'] = $customerInfo['data']['province'] . $customerInfo['data']['city']
                            . '#' . $customerInfo['data']['cid'];

                        $upData['customer_id'] = $customerId;
                        $upData['seller_code'] = session('kf_seller_code');
                        $info->updateCustomerInfo($upData);
                        $detail['data']['real_name'] = $upData['real_name'];
                    }
                }
            }

            if (0 == $detail['code']) {
                $deviceInfo = getDeviceInfo($detail['data']['user_agent']);
                $detail['data']['os'] = $deviceInfo['deviceOs'];
                $detail['data']['version'] = $deviceInfo['deviceVersion'];
            }

            return json($detail);
        }
    }

    // 更新访客信息
    public function updateCustomerInfo()
    {
        if (request()->isAjax()) {

            $param = input('post.');
            unset($param['u']);

            if (empty($param['real_name'])) {
                unset($param['real_name']);
            }

            if (empty($param['email'])) {
                unset($param['email']);
            }

            if (empty($param['phone'])) {
                unset($param['phone']);
            }

            if (empty($param['remark'])) {
                unset($param['remark']);
            }

            if (empty($param)) {
                return json(['code' => 0, 'data' => '', 'msg' => 'save nothing']);
            }

            $param['seller_code'] = session('kf_seller_code');

            $info = new CustomerInfo();
            $res = $info->updateCustomerInfo($param);

            return json($res);
        }
    }

    // 将访客加入商户黑名单
    public function joinBlackList()
    {
        if (request()->isAjax()) {

            $param = input('post.');
            unset($param['u']);

            $param['oper_kefu_id'] = session('kf_id');
            $param['seller_code'] = session('kf_seller_code');

            $black = new BlackList();
            $res = $black->updateBlackList($param);

            return json($res);
        }
    }

    // 移动端客服统计
    public function census()
    {
        if (request()->isAjax()) {

            $serviceModel = new ServiceLog();
            $nowServiceModel = new ServiceModel();
            // 客服累计服务人数
            $totalNum = $serviceModel->getKeFuTotalServiceNum(session('kf_user_id'))['data'];
            // 客服当前服务人数
            $nowNum = $nowServiceModel->getNowServiceNum(session('kf_user_id'))['data'];
            // 好评率
            $totalPraise = db('praise')->where('kefu_code', session('kf_user_id'))->count();
            $goodPraise = db('praise')->where('kefu_code', session('kf_user_id'))->where('star', '>', 3)->count();

            if (0 == $totalPraise) {
                $goodPercent = 0;
            } else {
                $goodPercent = round($goodPraise / $totalPraise * 100, 2);
            }

            return json(['code' => 0, 'data' => [
                'totalNum' => $totalNum,
                'nowNum' => $nowNum,
                'goodPercent' => $goodPercent
            ], 'msg' => 'success']);
        }
    }

    // 获取我的自定义常用语
    public function getMyWord()
    {
        if (request()->isAjax()) {

            $kefuCateModel = new KeFuCate();
            $res = $kefuCateModel->getKeFuWord(session('kf_seller_id'), session('kf_id'));

            return json($res);
        }
    }

    // 设置客服状态
    public function setStatus()
    {
        if (request()->isAjax()) {

            $event = input('post.status');
            if ($event == 'busy') {
                $status = 2;
            } else {
                $status = 1;
            }

            $kefuModel = new KeFu();
            $kefuModel->updateKeFuInfo(['kefu_id' => session('kf_id')], ['online_status' => $status]);

            return json(['code' => 0, 'data' => '', 'msg' => '设置成功']);
        }
    }
}