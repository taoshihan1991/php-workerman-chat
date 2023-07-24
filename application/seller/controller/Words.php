<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2019/3/2
 * Time: 7:28 PM
 */
namespace app\seller\controller;

use app\seller\model\Word;

class Words extends Base
{
    // 常用语列表
    public function index()
    {
        if(request()->isAjax()) {

            $limit = input('param.limit');
            $where = [];

            $word = new Word();
            $list = $word->getWordList($limit, $where);

            if(0 == $list['code']) {

                return json(['code' => 0, 'msg' => 'ok', 'count' => $list['data']->total(), 'data' => $list['data']->all()]);
            }

            return json(['code' => 0, 'msg' => 'ok', 'count' => 0, 'data' => []]);
        }

        return $this->fetch();
    }

    // 添加常用语
    public function addWord()
    {
        if(request()->isPost()) {

            $param = input('post.');

            if(!isset($param['word']) || empty($param['word'])) {
                return json(['code' => -1, 'data' => '', 'msg' => '请输入常用语']);
            }

            $param['seller_code'] = session('seller_code');

            $word = new Word();
            $res = $word->addWord($param);

            return json($res);
        }

        $cateModel = new \app\seller\model\Cate();
        $this->assign([
            'cate' => $cateModel->getSellerCate()['data']
        ]);

        return $this->fetch('add');
    }

    // 编辑常用语
    public function editWord()
    {
        $word = new Word();

        if(request()->isPost()) {

            $param = input('post.');

            if(!isset($param['word']) || empty($param['word'])) {
                return json(['code' => -1, 'data' => '', 'msg' => '请输入常用语']);
            }

            $res = $word->editWord($param);

            return json($res);
        }

        $cateModel = new \app\seller\model\Cate();
        $this->assign([
            'cate' => $cateModel->getSellerCate()['data'],
            'word' => $word->getWordInfoById(input('param.word_id'))['data']
        ]);

        return $this->fetch('edit');
    }

    // 删除常用语
    public function delWord()
    {
        if(request()->isAjax()) {

            $wordId = input('param.word_id');
            $word = new Word();

            $res = $word->delWord($wordId);

            return json($res);
        }
    }

    // 导入文件
    public function import()
    {
        if (request()->isPost()) {

            $param = input('post.');
            if (empty($param['words'])) {
                return json(['code' => -1, 'data' => '', 'msg' => '请上传文件']);
            }

            $words = file_get_contents(env('ROOT_PATH') . $param['words']);
            $wordModel = new Word();

            $res = $wordModel->batchAddWord($param['cate_id'], $words);

            return json($res);
        }

        $cateModel = new \app\seller\model\Cate();
        $this->assign([
            'cate' => $cateModel->getSellerCate()['data']
        ]);

        return $this->fetch();
    }

    // 上传文件
    public function uploadFile()
    {
        $file = request()->file('file');

        $fileInfo = $file->getInfo();

        // 检测图片格式
        $ext = explode('.', $fileInfo['name']);
        $ext = array_pop($ext);

        $extArr = explode('|', 'txt');
        if(!in_array($ext, $extArr)){
            return json(['code' => -3, 'data' => '', 'msg' => '只能上传txt格式的文件']);
        }

        // 移动到框架应用根目录/public/uploads/ 目录下
        $info = $file->move('./uploads');
        if($info){
            $src =  'public/uploads' . '/' . date('Ymd') . '/' . $info->getFilename();
            return json(['code' => 0, 'data' => ['src' => $src, 'name' => $fileInfo['name'] ], 'msg' => '']);
        }else{
            // 上传失败获取错误信息
            return json(['code' => -1, 'data' => '', 'msg' => $file->getError()]);
        }
    }
}