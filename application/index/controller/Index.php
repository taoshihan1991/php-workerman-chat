<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2019/2/16
 * Time: 8:19 PM
 */
namespace app\index\controller;

use app\model\ComQuestion;
use app\model\Customer;
use app\model\CustomerInfo;
use app\model\Seller;
use app\model\Style;
use app\model\System;
use think\Controller;
use app\model\Chat;

class Index extends Controller
{
    // demo 演示首页
    public function index()
    {
        return $this->fetch();
    }

    // 价格
    public function price()
    {
        return $this->fetch();
    }
    public function ranslation()
    {
        return $this->fetch();
    }
    public function guanyu()
    {
        return $this->fetch();
    }
    public function chatbot()
    {
        return $this->fetch();
    }
    // 弹层方式显示聊天框
    public function chatBoxJs()
    {
        $sellerCode = input('param.u');

        $sellerModel = new Seller();
        $info = $sellerModel->getSellerInfo($sellerCode);

        if(0 != $info['code']) {
            return 'alert("部署有误！-- 商户不存在")';
        }

        if(empty($info['data'])) {
            return 'alert("部署有误！-- 商户信息为空")';
        }

        if (date("Y-m-d H:i:s") > $info['data']['valid_time']) {
            return 'alert("部署有误！-- 商户已过期")';
        }

        if (1 == config('service_socketio.default_box_link_flag')) {

            $domain = rtrim(request()->header()['referer'], '/');
            $domain = explode('/', $domain);
            $domain = $domain['0'] . '//' . $domain['2'];

            $safeDomain = explode(",", $info['data']['access_url']);
            if(!in_array($domain, $safeDomain)) {
                return 'alert("部署有误！ -- 接入域名不正确")';
            }
        }

        $styleModel = new Style();
        $myStyle = $styleModel->getSellerStyle($info['data']['seller_id']);
        if (empty($myStyle)) {
            $myStyle = $styleModel->initStyle($info['data']['seller_id']);
        }

        if (1 == $myStyle['style_type']) {
            $baseCss = getBaseCss(1) . 'right:' . $myStyle['box_margin'] . 'px;background:' . $myStyle['box_color'] . ';';
        } else if (2 == $myStyle['style_type']) {
            $baseCss = getBaseCss(2) . 'bottom:' . $myStyle['box_margin'] . 'px;background:' . $myStyle['box_color'] . ';';
        }

        $time = time();
        $this->assign([
            'domain' => config('service_socketio.domain'),
            'seller' => $sellerCode,
            'time' => $time,
            'token' => md5($sellerCode . config('service.salt') . $time),
            'style' => $myStyle,
            'baseCss' => $baseCss
        ]);

        if(request()->isMobile()) {
            return $this->fetch('mobile');
        }

        return $this->fetch();
    }

    // 客户端聊天窗口
    public function cliBox()
    {
        $sellerCode = input('param.u');
        $time = input('param.t');
        $token = input('param.tk');

        if(empty($sellerCode) || empty($time) || empty($token)) {
            return $this->fetch('error');
        }

        // token 签发日期大于2天了
        if(time() - $time > 86400 * 2) {
            return $this->fetch('error');
        }

        $safeToken = md5($sellerCode . config('service.salt') . $time);
        if($token != $safeToken) {
            return $this->fetch('error');
        }

        // 到期检测
        $sellerModel = new Seller();
        $sellerInfo = $sellerModel->getSellerInfo($sellerCode);
        if (0 != $sellerInfo['code'] || $sellerInfo['data']['valid_time'] < date('Y-m-d H:i:s')) {
            return $this->fetch('error');
        }

        $nowTime = time();
        $safeToken = md5($sellerCode . config('service.salt') . $nowTime);
        $token =  $sellerCode . '-' . $nowTime  . '-' . $safeToken;

        // 获取商户的配置
        $systemModel = new System();
        $systemInfo = $systemModel->getSellerConfig($sellerCode);

        $styleModel = new Style();
        $myStyle = $styleModel->getSellerStyle($sellerInfo['data']['seller_id']);
        if (empty($myStyle)) {
            $myStyle = $styleModel->initStyle($sellerInfo['data']['seller_id']);
        }

        $this->assign([
            'port' => config('service_socketio.socket_port'),
            'nowTime' => $nowTime,
            'token' => $safeToken,
            'seller' => $sellerCode,
            'seller_id' => $sellerInfo['data']['seller_id'],
            'os' => (request()->isMobile()) ? 'm' : 'p',
            'direct_kefu' => '',
            'type' => 1, // 弹层显示
            'socket' => config('service.protocol') . config('service.socket') . '/' . $token,
            'robot_open' => $systemInfo['data']['robot_open'],
            'pre_input' => $systemInfo['data']['pre_input'],
            'robot_hello' => config('robot.robot_hello_word'),
            'robot_title' => config('robot.robot_title'),
            'customerId' => '',
            'customerName' => '',
            'avatar' => '',
            'pre_see' => config('seller.open_pre_see'),
            'model' => config('seller.model'),
            'referer' => isset(request()->header()['referer']) ? request()->header()['referer'] : config('service.domain'),
            'agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
            'style' => $myStyle
        ]);

        return $this->fetch();
    }

