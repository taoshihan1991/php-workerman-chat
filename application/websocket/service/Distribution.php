<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2019/2/17
 * Time: 10:01 PM
 */
namespace app\websocket\service;

use app\model\Customer;
use app\model\Group;
use app\model\KeFu;
use app\model\Seller;
use app\model\Service;

class Distribution
{
    /**
     * 用户上线后触发分配客服
     * @param $param
     * @return array
     */
    public static function customerDistribution($param)
    {
        if(empty($param)) {
            return ['code' => -1, 'data' => '', 'msg' => '参数缺失'];
        }

        // step one 优先找上次服务的用户给访客服务
        $preInfo = self::findPreKeFuService($param);
        if(0 != $preInfo['code']) {
            return $preInfo;
        }

        // step two 寻找该商户下前置业务组下是否有在线的空闲客服
        return self::findFreeKeFu($preInfo['data']);
    }

    /**
     * 首先查找尚未服务结束的，进入服务【可能是因为客服或者访客关闭页面，并没有到评价 和 客服主动关闭 进行的推出】
     * @param $param
     * @return array
     */
    private static function findNoEndService($param)
    {
        $service = new Service();
        $kefuCode = $service->findNowServiceKefu($param['customer_id'], $param['client_id']);

        $kefu = new KeFu();
        if(0 != $kefuCode['code'] || empty($kefuCode['data'])) {
            return ['code' => 0, 'data' => '', 'msg' => 'next step'];
        }

        $kefuInfo = $kefu->getKeFuInfoByCode($kefuCode['data']['kefu_code']);
        if(0 != $kefuInfo['code'] || empty($kefuInfo['data'])) {
            return ['code' => -8, 'data' => '', 'msg' => '获取客服信息失败'];
        }

        // 是同一个商户才处理
        if($kefuInfo['data']['seller_code'] != $param['seller_code']) {
            return ['code' => 0, 'data' => '', 'msg' => 'next step'];
        }

        $num = $service->getNowServiceNum($kefuInfo['data']['kefu_code']);
        if(0 != $num['code']) {
            return ['code' => -7, 'data' => '', 'msg' => '获取当前服务数据失败'];
        }

        $freeNumber = $kefuInfo['data']['max_service_num'] - $num['data'];
        if($freeNumber < 0) {
            return ['code' => 202, 'data' => '', 'msg' => '客服全忙'];
        }

        return ['code' => 200, 'data' => [
            'kefu_code' => 'KF_' . $kefuInfo['data']['kefu_code'],
            'kefu_name' => $kefuInfo['data']['kefu_name'],
            'kefu_avatar' => $kefuInfo['data']['kefu_avatar']
        ], 'msg' => '原来的客服继续服务'];
    }

