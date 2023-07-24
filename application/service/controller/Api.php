<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2019/11/9
 * Time: 2:23 PM
 */
namespace app\service\controller;

use app\model\BlackList;
use app\model\Chat;
use app\model\Customer;
use app\model\CustomerInfo;
use app\model\Queue;
use app\model\Service as ServiceModel;
use think\Controller;
use app\seller\model\ServiceLog;

class Api extends Controller
{
    // 获取待服务的访客列表
    public function getNowServiceList()
    {
        $keFuCode = ltrim(input('param.kefu_code'), 'KF_');
        $sellerCode = input('param.seller_code');

        $cacheToken = cache($sellerCode . '-KF_' . $keFuCode);
        if ($cacheToken != input('param.token')) {
            return json(['code' => 403, 'data' => '', 'msg' => '登录过期']);
        }

        $service = new ServiceModel();
        $newService = $service->getServiceList($keFuCode);

        if(0 != $newService['code'] || empty($newService['data'])) {
            return json(['code' => 0, 'data' => [], 'msg' => 'empty list']);
        }

        $ids = [];
        foreach($newService['data'] as $key => $vo) {
            $ids[$vo['customer_id']] = $vo['service_log_id'];
        }

        $customer = new Customer();
        $list = $customer->getCustomerListByIds(array_keys($ids), $keFuCode);

        // 查询该商户下,访客被标注的名称
        $infoMap = [];
        $info = new CustomerInfo();
        $customerInfo = $info->getCustomerNameByIds(array_keys($ids), $sellerCode);

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

    // ip 定位
    public function getCity()
    {
        $ip = input('param.ip');

        $ip2region = new \Ip2Region();
        $info = $ip2region->btreeSearch($ip);

        $info = explode('|', $info['region']);

        $address = '';
        foreach($info as $vo) {
            if('0' !== $vo) {
                $address .= $vo . '-';
            }
        }

        return json(['code' => 0, 'data' => rtrim($address, '-'), 'msg' => 'ok']);
    }

    // 获取当前商户在线的未咨询的客户
    public function getCustomerQueue()
    {
        $sellerCode = input('param.seller_code');
        $keFuCode = input('param.kefu_code');

        $cacheToken = cache($sellerCode . '-' . $keFuCode);
        if ($cacheToken != input('param.token')) {
            return json(['code' => 403, 'data' => '', 'msg' => '登录过期']);
        }

        $queue = new Queue();
        $list = $queue->getCustomerList($sellerCode);

        return json($list);
    }

    // 获取聊天记录
    public function getChatLog()
    {
        $param = input('param.');

        $cacheToken = cache($param['seller_code'] . '-' . $param['kefu_code']);
        if ($cacheToken != input('param.token')) {
            return json(['code' => 403, 'data' => '', 'msg' => '登录过期']);
        }

        $log = new Chat();
        $list = $log->getChatLogByClint($param);

        return json($list);
    }

    // 转接
    public function reLink()
    {
        $keFuCode = input('param.kefu_code');
        $sellerId = input('param.seller_id');

        try {

            $groups = db('group')->where('group_status', 1)->where('seller_id', $sellerId)->select();
            if(!empty($groups)) {

                foreach($groups as $key => $vo) {
                    $groups[$key]['users'] = db('kefu')->alias('a')
                        ->field('a.kefu_code,a.kefu_name,a.kefu_avatar,a.group_id,a.max_service_num,count(b.service_id) as service_num')
                        ->leftJoin('v2_now_service b', 'a.kefu_code = b.kefu_code')
                        ->where('a.group_id', $vo['group_id'])->where('a.online_status', 1)
                        ->where('a.kefu_code', '<>', ltrim($keFuCode, 'KF_'))
                        ->group('a.group_id')
                        ->select();
                }
            }

        } catch (\Exception $e) {

            return json(['code' => -1, 'data' => [], 'msg' => $e->getMessage()]);
        }

        return json(['code' => 0, 'data' => $groups, 'msg' => 'online info']);
    }

    // 获取用户详情
    public function getCustomerInfo()
    {
        $customerId = input('param.customer_id');
        $sellerCode = input('param.seller_code');

        $info = new CustomerInfo();
        $detail = $info->getCustomerInfoById($customerId, $sellerCode);

        return json($detail);
    }

    // 更新访客信息
    public function updateCustomerInfo()
    {
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

        $info = new CustomerInfo();
        $res = $info->updateCustomerInfo($param);

        return json($res);
    }

    // 将访客加入商户黑名单
    public function joinBlackList()
    {
        $param = input('post.');
        unset($param['u']);

        $black = new BlackList();
        $res = $black->updateBlackList($param);

        return json($res);
    }

    // 获取历史聊天列表
    public function getHistoryChatList()
    {
        $sellerCode = input('param.seller_code');
        $keFuCode = input('param.kefu_code');

        $cacheToken = cache($sellerCode . '-' . $keFuCode);
        if ($cacheToken != input('param.token')) {
            return json(['code' => 403, 'data' => '', 'msg' => '登录过期']);
        }

        $customerModel = new Customer();
        $list = $customerModel->getHistoryChatList(ltrim($keFuCode, 'KF_'), $sellerCode);

        $ids = [];
        foreach($list['data'] as $key => $vo) {
            $ids[] = $vo['customer_id'];
        }

        // 查询该商户下,访客被标注的名称
        $infoMap = [];
        $info = new CustomerInfo();
        $customerInfo = $info->getCustomerNameByIds($ids, $sellerCode);

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

    // 移动端客服统计
    public function census()
    {
        $sellerCode = input('param.seller_code');
        $keFuCode = input('param.kefu_code');

        $cacheToken = cache($sellerCode . '-' . $keFuCode);
        if ($cacheToken != input('param.token')) {
            return json(['code' => 403, 'data' => '', 'msg' => '登录过期']);
        }

        $keFuCode = ltrim($keFuCode, 'KF_');
        $serviceModel = new ServiceLog();
        $nowServiceModel = new ServiceModel();
        // 客服累计服务人数
        $totalNum = $serviceModel->getKeFuTotalServiceNum($keFuCode)['data'];
        // 客服当前服务人数
        $nowNum = $nowServiceModel->getNowServiceNum($keFuCode)['data'];
        // 好评率
        $totalPraise = db('praise')->where('kefu_code', $keFuCode)->count();
        $goodPraise = db('praise')->where('kefu_code', $keFuCode)->where('star', '>', 3)->count();

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

    //上传图片
    public function uploadImg()
    {
        $file = request()->file('file');

        $fileInfo = $file->getInfo();

        // 检测图片格式
        $ext = explode('.', $fileInfo['name']);
        $ext = array_pop($ext);

        $extArr = explode('|', 'jpg|png|gif|jpeg');
        if(!in_array($ext, $extArr)){
            return json(['code' => -3, 'data' => '', 'msg' => '只能上传jpg|png|gif|jpeg的文件']);
        }

        // 移动到框架应用根目录/public/uploads/ 目录下
        $info = $file->move('./uploads');
        if($info){
            $src =  '/uploads' . '/' . date('Ymd') . '/' . $info->getFilename();
            return json(['code' => 0, 'data' => ['src' => $src ], 'msg' => '']);
        }else{
            // 上传失败获取错误信息
            return json(['code' => -1, 'data' => '', 'msg' => $file->getError()]);
        }
    }

    //上传文件
    public function uploadFile()
    {
        $file = request()->file('file');

        $fileInfo = $file->getInfo();

        // 检测图片格式
        $ext = explode('.', $fileInfo['name']);
        $ext = array_pop($ext);

        $extArr = explode('|', 'zip|rar|txt|doc|docx|xls|xlsx');
        if(!in_array($ext, $extArr)){
            return json(['code' => -3, 'data' => '', 'msg' => '只能上传zip|rar|txt|doc|docx|xls|xlsx的文件']);
        }

        // 移动到框架应用根目录/public/uploads/ 目录下
        $info = $file->move('./uploads');
        if($info){
            $src =  '/uploads' . '/' . date('Ymd') . '/' . $info->getFilename();
            return json(['code' => 0, 'data' => ['src' => $src, 'name' => $fileInfo['name'] ], 'msg' => '']);
        }else{
            // 上传失败获取错误信息
            return json(['code' => -1, 'data' => '', 'msg' => $file->getError()]);
        }
    }
}