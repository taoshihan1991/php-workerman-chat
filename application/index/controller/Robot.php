<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2019/11/28
 * Time: 9:41 PM
 */
namespace app\index\controller;

use app\model\Chat;
use think\Controller;
use think\facade\Cache;
use tool\Elasticsearch;

class Robot extends Controller
{
    public function service()
    {
        if (request()->isAjax()) {

            // 限制同一个ip访问频率 3s 钟一次
            $ip = request()->ip();
            $forbid = Cache::get($ip . '_limit');

            if (!empty($forbid)) {
                return json(['code' => -1, 'data' => '', 'msg' => config('robot.forbid_word')]);
            }

            $param = input('post.');

            try {

                $elasticSearchModel = new Elasticsearch();
                $like = $elasticSearchModel->search('search_' . $param['seller_id'], [
                    'question' => $param['q']
                ]);

                // 频率限制
                Cache::set($ip . '_limit', 1, config('robot.request_limit_time'));
                $chatLog = new Chat();

                $chatLog->addChatLog([
                    'from_id' => $param['from_id'],
                    'from_name' => $param['from_name'],
                    'from_avatar' => $param['from_avatar'],
                    'to_id' => '0',
                    'to_name' => '自动问答机器人',
                    'seller_code' => $param['seller_code'],
                    'content' => $param['q'],
                    'create_time' => date('Y-m-d H:i:s'),
                    'read_flag' => 2
                ]);


                if (empty($like)) {

                    $defaultAnswer = config('robot.default_answer');
                    $offset = mt_rand(0, count($defaultAnswer) - 1);

                    if (config('robot.save_unknown_question')) {
                        db('unknown_question')->insert([
                            'seller_id' => $param['seller_id'],
                            'question' => $param['q'],
                            'customer_name' => $param['from_name'],
                            'create_time' => date('Y-m-d H:i:s')
                        ]);
                    }

                    $chatLog->addChatLog([
                        'from_id' => '0',
                        'from_name' => '自动问答机器人',
                        'from_avatar' => '/static/common/images/robot.jpg',
                        'to_id' => $param['from_id'],
                        'to_name' => $param['from_name'],
                        'seller_code' => $param['seller_code'],
                        'content' => $defaultAnswer[$offset],
                        'create_time' => date('Y-m-d H:i:s'),
                        'read_flag' => 2
                    ]);

                    return json(['code' => -3, 'data' => '', 'msg' => $defaultAnswer[$offset]]);
                }

                $like = $like[0];
                $content = '[p]' . config('robot.show_like_title') . '[/p]';
                foreach ($like as $vo) {
                    $content .= '[p style=cursor:pointer;color:#1E9FFF; onclick=robotAutoAnswer(this) data-id='
                        . $vo['_id'] . ']' . $vo['_source']['question'] . '[/p]';
                }

                // 记录机器问答日志
                $chatLog->addChatLog([
                    'from_id' => '0',
                    'from_name' => '自动问答机器人',
                    'from_avatar' => '/static/common/images/robot.jpg',
                    'to_id' => $param['from_id'],
                    'to_name' => $param['from_name'],
                    'seller_code' => $param['seller_code'],
                    'content' => $content,
                    'create_time' => date('Y-m-d H:i:s'),
                    'read_flag' => 2
                ]);

            } catch (\Exception $e) {
                return json(['code' => -2, 'data' => $e->getMessage(), 'msg' => '哦，系统出错了']);
            }

            return json(['code' => 0, 'data' => '', 'msg' => $content]);
        }
    }

    public function autoAnswer()
    {
        if (request()->isAjax()) {

            // 限制同一个ip访问频率 3s 钟一次
            $ip = request()->ip();
            $forbid = Cache::get($ip . '_answer_limit');

            if (!empty($forbid)) {
                return json(['code' => -1, 'data' => '', 'msg' => config('robot.forbid_word')]);
            }

            $param = input('post.');

            try {

                $content = db('knowledge_store')->where('knowledge_id', $param['id'])
                    ->where('seller_id', $param['sid'])->find();

                // 记录机器问答日志
                $chatLog = new Chat();
                $chatLog->addChatLog([
                    'from_id' => $param['from_id'],
                    'from_name' => $param['from_name'],
                    'from_avatar' => $param['from_avatar'],
                    'to_id' => '0',
                    'to_name' => '自动问答机器人',
                    'seller_code' => $param['seller_code'],
                    'content' => $content['question'],
                    'create_time' => date('Y-m-d H:i:s'),
                    'read_flag' => 2
                ]);

                $chatLog->addChatLog([
                    'from_id' => '0',
                    'from_name' => '自动问答机器人',
                    'from_avatar' => '/static/common/images/robot.jpg',
                    'to_id' => $param['from_id'],
                    'to_name' => $param['from_name'],
                    'seller_code' => $param['seller_code'],
                    'content' => $content['answer'],
                    'create_time' => date('Y-m-d H:i:s'),
                    'read_flag' => 2
                ]);

            } catch (\Exception $e) {
                return json(['code' => -2, 'data' => $e->getMessage(), 'msg' => '哦，系统出错了']);
            }

            return json(['code' => 0, 'data' => '', 'msg' => $content['answer']]);
        }
    }
}