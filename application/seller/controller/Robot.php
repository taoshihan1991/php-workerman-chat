<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2019/2/28
 * Time: 8:23 PM
 */
namespace app\seller\controller;

use app\model\Seller;
use app\seller\model\Knowledge;
use app\seller\model\UnKnown;
use app\seller\validate\GroupValidate;
use app\seller\validate\KnowledgeValidate;
use tool\Elasticsearch;

class Robot extends Base
{
    // 机器人知识库列表
    public function index()
    {
        if(request()->isAjax()) {

            $limit = input('param.limit');
            $question = input('param.question');

            $where = [];
            if (!empty($question)) {
                $where[] = ['question', 'like', '%' . $question . '%'];
            }

            $knowledge = new Knowledge();
            $list = $knowledge->getKnowledgeList($limit, $where);

            if(0 == $list['code']) {

                return json(['code' => 0, 'msg' => 'ok', 'count' => $list['data']->total(), 'data' => $list['data']->all()]);
            }

            return json(['code' => 0, 'msg' => 'ok', 'count' => 0, 'data' => []]);
        }

        return $this->fetch();
    }

    // 添加
    public function add()
    {
        if(request()->isPost()) {

            $param = input('post.');

            $validate = new KnowledgeValidate();
            if(!$validate->check($param)) {
                return ['code' => -3, 'data' => '', 'msg' => $validate->getError()];
            }

            isset($param['status']) ? $param['status']= 1 : $param['status'] = 0;

            $param['seller_id'] = session('seller_user_id');
            $param['cate_id'] = 1;
            $param['useful_num'] = 0;
            $param['useless_num'] = 0;
            $param['create_time'] = date('Y-m-d H:i:s');

            $knowledge = new Knowledge();
            $res = $knowledge->addKnowledge($param);
            if (0 != $res['code']) {
                return json($res);
            }

            // 创建es索引 和 文档
            $sellerModel = new Seller();
            $isMakeIndex = $sellerModel->getSellerInfo(session('seller_code'))['data'];
            if (empty($isMakeIndex)) {
                return json(['code' => -1, 'data' => '', 'msg' => '索引文档失败']);
            }

            $elasticSearchModel = new Elasticsearch();
            if (1 == $isMakeIndex['create_index_flag']) {

                try {

                    $elasticSearchModel->createESIndex('search_' . session('seller_user_id'), [
                        'question' => [
                            'type' => 'text',
                            'analyzer' => 'ik_max_word'
                        ]
                    ]);

                } catch (\Exception $e) {
                    return json(['code' => -2, 'data' => $e->getMessage(), 'msg' => '创建索引失败']);
                }

                $sellerModel->updateSellerInfo(session('seller_user_id'), [
                    'create_index_flag' => 2
                ]);
            }

            try {

                $elasticSearchModel->createDocument('search_' . session('seller_user_id'), $res['data'], [
                    'question' => $param['question'],
                ]);
            } catch (\Exception $e) {
                return json(['code' => -3, 'data' => $e->getMessage(), 'msg' => '索引文档失败']);
            }

            return json($res);
        }

        return $this->fetch();
    }

    // 编辑问答库
    public function edit()
    {
        if(request()->isPost()) {

            $param = input('post.');

            $validate = new KnowledgeValidate();
            if(!$validate->check($param)) {
                return ['code' => -2, 'data' => '', 'msg' => $validate->getError()];
            }

            isset($param['status']) ? $param['status']= 1 : $param['status'] = 0;

            $param['update_time'] = date('Y-m-d H:i:s');

            $knowledge = new Knowledge();
            $res = $knowledge->editKnowledge($param);

            $elasticSearchModel = new Elasticsearch();

            try {

                $elasticSearchModel->updateDocument('search_' . session('seller_user_id'), $param['knowledge_id'], [
                    'question' => $param['question'],
                ]);
            } catch (\Exception $e) {
                return json(['code' => -1, 'data' => $e->getMessage(), 'msg' => '编辑索引失败']);
            }

            return json($res);
        }

        $knowledgeId = input('param.id');
        $knowledge = new Knowledge();

        $this->assign([
            'info' => $knowledge->getKnowledgeInfoByCateId($knowledgeId)['data']
        ]);

        return $this->fetch();
    }

    // 删除问答库
    public function del()
    {
        if(request()->isAjax()) {

            $knowledgeId = input('param.id');
            $knowledge = new Knowledge();

            $res = $knowledge->delKnowledge($knowledgeId);

            $elasticSearchModel = new Elasticsearch();

            try {

                $elasticSearchModel->deleteDocument('search_' . session('seller_user_id'), $knowledgeId);
            } catch (\Exception $e) {
                return json(['code' => -1, 'data' => $e->getMessage(), 'msg' => '删除索引失败']);
            }

            return json($res);
        }
    }

    // 未知问题列表
    public function question()
    {
        if(request()->isAjax()) {

            $limit = input('param.limit');

            $where = [];
            $question = new UnKnown();
            $list = $question->getQuestionList($limit, $where);

            if(0 == $list['code']) {

                return json(['code' => 0, 'msg' => 'ok', 'count' => $list['data']->total(), 'data' => $list['data']->all()]);
            }

            return json(['code' => 0, 'msg' => 'ok', 'count' => 0, 'data' => []]);
        }

        return $this->fetch();
    }

    // 删除未知问题
    public function delQuestion()
    {
        if(request()->isAjax()) {

            $questionId = input('param.id');

            $question = new UnKnown();
            $res = $question->delQuestion($questionId);

            return json($res);
        }
    }
}