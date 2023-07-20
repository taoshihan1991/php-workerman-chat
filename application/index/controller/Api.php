<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2019/3/28
 * Time: 8:33 PM
 */
namespace app\index\controller;

use app\model\Chat;
use think\Controller;
use app\model\Group;
use app\model\KeFu;
use app\model\Seller;
use app\model\Service;

class Api extends Controller
{
    // 获取空闲客服
    public function getFreeKeFu()
    {
        $sellerCode = input('param.code');

        $sellerModel = new Seller();
        $info = $sellerModel->getSellerInfo($sellerCode);

        if(0 != $info['code'] || empty($info['data'])) {
            return json(['code' => -1, 'data' => '', 'msg' => '商户不存在']);
        }

        if(0 == $info['data']['seller_status']) {
            return json(['code' => -2, 'data' => '', 'msg' => '商户被禁用']);
        }

        $groupModel = new Group();
        $groupInfo = $groupModel->getFirstServiceGroup($info['data']['seller_id']);
        if(0 != $groupInfo['code'] || empty($groupInfo['data'])) {
            return json(['code' => -3, 'data' => '', 'msg' => '该商户下没配置前置服务组']);
        }

        $kefu = new KeFu();
        $kefuInfo = $kefu->getOnlineKeFuByGroup($groupInfo['data']['group_id']);
        if(0 != $kefuInfo['code']) {
            return json(['code' => -4, 'data' => '', 'msg' => '查询分组客服失败']);
        }

        if(empty($kefuInfo['data'])) {
            return json(['code' => -5, 'data' => '', 'msg' => '暂无客服上班']);
        }

        $serviceKefu = [];
        $service = new Service();
        foreach($kefuInfo['data'] as $key => $vo) {

            $num = $service->getNowServiceNum($vo['kefu_code']);
            if(0 != $num['code']) {
                return json(['code' => -6, 'data' => '', 'msg' => '获取当前服务数据失败']);
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
            return json(['code' => -7, 'data' => '', 'msg' => '客服全忙']);
        }
        unset($returnKefu['free_degree']);

        $returnKefu['kefu_code'] = 'KF_' . $returnKefu['kefu_code'];
        $returnKefu['seller_code'] = $sellerCode;

        return json(['code' => 200, 'data' => $returnKefu, 'msg' => 'ok']);
    }

    // 客服发消息给 接口端
    public function send2Customer()
    {
        if (request()->isPost()) {

            $param = input('post.');

            $this->curlPost(config('service_socketio.api_url'), $param);

            // 记录聊天日志
            $chatLog = new Chat();
            $chatLogId = $chatLog->addChatLog([
                'from_id' => $param['data']['from_id'],
                'from_name' => $param['data']['from_name'],
                'from_avatar' => $param['data']['from_avatar'],
                'to_id' => $param['data']['to_id'],
                'to_name' => $param['data']['to_name'],
                'seller_code' => $param['data']['seller_code'],
                'content' => $param['data']['content'],
                'create_time' => date('Y-m-d H:i:s'),
                'read_flag' => 2 // 已读
            ]);

            return json(['code' => 0, 'data' => $chatLogId, 'msg' => 'ok']);
        }
    }

    /**
     * 处理转接
     * @return \think\response\Json
     */
    public function doRelink()
    {
        if (request()->isPost()) {

            $param = input('post.');

            $port = config('service_socketio.http_port');
            $relinkInfo = $this->curlPost('http://127.0.0.1:' . $port, $param);
            $relinkInfo = json_decode($relinkInfo, true);

            // 通知转接车成功
            $this->curlPost(config('service_socketio.api_url'), $relinkInfo['data']);

            return json(['code' => 0, 'data' => '', 'msg' => '转接成功']);
        }
    }

    /**
     * 处理主动关闭访客
     * @return \think\response\Json
     */
    public function closeUser()
    {
        if (request()->isPost()) {

            $param = input('post.');

            $port = config('service_socketio.http_port');
            $returnInfo = $this->curlPost('http://127.0.0.1:' . $port, $param);
            $returnInfo = json_decode($returnInfo, true);

            // 通知关闭消息
            $this->curlPost(config('service_socketio.api_url'), $returnInfo['data']);

            return json(['code' => 0, 'data' => '', 'msg' => '关闭成功']);
        }
    }

    /**
     * 获取商户下的指定分组客服
     * @return \think\response\Json
     */
    public function getSellerKeFuByGroup()
    {
        $groupId = input('param.group_id');
        $seller = input('param.seller_code');

        $keFuModel = new KeFu();
        $list = $keFuModel->getSellerKeFuByGroup($seller, $groupId);

        return json(['code' => 0, 'data' => $list['data'], 'msg' => 'success']);
    }

    /**
     * curl post
     * @param $url
     * @param $param
     * @return mixed
     */
    private function curlPost($url, $param)
    {
        $ch = curl_init();
        curl_setopt ($ch, CURLOPT_URL, $url);
        curl_setopt ($ch, CURLOPT_POST, 1);
        curl_setopt ($ch, CURLOPT_HEADER, 0);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_POSTFIELDS, http_build_query($param));
        $return = curl_exec($ch);
        curl_close ($ch);

        return $return;
    }
}