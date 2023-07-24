<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2019/10/19
 * Time: 4:51 PM
 */
namespace app\socketio\service;

use app\model\BlackList;
use app\model\QuestionConf;
use app\model\ComQuestion;

class Common
{
    /**
     * 黑名单检测
     * @param $ip
     * @param $data
     * @param $callback
     * @return bool
     */
    public static function checkBlackList($ip, $data, $callback)
    {
        $black = new BlackList();

        $isIn = $black->checkBlackList($ip, $data['seller']);
        if (0 == $isIn['code']) {

            if (is_callable($callback)) {
                $callbackData = [
                    'code' => 201,
                    'data' => [],
                    'msg' => '黑名单用户'
                ];

                $callback(json_encode($callbackData));
                return false;
            }

            return true;
        }

        return true;
    }

    /**
     * 检测hello word 的发送
     * @param $onlineCustomer
     * @param $customer
     * @param $sysConfig
     * @param $callback
     * @param $dsInfo
     * @return bool
     */
    public static function checkHelloWord($onlineCustomer, $customer, $sysConfig, $callback, $dsInfo)
    {
        $preHelloWord = cache($customer['seller_code'] . '-' . $customer['customer_id'] . 'hello_word');
        if(0 == $sysConfig['code'] && !empty($sysConfig['data'])
            && 1 == $sysConfig['data']['hello_status'] && empty($preHelloWord)) {
            try {

                if (empty($onlineCustomer[$customer['customer_id']])) {
                    if (is_callable($callback)) {
                        // 收到客服测接待失败,通知访客端，重新连接
                        $callbackData = [
                            'code' => 400,
                            'data' => [],
                            'msg' => '请重新尝试分配客服'
                        ];

                        $callback(json_encode($callbackData));

                        return false;
                    }
                }

                $onlineCustomer[$customer['customer_id']]->emit("hello", [
                    'data' => [
                        'avatar' => $dsInfo['data']['kefu_avatar'],
                        'time' => date('Y-m-d H:i:s'),
                        'content' => $sysConfig['data']['hello_word'],
                        'protocol' => 'ws'
                    ]
                ], function ($res) {});
            } catch (\Exception $e) {}

            // 20分钟 之内不重复发送欢迎语
            cache($customer['seller_code'] . '-' . $customer['customer_id'] . 'hello_word', 1, 1200);
        }

        return true;
    }

    /**
     * 检测常见问题
     * @param $customer
     * @param $onlineCustomer
     */
    public static function checkCommonQuestion($customer, $onlineCustomer)
    {
        $preComQuestion = cache($customer['seller_code'] . '-' . $customer['customer_id'] . 'common_question');
        if (empty($preComQuestion)) {

            $questionCof = new QuestionConf();
            $configInfo = $questionCof->getSellerQuestionConfig($customer['seller_code']);
            if (0 == $configInfo['code'] && !empty($configInfo['data']) && 1 == $configInfo['data']['status']) {

                // 查询要发送的常见问题
                $question = new ComQuestion();
                $comQInfo = $question->getSellerQuestion($customer['seller_code']);

                $content = '[p]' . $configInfo['data']['question_title'] . '[/p]';
                if (!empty($comQInfo['data'])) {

                    foreach ($comQInfo['data'] as $vo) {
                        $content .= '[p style=cursor:pointer;color:#1E9FFF; onclick=autoAnswer(this) data-id='
                            . $vo['question_id'] . ']' . $vo['question'] . '[/p]';
                    }

                    if (isset($onlineCustomer[$customer['customer_id']])) {

                        try {

                            $onlineCustomer[$customer['customer_id']]->emit("comQuestion", [
                                'data' => [
                                    'avatar' => '/static/common/images/robot.jpg',
                                    'time' => date('Y-m-d H:i:s'),
                                    'content' => $content
                                ]
                            ], function ($res) {});
                        } catch (\Exception $e) {}
                    }

                    // 10分钟 之内不重复发送常见问题
                    cache($customer['seller_code'] . '-' . $customer['customer_id'] . 'common_question', 1, 60 * 10);
                }
            }
        }
    }
}