    // 固定连接打开聊天窗口
    public function chat()
    {
        $sellerCode = input('param.u');
        $kefuCode = input('param.f', '');

        // 到期检测
        $sellerModel = new Seller();
        $sellerInfo = $sellerModel->getSellerInfo($sellerCode);

        if (0 != $sellerInfo['code'] || $sellerInfo['data']['valid_time'] < date('Y-m-d H:i:s')) {
            return $this->fetch('error');
        }

        $safeDomain = explode(",", $sellerInfo['data']['access_url']);
        // 校验接入域名
        if (1 == config('service_socketio.default_link_flag') && (!isset($_SERVER['HTTP_REFERER']) ||
                !in_array(rtrim($_SERVER['HTTP_REFERER'], '/'), $safeDomain))) {
            return $this->fetch('error');
        }

        $nowTime = time();
        $safeToken = md5($sellerCode . config('service.salt') . $nowTime);
        $token =  $sellerCode . '-' . $nowTime  . '-' . md5($sellerCode . config('service.salt') . $nowTime);

        $question = new ComQuestion();
        $comQInfo = $question->getSellerQuestion($sellerCode);

        // 获取商户的配置
        $systemModel = new System();
        $systemInfo = $systemModel->getSellerConfig($sellerCode);

        $styleModel = new Style();
        $myStyle = $styleModel->getSellerStyle($sellerInfo['data']['seller_id']);
        if (empty($myStyle)) {
            $myStyle = $styleModel->initStyle($sellerInfo['data']['seller_id']);
        }

        $this->assign([
            'port' => config('service_socketio.socket_port'),
            'nowTime' => $nowTime,
            'token' => $safeToken,
            'seller_id' => $sellerInfo['data']['seller_id'],
            'seller' => $sellerCode,
            'os' => (request()->isMobile()) ? 'm' : 'p',
            'direct_kefu' => $kefuCode, // 是否是电商模式
            'type' => 2, // 固定连接打开窗口
            'socket' => config('service.protocol') . config('service.socket') . '/' . $token,
            'question' => $comQInfo['data'],
            'robot_open' => $systemInfo['data']['robot_open'],
            'hello_word' => $systemInfo['data']['hello_word'],
            'pre_input' => $systemInfo['data']['pre_input'],
            'robot_hello' => config('robot.robot_hello_word'),
            'robot_title' => config('robot.robot_title'),
            'customerId' => '',
            'customerName' => '',
            'avatar' => '',
            'pre_see' => config('seller.open_pre_see'),
            'model' => config('seller.model'),
            'referer' => isset(request()->header()['referer']) ? request()->header()['referer'] : config('service.domain'),
            'agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
            'style' => $myStyle
        ]);

        return $this->fetch('cli_box');
    }

    // 固定连接打开聊天窗口 带参数
    public function kefu()
    {
        $sellerCode = input('param.u');
        $kefuCode = input('param.f', '');
        $customerId = input('param.uid');
        $customerName = input('param.name');
        $avatar = input('param.avatar');

        // 到期检测
        $sellerModel = new Seller();
        $sellerInfo = $sellerModel->getSellerInfo($sellerCode);

        if (0 != $sellerInfo['code'] || $sellerInfo['data']['valid_time'] < date('Y-m-d H:i:s')) {
            return $this->fetch('error');
        }

        // 校验接入域名
        if (1 == config('service_socketio.default_link_flag') && (!isset($_SERVER['HTTP_REFERER']) ||
                rtrim($_SERVER['HTTP_REFERER'], '/') != $sellerInfo['data']['access_url'])) {
            return $this->fetch('error');
        }

        $nowTime = time();
        $safeToken = md5($sellerCode . config('service.salt') . $nowTime);
        $token =  $sellerCode . '-' . $nowTime  . '-' . md5($sellerCode . config('service.salt') . $nowTime);

        $question = new ComQuestion();
        $comQInfo = $question->getSellerQuestion($sellerCode);

        // 获取商户的配置
        $systemModel = new System();
        $systemInfo = $systemModel->getSellerConfig($sellerCode);

        $styleModel = new Style();
        $myStyle = $styleModel->getSellerStyle($sellerInfo['data']['seller_id']);
        if (empty($myStyle)) {
            $myStyle = $styleModel->initStyle($sellerInfo['data']['seller_id']);
        }

        $this->assign([
            'port' => config('service_socketio.socket_port'),
            'nowTime' => $nowTime,
            'token' => $safeToken,
            'seller' => $sellerCode,
            'seller_id' => $sellerInfo['data']['seller_id'],
            'os' => (request()->isMobile()) ? 'm' : 'p',
            'direct_kefu' => $kefuCode, // 是否是电商模式
            'type' => 2, // 固定连接打开窗口
            'socket' => config('service.protocol') . config('service.socket') . '/' . $token,
            'question' => $comQInfo['data'],
            'robot_open' => $systemInfo['data']['robot_open'],
            'hello_word' => $systemInfo['data']['hello_word'],
            'pre_input' => $systemInfo['data']['pre_input'],
            'robot_hello' => config('robot.robot_hello_word'),
            'robot_title' => config('robot.robot_title'),
            'customerId' => $customerId,
            'customerName' => $customerName,
            'avatar' => $avatar,
            'pre_see' => config('seller.open_pre_see'),
            'model' => config('seller.model'),
            'referer' => isset(request()->header()['referer']) ? request()->header()['referer'] : config('service.domain'),
            'agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
            'style' => $myStyle
        ]);


        return $this->fetch('cli_box');
    }

