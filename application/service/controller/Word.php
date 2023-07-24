<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2020/7/6
 * Time: 10:42 PM
 */
namespace app\service\controller;

use app\model\KeFuWord;

class Word extends Base
{
    public function addKeFuWord()
    {
        if (request()->isPost()) {

            $param = input('post.');

            $param['kefu_id'] = session('kf_id');
            $param['create_time'] = date('Y-m-d H:i:s');

            $wordModel = new KeFuWord();
            $res = $wordModel->addKeFuWord($param);

            return json($res);
        }

        $this->assign([
            'cate_id' => input('param.cate_id'),
            'u' => session('kf_seller_code')
        ]);

        return $this->fetch('add');
    }

    public function delKeFuWord()
    {
        if (request()->isPost()) {

            $wordId = input('post.word_id');

            $wordModel = new KeFuWord();
            $res = $wordModel->delKeFuWord([
                'word_id' => $wordId,
                'kefu_id' => session('kf_id')
            ]);

            return json($res);
        }
    }
}