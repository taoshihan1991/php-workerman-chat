<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2019/2/20
 * Time: 9:57 PM
 */
namespace app\index\controller;

use app\model\Customer;
use app\model\Service;
use app\websocket\service\Distribution;

class Test
{
    // 访客连线分配测试
    public function testOnlineTask()
    {
        $customer = [
            'customer_id' => '52nemknbx6c000',
            'customer_name' => '访客52nemknbx6c000',
            'customer_avatar' => '/static/common/images/customer.png',
            'customer_ip' => '127.0.0.1',
            'seller_code' => '5c6cbcb7d55ca',
            'client_id' => '7f00000107d000000013',
            'create_time' => date('Y-m-d H:i:s'),
            'online_status' => 1
        ];

        $res = Distribution::userOnlineTask($customer);

        echo "<pre>";
        print_r($res);
    }

    public function serviceList()
    {
        $service = new Service();
        $res = $service->getServiceList('5c6ce9f6d753b');

        $ids = [];
        foreach($res['data'] as $key => $vo) {
            $ids[] = $vo['customer_id'];
        }

        $customer = new Customer();
        $res = $customer->getCustomerListByIds($ids, '5c6ce9f6d753b');

        echo "<pre>";
        print_r($res);
    }

    // api 获取空闲客服demo
    public function apiService()
    {
        $code= input('param.code');

        $kefuInfo = file_get_contents(request()->domain() . '/index/api/getFreeKeFu?code=' . $code);
        $kefuInfo = json_decode($kefuInfo, true);

        return json($kefuInfo);
    }

    // api 访客接入demo
    public function apiCustomerLink()
    {
        // apiService 得到的客服信息
        $kefuInfo = [
            'data' => [
                'kefu_code' => 'KF_5c6ce9f6d753b',
                'kefu_name' => '客服小白',
                'seller_code' => '5c6cbcb7d55ca'
            ]
        ];

        $content = [
            'uid' => 20190321,
            "name" => "访客20190321",
            "avatar" => "//tva2.sinaimg.cn/crop.0.0.512.512.180/005LMAegjw8f2bp9qg4mrj30e80e8dg5.jpg",
            "ip" => "58.240.254.162"
        ];

        $push_api_url = "http://127.0.0.1:2945";
        $post_data = [
            "cmd" => "link",
            "data" => $content,
            "kefu_code" => $kefuInfo['data']['kefu_code'],
            "kefu_name" => $kefuInfo['data']['kefu_name'],
            "seller_code" => $kefuInfo['data']['seller_code']
        ];

        $ch = curl_init ();
        curl_setopt ( $ch, CURLOPT_URL, $push_api_url );
        curl_setopt ( $ch, CURLOPT_POST, 1 );
        curl_setopt ( $ch, CURLOPT_HEADER, 0 );
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt ( $ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
        $return = curl_exec ( $ch );
        curl_close ( $ch );

        echo $return;
    }

    // api 聊天demo
    public function apiChat()
    {
        // apiService 得到的客服信息
        $kefuInfo = [
            'data' => [
                'kefu_code' => 'KF_5c6ce9f6d753b',
                'kefu_name' => '客服小白',
                'seller_code' => '5c6cbcb7d55ca'
            ]
        ];

        $push_api_url = "http://127.0.0.1:2945";
        $post_data = [
            "cmd" => "c2sChat",
            "data" => [
                'from_name' => "访客20190321",
                'from_avatar' => "//tva2.sinaimg.cn/crop.0.0.512.512.180/005LMAegjw8f2bp9qg4mrj30e80e8dg5.jpg",
                'from_id' => 20190321,
                'content' => 'api聊天测试',
                'to_id' => $kefuInfo['data']['kefu_code'],
                'to_name' => $kefuInfo['data']['kefu_name'],
                'seller_code' => $kefuInfo['data']['seller_code']
            ]
        ];

        $ch = curl_init ();
        curl_setopt ( $ch, CURLOPT_URL, $push_api_url );
        curl_setopt ( $ch, CURLOPT_POST, 1 );
        curl_setopt ( $ch, CURLOPT_HEADER, 0 );
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt ( $ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
        $return = curl_exec ( $ch );
        curl_close ( $ch );

        echo $return;
    }

    // 接受客服发来的消息
    public function receive()
    {
        $param = input('post.');

        file_put_contents('./log.log', var_export($param, true));
        /**
         * 消息体如下 -- 聊天消息
         * [
         *      'cmd' => 's2cChat',
         *      'data' => [
         *           'from_name' => "客服小白",
         *           'from_avatar' => "//tva2.sinaimg.cn/crop.0.0.512.512.180/005LMAegjw8f2bp9qg4mrj30e80e8dg5.jpg",
         *           'from_id' => KF_WERWERSDF,
         *           'content' => '客服返回消息',
         *           'to_id' => 20190321,
         *           'to_name' => '访客20190321',
         *           'seller_code' => '5c6cbcb7d55ca'
         *      ]
         * ]
         */

        /**
         * 消息体如下  -- 转接消息
         * [
         *      'cmd' => 'relink',
         *      'data' => [
         *          'kefu_code' => 'KF_qeqw',
         *          'kefu_name' => '客服小白',
         *          'msg' => '您已被转接'
         *      ]
         * ]
         */

        /**
         * 消息体如下  -- 被关闭消息
         * [
         *      'cmd' => 'isClose',
         *       'data' => [
         *          'msg' => '客服下班了,稍后再来吧。'
         *       ]
         * ]
         */


    }
}