    // 获取聊天信息
    public function getChatLog()
    {
        if(request()->isAjax()) {

            $param = input('param.');

            $log = new Chat();
            $list = $log->getCustomerChatLog($param);

            return json($list);
        }
    }

    // 访客留言
    public function leaveMsg()
    {
        if (request()->isPost()) {

            $param = input('post.');
            if(empty($param['username']) || empty($param['phone']) || empty($param['content'])){
                return json(['code' => -1, 'data' => '', 'msg' => '请全部填写']);
            }

            $param['add_time'] = time();

            try{
                db('leave_msg')->insert($param);
            }catch (\Exception $e){
                return json(['code' => -2, 'data' => '', 'msg' => '留言失败']);
            }

            return json(['code' => 0, 'data' => '', 'msg' => '留言成功']);
        }

        $sellerCode = input('param.s');
        $backUrl = input('param.referer');

        $this->assign([
            'seller_code' => $sellerCode,
            'back_url' => $backUrl
        ]);

        return $this->fetch('msg/index');
    }

    // 更新访客信息
    public function updateUserInfo()
    {
        if (request()->isAjax()) {

            $customerId = input('param.customer_id');
            $sellerCode = input('param.seller_code');
            $referrer = input('param.referrer');
            $agent = input('param.agent');

            $upData = [
                'customer_id' => $customerId,
                'seller_code' => $sellerCode,
                'from_url' => $referrer,
                'search_engines' => $this->analysisEngine($referrer),
                'user_agent' => $agent
            ];

            $infoModel = new CustomerInfo();
            $has = $infoModel->getCustomerInfoById($customerId, $sellerCode);
            if (0 == $has['code'] && !empty($has['data']) && empty($has['data']['real_name'])) {

                // 是否开启了自动备注
                $systemModel = new System();
                $systemInfo = $systemModel->getSellerConfig($sellerCode);
                if (!empty($systemInfo['data']) && 1 == $systemInfo['data']['auto_remark']) {

                    $customerModel = new Customer();
                    $customerInfo = $customerModel->getCustomerInfoById($customerId, $sellerCode);
                    if (0 == $customerInfo['code']) {
                        $upData['real_name'] = $customerInfo['data']['province'] . $customerInfo['data']['city']
                            . '#' . $customerInfo['data']['cid'];
                    }
                }
            }

            $res = $infoModel->updateCustomerInfo($upData);

            return json($res);
        }
    }

    // 给客服评价
    public function praise()
    {
        if (request()->isAjax()) {

            $param = input('post.');

            $param['kefu_code'] = ltrim($param['kefu_code'], "KF_");
            $param['add_time'] = date('Y-m-d H:i:s');

            try {

                db("praise")->insert($param);
            } catch (\Exception $e) {
                return json(['code' => -1, 'data' => '', 'msg' => '暂时无法评价']);
            }

            return json(['code' => 0, 'data' => '', 'msg' => '本次服务已结束，感谢您的评价']);
        }
    }

    /**
     * 分析来源
     * @param $referrer
     * @return string
     */
    protected function analysisEngine($referrer)
    {
        if (empty($referrer)) {
            return '直接访问';
        }

        return $referrer;
    }

    // 更新访客指定的内容
    public function updateCustomerData()
    {
        if (request()->isAjax()) {

            $customerId = input('param.customer_id');
            $sellerCode = input('param.seller_code');
            $phone = input('param.phone');

            $info = new CustomerInfo();

            $res = $info->updateCustomerInfo([
                'customer_id' => $customerId,
                'seller_code' => $sellerCode,
                'phone' => $phone
            ]);

            return json($res);
        }
    }
}
