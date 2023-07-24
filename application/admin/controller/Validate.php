<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2019/2/28
 * Time: 8:23 PM
 */
namespace app\admin\controller;

use app\admin\model\KeFu;
use app\admin\model\Seller as SellerModel;
use app\admin\model\System;
use app\admin\validate\SellerValidate;
use app\model\OperateLog;

class Validate extends Base
{
    // 商户配置管理
    public function index()
    {
        if(request()->isAjax()) {

            $limit = input('param.limit');
            $sellerName = input('param.seller_name');

            $where = [];
            if (!empty($sellerName)) {
                $where[] = ['seller_name', '=', $sellerName];
            }

            $seller = new SellerModel();
            $list = $seller->getSellersConfig($limit, $where);

            if(0 == $list['code']) {

                return json(['code' => 0, 'msg' => 'ok', 'count' => $list['data']->total(), 'data' => $list['data']->all()]);
            }

            return json(['code' => 0, 'msg' => 'ok', 'count' => 0, 'data' => []]);
        }

        return $this->fetch();
    }

    // 编辑商户
    public function editSeller()
    {
        if (request()->isPost()) {

            $param = input('post.');

            if (empty($param['max_group_num'])) {
                return json(['code' => -1, 'data' => '', 'msg' => '分组数量必须大于0']);
            }

            if (empty($param['max_kefu_num'])) {
                return json(['code' => -1, 'data' => '', 'msg' => '坐席数量必须大于0']);
            }

            $seller = new SellerModel();
            $res = $seller->editSeller($param);

            // 记录操作日志
            (new OperateLog())->writeOperateLog([
                'operator' => session('admin_user_name'),
                'operator_ip' => request()->ip(),
                'operate_method' => 'validate/editSeller',
                'operate_title' => '编辑商户坐席',
                'operate_desc' => '编辑' . $param['seller_name'] . ' 最大分组数为： ' . $param['max_group_num']
                    . ' , 最大客服数为： ' . $param['max_kefu_num'],
                'operate_time' => date('Y-m-d H:i:s')
            ]);

            return json($res);
        }

        $sellerId = input('param.seller_id');
        $seller = new SellerModel();

        $this->assign([
            'seller' => $seller->getSellerById($sellerId)['data']
        ]);

        return $this->fetch('edit');
    }
}