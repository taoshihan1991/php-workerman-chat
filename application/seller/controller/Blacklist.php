<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2019/2/28
 * Time: 8:23 PM
 */
namespace app\seller\controller;

use app\seller\model\BlackList as BlackListModel;

class Blacklist extends Base
{
    // 分组列表
    public function index()
    {
        if(request()->isAjax()) {

            $limit = input('param.limit');
            $ip = input('param.ip');

            $listModel = new BlackListModel();
            $list = $listModel->getBlackList($limit, $ip);

            if(0 == $list['code']) {

                return json(['code' => 0, 'msg' => 'ok', 'count' => $list['data']->total(), 'data' => $list['data']->all()]);
            }

            return json(['code' => 0, 'msg' => 'ok', 'count' => 0, 'data' => []]);
        }

        return $this->fetch();
    }

    // 删除分组
    public function delBlacklist()
    {
        if(request()->isAjax()) {

            $listId = input('param.list_id');
            $listModel = new BlackListModel();

            $res = $listModel->delBlackList($listId);

            return json($res);
        }
    }
}