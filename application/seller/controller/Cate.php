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

class Cate extends Base
{
    // 常用语列表
    public function index()
    {
        if(request()->isAjax()) {

            $limit = input('param.limit');
            $cateName = input('param.cate_name');
            $where = [];

            if (!empty($cateName)) {
                $where[] = ['cate_name', '=', $cateName];
            }

            $cateModel = new \app\seller\model\Cate();
            $list = $cateModel->getCateList($limit, $where);

            if(0 == $list['code']) {

                return json(['code' => 0, 'msg' => 'ok', 'count' => $list['data']->total(), 'data' => $list['data']->all()]);
            }

            return json(['code' => 0, 'msg' => 'ok', 'count' => 0, 'data' => []]);
        }

        return $this->fetch();
    }

    // 添加常用语分类
    public function add()
    {
        if(request()->isPost()) {

            $param = input('post.');

            if(!isset($param['cate_name']) || empty($param['cate_name'])) {
                return json(['code' => -1, 'data' => '', 'msg' => '请输入分类名称']);
            }

            $param['seller_id'] = session('seller_user_id');
            isset($param['status']) ? $param['status']= 1 : $param['status'] = 2;

            $cateModel = new \app\seller\model\Cate();
            $res = $cateModel->addCate($param);

            return json($res);
        }

        return $this->fetch('add');
    }

    // 编辑常用语
    public function edit()
    {
        $cateModel = new \app\seller\model\Cate();

        if(request()->isPost()) {

            $param = input('post.');

            if(!isset($param['cate_name']) || empty($param['cate_name'])) {
                return json(['code' => -1, 'data' => '', 'msg' => '请输入分类名称']);
            }

            isset($param['status']) ? $param['status']= 1 : $param['status'] = 2;

            $cateModel = new \app\seller\model\Cate();
            $res = $cateModel->editCate($param);

            return json($res);
        }

        $cateId = input('param.cate_id');

        $this->assign([
            'cate' => $cateModel->getCateInfoByCateId($cateId)['data']
        ]);

        return $this->fetch('edit');
    }

    // 删除常用语
    public function del()
    {
        if(request()->isAjax()) {

            $cateId = input('param.cate_id');
            $cateModel = new \app\seller\model\Cate();

            $res = $cateModel->delCate($cateId);

            return json($res);
        }
    }
}