<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2020/7/5
 * Time: 10:07 PM
 */
namespace app\service\controller;

use app\model\KeFuCate;

class Cate extends Base
{
    public function addKeFuCate()
    {
        if (request()->isPost()) {

            $param = input('post.');

            if (empty($param)) {
                return json(['code' => -1, 'data' => '', 'msg' => '请输入分类名称']);
            }

            $cateModel = new KeFuCate();
            $has = $cateModel->getKeFuCateInfoByName($param['cate_name'], session('kf_seller_id'), session('kf_id'));
            if (0 != $has['code']) {
                return json($has);
            }

            if (!empty($has['data'])) {
                return json(['code' => -2, 'data' => '', 'msg' => '该分类已经存在']);
            }

            $res = $cateModel->addKeFuCate([
                'cate_name' => $param['cate_name'],
                'kefu_id' => session('kf_id'),
                'seller_id' => session('kf_seller_id'),
                'create_time' => date('Y-m-d H:i:s')
            ]);

            return json($res);
        }
    }

    public function editKeFuCate()
    {
        if (request()->isPost()) {

            $param = input('post.');

            if (empty($param['cate_name'])) {
                return json(['code' => -1, 'data' => '', 'msg' => '请输入分类名称']);
            }

            $cateModel = new KeFuCate();
            $has = $cateModel->getKeFuCateInfoByName($param['cate_name'], session('kf_seller_id'), session('kf_id'));
            if (0 != $has['code']) {
                return json($has);
            }

            if (!empty($has['data']) && $has['data']['cate_id'] != $param['cate_id']) {
                return json(['code' => -2, 'data' => '', 'msg' => '该分类已经存在']);
            }

            $res = $cateModel->editKeFuCate([
                'cate_name' => $param['cate_name']
            ], [
                'kefu_id' => session('kf_id'),
                'seller_id' => session('kf_seller_id'),
                'cate_id' => $param['cate_id']
            ]);

            return json($res);
        }
    }

    public function delKeFuCate()
    {
        if (request()->isPost()) {

            $cateId = input('post.cate_id');
            $cateModel = new KeFuCate();

            $res = $cateModel->delKeFuCate([
                'kefu_id' => session('kf_id'),
                'seller_id' => session('kf_seller_id'),
                'cate_id' => $cateId
            ]);

            return json($res);
        }
    }
}