<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2019/3/17
 * Time: 3:45 PM
 */
namespace app\seller\controller;

use app\model\Chat;
use app\seller\model\Log as LogModel;
use app\seller\model\Msg;
use app\seller\model\ServiceLog;

class Log extends Base
{
    // 聊天日志
    public function index()
    {
        $keFuModel = new \app\seller\model\KeFu();
        $allKeFu = $keFuModel->getSellerKeFu();

        $this->assign([
            'all_kf' => $allKeFu['data']
        ]);

        return $this->fetch();
    }

    // 获取客服接待的访客
    public function getTakeCaredCustomer()
    {
        if (request()->isAjax()) {

            $param = input('post.');

            $where = [];
            if (-7 == $param['talk_date']) {
                $where[] = ['l.start_time', '>', date("Y-m-d", strtotime('-7days'))];
            } else if (-30 == $param['talk_date']) {
                $where[] = ['l.start_time', '>', date("Y-m-d", strtotime('-30days'))];
            } else {
                $where[] = ['l.start_time', 'between', explode(' - ', $param['talk_date'])];
            }

            $serviceLogModel = new ServiceLog();
            $info = $serviceLogModel->getKeFuServiceCustomer($param['kefu_code'], $where);

            return json($info);
        }
    }

    // 获取聊天详情
    public function getChatLogDetail()
    {
        if(request()->isAjax()) {

            $param = input('post.');

            $log = new Chat();
            $list = $log->getSellerChatLogBackend($param);

            return json($list);
        }
    }

    // 访客留言
    public function leave()
    {
        if(request()->isAjax()) {

            $limit = input('param.limit');

            $msgModel = new Msg();
            $list = $msgModel->getLeaveMsgList($limit);

            if(0 == $list['code']) {

                return json(['code' => 0, 'msg' => 'ok', 'count' => $list['data']->total(), 'data' => $list['data']->all()]);
            }

            return json(['code' => 0, 'msg' => 'ok', 'count' => 0, 'data' => []]);
        }

        // 获取未读留言消息
        $noRead = (new Msg())->getNoReadMsgCount()['data'];

        $this->assign([
            'no_read' => $noRead
        ]);

        return $this->fetch();
    }

    // 标记已读
    public function readMsg()
    {
        if(request()->isAjax()) {

            $id = input('param.id');

            $msgModel = new Msg();
            $res = $msgModel->updateMsgStatus($id);

            return json($res);
        }
    }

    // 全部标记已读
    public function readAll()
    {
        if(request()->isAjax()) {

            $msgModel = new Msg();
            $res = $msgModel->updateMsgStatusBatch();

            return json($res);
        }
    }

    // 清理聊天记录
    public function clean()
    {
        if (request()->isPost()) {

            $param = input('post.');
            $date = explode(' - ', $param['cleanDate']);

            try {

                db('chat_log')
                    ->where('create_time', '>' , $date['0'])
                    ->where('create_time', '<' , $date['1'])
                    ->where('seller_code', session('seller_code'))->delete();
            } catch (\Exception $e) {
                return json(['code' => -1, 'data' => $e->getMessage(), 'msg' => '清理失败']);
            }

            return json(['code' => 0, 'data' => '', 'msg' => '清理成功']);
        }
    }
}