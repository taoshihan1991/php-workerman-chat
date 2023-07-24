<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2019/2/28
 * Time: 8:23 PM
 */
namespace app\seller\controller;

use app\seller\model\ServiceLog;

class Customer extends Base
{
    public function index()
    {
        $keFuModel = new \app\seller\model\KeFu();
        $keFu = $keFuModel->getSellerKeFu();

        $keFuArr = [];
        foreach ($keFu['data'] as $vo) {
            $keFuArr[$vo['kefu_code']] = $vo['kefu_name'];
        }

        if(request()->isAjax()) {

            $limit = input('param.limit');
            $kefuCode = input('param.kefu_code');
            $startTime = input('param.start_time');
            $where = [];

            if (!empty($kefuCode)) {
                $where[] = ['kefu_code', '=', $kefuCode];
            }

            if (!empty($startTime)) {
                $dateTime = explode(" - ", $startTime);
                $where[] = ['start_time', 'between', [$dateTime['0'], $dateTime['1'] . ' 23:59:59']];
            }

            $log = new ServiceLog();
            $list = $log->getServiceList($where, $limit, $keFuArr);

            if (0 != $list['code']) {
                return json(['code' => -1, 'msg' => 'ok', 'count' => 0, 'data' => []]);
            }

            return json(['code' => 0, 'msg' => 'ok', 'count' => $list['data']->total(), 'data' => $list['data']->all()]);
        }

        $this->assign([
            'kefu' => $keFuArr
        ]);

        return $this->fetch();
    }

    public function area()
    {
        $customerModel = new \app\seller\model\Customer();
        $res = $customerModel->getCustomerAreaInfo()['data'];

        $json = [];
        foreach ($res as $vo) {
            $json[] = $vo;
        }

        $this->assign([
            'area' => $res,
            'areaJson' => json_encode($json)
        ]);

        return $this->fetch();
    }

    public function showNum()
    {
        if (request()->isAjax()) {

            $kefuCode = input('param.kefu_code');
            $startTime = input('param.start_time');
            $where = [];

            if (!empty($kefuCode)) {
                $where[] = ['kefu_code', '=', $kefuCode];
            }

            if (!empty($startTime)) {
                $dateTime = explode(" - ", $startTime);
                $where[] = ['start_time', 'between', [$dateTime['0'], $dateTime['1'] . ' 23:59:59']];
            }

            $keFuModel = new \app\seller\model\KeFu();
            $keFu = $keFuModel->getSellerKeFu();

            $keFuArr = [];
            $serviceData = [];
            foreach ($keFu['data'] as $vo) {
                $keFuArr[$vo['kefu_code']] = $vo['kefu_name'];
                $serviceData[$vo['kefu_code']] = [
                    'kefu_name' => $vo['kefu_name'],
                    't_total' => 0
                ];

                if (!empty($kefuCode)) {
                    if ($kefuCode != $vo['kefu_code']) {
                        unset($keFuArr[$vo['kefu_code']], $serviceData[$vo['kefu_code']]);
                    }
                }
            }

            $detail = db('customer_service_log')->field('kefu_code,count(*) as t_total')
                ->where('seller_code', session('seller_code'))->where($where)->group('kefu_code')->select();

            foreach ($detail as $vo) {
                if (isset($serviceData[$vo['kefu_code']])) {
                    $serviceData[$vo['kefu_code']]['t_total'] = $vo['t_total'];
                }
            }

            return json(['code' => 0, 'data' => $serviceData, 'msg' => '成功']);
        }
    }
}