<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2019/6/2
 * Time: 10:11 AM
 */
namespace app\seller\controller;

use app\model\ComQuestion;
use app\model\QuestionConf;

class Question extends Base
{
    // 设置首页
    public function index()
    {
        // 基础设置
        $conf = new QuestionConf();
        $configInfo = $conf->getSellerQuestionConfig(session('seller_code'));

        // 常见问题
        $question = new ComQuestion();
        $questionInfo = $question->getSellerQuestion(session('seller_code'));

        $this->assign([
            'conf' => $configInfo['data'],
            'question' => $questionInfo['data']
        ]);

        return $this->fetch();
    }

    // 配置常见问题
    public function editConf()
    {
        if (request()->isPost()) {

            $param = input('post.');

            isset($param['status']) ? $param['status']= 1 : $param['status'] = 0;

            $config = new QuestionConf();
            $res = $config->editSellerQuestionConfig($param);

            return json($res);
        }
    }

    // 添加常见问题
    public function add()
    {
        if (request()->isPost()) {

            $param = input('post.');

            $question = new ComQuestion();
            $res = $question->addSellerQuestion($param);

            return json($res);
        }

        return $this->fetch();
    }

    // 删除常见问题
    public function del()
    {
        if (request()->isAjax()) {

            $id = input('param.id');

            $question = new ComQuestion();
            $res = $question->delSellerQuestion($id);

            return json($res);
        }
    }
}