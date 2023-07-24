<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2020/5/17
 * Time: 9:25 PM
 */
namespace app\utils;

use app\model\BaseModel;
use app\model\Customer;
use app\model\Group;
use app\model\KeFu;
use app\model\Seller;
use app\model\Service;

class Distribution extends BaseModel
{
    /**
     * 用户上线后触发分配客服
     * @param $param
     * @return array
     */
    public function customerDistribution($param)
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
     * 优先寻找该访客在该商户下，上次为其服务的客服，再次给他服务【该客服要在 前置服务组 】
     * @param $param
     * @return array
     */
    private function findPreKeFuService($param)
    {
        // 获取该访客的所属商户信息
        $seller = new Seller($this->db);
        $info = $seller->getSellerInfo($param['seller_code']);

        if(0 != $info['code'] || empty($info['data'])) {
            return ['code' => -2, 'data' => '', 'msg' => '商户不存在'];
        }

        if(0 == $info['data']['seller_status']) {
            return ['code' => -4, 'data' => '', 'msg' => '商户被禁用'];
        }

        $customerModel = new Customer($this->db);
        $preKefu = $customerModel->getCustomerInfoById($param['customer_id'], $param['seller_code']);

        if(0 != $preKefu['code'] || empty($preKefu['data'])) {
            return ['code' => 0, 'data' => $info, 'msg' => 'next step'];
        }

        $kefu = new KeFu($this->db);
        $kefuInfo = $kefu->getKeFuInfoByCode($preKefu['data']['pre_kefu_code']);

        if(0 != $kefuInfo['code'] || empty($kefuInfo['data'])) {
            return ['code' => 0, 'data' => $info, 'msg' => 'next step'];
        }

        $groupModel = new Group($this->db);
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

        $service = new Service($this->db);
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
    private function findFreeKeFu($info)
    {
        $groupModel = new Group($this->db);
        $groupInfo = $groupModel->getFirstServiceGroup($info['data']['seller_id']);
        if(0 != $groupInfo['code'] || empty($groupInfo['data'])) {
            return ['code' => -5, 'data' => '', 'msg' => '该商户下没配置前置服务组'];
        }

        $kefu = new KeFu($this->db);
        $kefuInfo = $kefu->getOnlineKeFuByGroup($groupInfo['data']['group_id']);
        if(0 != $kefuInfo['code']) {
            return ['code' => -6, 'data' => '', 'msg' => '查询分组客服失败'];
        }

        if(empty($kefuInfo['data'])) {
            return ['code' => 201, 'data' => '', 'msg' => '暂无客服上班'];
        }

        // TODO 此处执行策略 -- v1.1暂时在此处定死一种策略，后面做动态切换
        $distributionObj = Factory::getObject("circle");
        $distributionObj->setDb($this->db);
        $res = $distributionObj->doDistribute($kefuInfo['data']);

        if (0 != $res['code']) {
            return $res;
        }

        return ['code' => 200, 'data' => $res['data'], 'msg' => 'ok'];
    }
}