    /**
     * 优先寻找该访客在该商户下，上次为其服务的客服，再次给他服务【该客服要在 前置服务组 】
     * @param $param
     * @return array
     */
    private static function findPreKeFuService($param)
    {
        // 获取该访客的所属商户信息
        $seller = new Seller();
        $info = $seller->getSellerInfo($param['seller_code']);

        if(0 != $info['code'] || empty($info['data'])) {
            return ['code' => -2, 'data' => '', 'msg' => '商户不存在'];
        }

        if(0 == $info['data']['seller_status']) {
            return ['code' => -4, 'data' => '', 'msg' => '商户被禁用'];
        }

        $customerModel = new Customer();
        $preKefu = $customerModel->getCustomerInfoById($param['customer_id'], $param['seller_code']);

        if(0 != $preKefu['code'] || empty($preKefu['data'])) {
            return ['code' => 0, 'data' => $info, 'msg' => 'next step'];
        }

        $kefu = new KeFu();
        $kefuInfo = $kefu->getKeFuInfoByCode($preKefu['data']['pre_kefu_code']);

        if(0 != $kefuInfo['code'] || empty($kefuInfo['data'])) {
            return ['code' => 0, 'data' => $info, 'msg' => 'next step'];
        }

        $groupModel = new Group();
        $groupInfo = $groupModel->getGroupInfoById($kefuInfo['data']['group_id']);
        if(0 != $groupInfo['code'] || empty($groupInfo['data'])) {
            return ['code' => -10, 'data' => '', 'msg' => '获取分组信息异常'];
        }

        // 上次服务的客服，并不在前置服务分组中，重新分配
        if(1 != $groupInfo['data']['first_service']) {
            return ['code' => 0, 'data' => $info, 'msg' => 'next step'];
        }

        // 上次服务的客服不在线，重新分配
        if(1 != $kefuInfo['data']['online_status']) {
            return ['code' => 0, 'data' => $info, 'msg' => 'next step'];
        }

        $service = new Service();
        $num = $service->getNowServiceNum($kefuInfo['data']['kefu_code']);
        if(0 != $num['code']) {
            return ['code' => 0, 'data' => $info, 'msg' => 'next step'];
        }

        // 上次服务的客服，现在忙，重新分配
        $freeNumber = $kefuInfo['data']['max_service_num'] - $num['data'];
        if($freeNumber <= 0) {
            return ['code' => 0, 'data' => $info, 'msg' => 'next step'];
        }

        return ['code' => 200, 'data' => [
            'kefu_code' => 'KF_' . $kefuInfo['data']['kefu_code'],
            'kefu_name' => $kefuInfo['data']['kefu_name'],
            'kefu_avatar' => $kefuInfo['data']['kefu_avatar']
        ], 'msg' => '上次的客服继续服务'];
    }

    /**
     * 寻找该商户下空闲的客服去服务
     * @param $info
     * @return array
     */
    private static function findFreeKeFu($info)
    {
        $groupModel = new Group();
        $groupInfo = $groupModel->getFirstServiceGroup($info['data']['seller_id']);
        if(0 != $groupInfo['code'] || empty($groupInfo['data'])) {
            return ['code' => -5, 'data' => '', 'msg' => '该商户下没配置前置服务组'];
        }

        $kefu = new KeFu();
        $kefuInfo = $kefu->getOnlineKeFuByGroup($groupInfo['data']['group_id']);
        if(0 != $kefuInfo['code']) {
            return ['code' => -6, 'data' => '', 'msg' => '查询分组客服失败'];
        }

        if(empty($kefuInfo['data'])) {
            return ['code' => 201, 'data' => '', 'msg' => '暂无客服上班'];
        }

        $serviceKefu = [];
        $service = new Service();
        foreach($kefuInfo['data'] as $key => $vo) {

            $num = $service->getNowServiceNum($vo['kefu_code']);
            if(0 != $num['code']) {
                return ['code' => -7, 'data' => '', 'msg' => '获取当前服务数据失败'];
                break;
            }

            $serviceKefu[$key] = [
                'kefu_code' => $vo['kefu_code'],
                'kefu_name' => $vo['kefu_name'],
                'kefu_avatar' => $vo['kefu_avatar'],
                'free_degree' => round(($vo['max_service_num'] - $num['data']) / $vo['max_service_num'], 2) // 空闲度 0.xx
            ];
        }

        // 寻找最空闲的客服
        $returnKefu = [];
        if(!empty($serviceKefu)) {

            $returnKefu = $serviceKefu[0];
            foreach($serviceKefu as $key => $vo) {

                if(0 == $vo['free_degree']) {
                    continue;
                }

                if($vo['free_degree'] > $returnKefu['free_degree']) {
                    $returnKefu = $vo;
                }
            }
        }

        if($returnKefu['free_degree'] <= 0) {
            return ['code' => 202, 'data' => '', 'msg' => '客服全忙'];
        }
        unset($returnKefu['free_degree']);

        $returnKefu['kefu_code'] = 'KF_' . $returnKefu['kefu_code'];

        return ['code' => 200, 'data' => $returnKefu, 'msg' => 'ok'];
    }
}