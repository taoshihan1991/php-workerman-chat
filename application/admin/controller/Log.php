<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2019/9/29
 * Time: 11:29 PM
 */
namespace app\admin\controller;

use app\model\LoginLog;
use app\model\OperateLog;

class Log extends Base
{
    // 登录日志明细
    public function login()
    {
        if(request()->isAjax()) {

            $limit = input('param.limit');

            $log = new LoginLog();
            $list = $log->loginLogList($limit);

            if(0 == $list['code']) {

                return json(['code' => 0, 'msg' => 'ok', 'count' => $list['data']->total(), 'data' => $list['data']->all()]);
            }

            return json(['code' => 0, 'msg' => 'ok', 'count' => 0, 'data' => []]);
        }

        return $this->fetch();
    }

    // 操作日志明细
    public function operate()
    {
        if(request()->isAjax()) {

            $limit = input('param.limit');

            $log = new OperateLog();
            $list = $log->operateLogList($limit);

            if(0 == $list['code']) {

                return json(['code' => 0, 'msg' => 'ok', 'count' => $list['data']->total(), 'data' => $list['data']->all()]);
            }

            return json(['code' => 0, 'msg' => 'ok', 'count' => 0, 'data' => []]);
        }

        return $this->fetch();
    }
}

