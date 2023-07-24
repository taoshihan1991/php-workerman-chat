<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2020/5/17
 * Time: 9:22 PM
 */
namespace app\utils;

use app\model\BaseModel;
use app\model\BlackList;
use app\model\QuestionConf;
use app\model\ComQuestion;
use GatewayWorker\Lib\Gateway;

class Common extends BaseModel
{
    /**
     * 黑名单检测
     * @param $ip
     * @param $data
     * @param $callback
     * @return bool
     */
    public function checkBlackList($ip, $data, $callback)
    {
        $black = new BlackList($this->db);

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
     * @param $customer
     * @param $sysConfig
     * @param $dsInfo
     * @param $sessionId
     * @return bool
     */
    public function checkHelloWord($customer, $sysConfig, $dsInfo, $sessionId)
    {
        if(0 == $sysConfig['code'] && !empty($sysConfig['data'])
            && 1 == $sysConfig['data']['hello_status']) {
            try {

                if (0 == Gateway::isUidOnline($customer['customer_id'])) {
                    // 收到客服测接待失败,通知访客端，重新连接
                    Gateway::sendToClient($sessionId, json_encode([
                        'cmd' => 'userInit',
                        'data' => [
                            'code' => 400,
                            'data' => [],
                            'msg' => '请重新尝试分配客服'
                        ]
                    ]));

                    return false;
                }

                Gateway::sendToUid($customer['customer_id'], json_encode([
                    'cmd' => 'hello',
                    'data' => [
                        'avatar' => $dsInfo['data']['kefu_avatar'],
                        'time' => date('Y-m-d H:i:s'),
                        'content' => $sysConfig['data']['hello_word'],
                        'protocol' => 'ws'
                    ]
                ]));
            } catch (\Exception $e) {}

        }

        return true;
    }

    /**
     * 检测常见问题
     * @param $customer
     */
    public function checkCommonQuestion($customer)
    {
        $questionCof = new QuestionConf($this->db);
        $configInfo = $questionCof->getSellerQuestionConfig($customer['seller_code']);

        if (0 == $configInfo['code'] && !empty($configInfo['data']) && 1 == $configInfo['data']['status']) {

            // 查询要发送的常见问题
            $question = new ComQuestion($this->db);
            $comQInfo = $question->getSellerQuestion($customer['seller_code']);

            $content = '[p]' . $configInfo['data']['question_title'] . '[/p]';
            if (!empty($comQInfo['data'])) {

                foreach ($comQInfo['data'] as $vo) {
                    $content .= '[p style=cursor:pointer;color:#1E9FFF; onclick=autoAnswer(this) data-id='
                        . $vo['question_id'] . ']' . $vo['question'] . '[/p]';
                }

                if (Gateway::isUidOnline($customer['customer_id'])) {

                    try {

                        Gateway::sendToUid($customer['customer_id'], json_encode([
                            'cmd' => 'comQuestion',
                            'data' => [
                                'avatar' => '/static/common/images/robot.jpg',
                                'time' => date('Y-m-d H:i:s'),
                                'content' => $content
                            ]
                        ]));
                    } catch (\Exception $e) {}
                }
            }
        }
    }
}