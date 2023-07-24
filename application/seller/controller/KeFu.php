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
use app\seller\model\KeFu as KeFuModel;
use app\seller\model\Group as GroupModel;
use app\seller\validate\KeFuValidate;

class KeFu extends Base
{
    // 客服列表
    public function index()
    {
        if(request()->isAjax()) {

            $limit = input('param.limit');
            $keFuName = input('param.kefu_name');
            $groupId = input('param.group_id');

            $where = [];
            if(!empty($keFuName)) {
                $where[] = ['a.kefu_name', 'like', '%' . $keFuName . '%'];
            }

            if(!empty($groupId)) {
                $where[] = ['a.group_id', '=', $groupId];
            }

            $keFu = new KeFuModel();
            $list = $keFu->getKeFuList($limit, $where);

            if(0 == $list['code']) {

                return json(['code' => 0, 'msg' => 'ok', 'count' => $list['data']->total(), 'data' => $list['data']->all()]);
            }

            return json(['code' => 0, 'msg' => 'ok', 'count' => 0, 'data' => []]);
        }

        $group = new GroupModel();
        $this->assign([
            'group' => $group->getSellerGroup()['data']
        ]);

        return $this->fetch();
    }

    // 添加客服
    public function addKeFu()
    {
        if(request()->isPost()) {

            $param = input('post.');

            $canMake = (new Seller())->checkCanAddKeFu(session('seller_user_id'));
            if (0 != $canMake['code']) {
                return ['code' => -4, 'data' => '', 'msg' => '系统错误'];
            }

            if (empty($canMake['data'])) {
                return ['code' => -5, 'data' => '', 'msg' => '客服坐席数量已达上限，请联系管理员增加'];
            }

            $validate = new KeFuValidate();
            if(!$validate->check($param)) {
                return ['code' => -3, 'data' => '', 'msg' => $validate->getError()];
            }

            isset($param['kefu_status']) ? $param['kefu_status']= 1 : $param['kefu_status'] = 0;
            $param['kefu_code'] = uniqid();
            $param['kefu_password'] = md5($param['kefu_password'] . config('service.salt'));
            $param['seller_id'] = session('seller_user_id');
            $param['seller_code'] = session('seller_code');
            $param['online_status'] = 0;

            $keFu = new KeFuModel();
            $res = $keFu->addKeFu($param);

            return json($res);
        }

        $group = new GroupModel();
        $this->assign([
            'group' => $group->getSellerGroup()['data']
        ]);

        return $this->fetch('add');
    }

    // 编辑客服
    public function editKeFu()
    {
        if(request()->isPost()) {

            $param = input('post.');

            $validate = new KeFuValidate();
            if(!$validate->scene('edit')->check($param)) {
                return ['code' => -3, 'data' => '', 'msg' => $validate->getError()];
            }

            isset($param['kefu_status']) ? $param['kefu_status']= 1 : $param['kefu_status'] = 0;
            if(!empty(trim($param['kefu_password']))) {          
                $param['kefu_password'] = md5($param['kefu_password'] . config('service.salt'));
            } else {
            	unset($param['kefu_password']);
            }

            $keFu = new KeFuModel();
            $res = $keFu->editKeFu($param);

            return json($res);
        }

        $keFuId = input('param.kefu_id');
        $keFu = new KeFuModel();
        $group = new GroupModel();

        $this->assign([
            'kefu' => $keFu->getKeFuById($keFuId)['data'],
            'group' => $group->getSellerGroup()['data']
        ]);

        return $this->fetch('edit');
    }

    // 删除客服
    public function delKeFu()
    {
        if(request()->isAjax()) {

            $keFuId = input('param.kefu_id');
            $keFu = new KeFuModel();

            $res = $keFu->delKeFu($keFuId);

            return json($res);
        }
    }

    // 点赞
    public function praise()
    {
        if(request()->isAjax()) {

            try {

                // 所有的客服
                $users = db('kefu')->field('kefu_code,kefu_name')->where('seller_code', session('seller_code'))->select();
                $userArr = [];
                foreach($users as $key => $vo) {

                    $userArr[$vo['kefu_code']]['kefu_code'] = $vo['kefu_code'];
                    $userArr[$vo['kefu_code']]['kefu_name'] = $vo['kefu_name'];
                    $userArr[$vo['kefu_code']]['star1'] = 0; // 非常不满意
                    $userArr[$vo['kefu_code']]['star2'] = 0; // 不满意
                    $userArr[$vo['kefu_code']]['star3'] = 0; // 一般
                    $userArr[$vo['kefu_code']]['star4'] = 0; // 满意
                    $userArr[$vo['kefu_code']]['star5'] = 0; // 非常满意
                }

                $start = input('param.start', date('Y-m') . '-01');
                $end = input('param.end', date('Y-m-d'));

                $result = db('praise')->where('add_time', '>=', $start)->where('add_time', '<=', $end . ' 23:59:59')
                    ->where('seller_code', session('seller_code'))
                    ->select();
                foreach($result as $key=>$vo) {
                    if(isset($userArr[$vo['kefu_code']])) {

                        switch ($vo['star']) {
                            case 1:
                                $userArr[$vo['kefu_code']]['star1'] += 1;
                                break;
                            case 2:
                                $userArr[$vo['kefu_code']]['star2'] += 1;
                                break;
                            case 3:
                                $userArr[$vo['kefu_code']]['star3'] += 1;
                                break;
                            case 4:
                                $userArr[$vo['kefu_code']]['star4'] += 1;
                                break;
                            case 5:
                                $userArr[$vo['kefu_code']]['star5'] += 1;
                                break;
                        }
                    }
                }

                $returnUser = [];
                foreach($userArr as $vo) {
                    $total = $vo['star5'] + $vo['star4'] + $vo['star3'] + $vo['star2'] + $vo['star1'];
                    if(0 == $total) {
                        $vo['percent'] = '0%';
                    }else {
                        $vo['percent'] = round(($vo['star5'] + $vo['star4']) / $total * 100, 2) . '%';
                    }

                    $returnUser[] = $vo;
                }

                return json(['code' => 0, 'msg' => 'ok', 'count' => count($userArr), 'data' => $returnUser]);

            } catch (\Exception $e) {

                return json(['code' => 0, 'msg' => 'ok', 'count' => 0, 'data' => []]);
            }
        }

        $this->assign([
            'start' => date('Y-m') . '-01',
            'end' => date('Y-m-d')
        ]);

        return $this->fetch();
    }

    // 总体客服分析
    public function praiseAll()
    {
        if(request()->isAjax()){

            $start = input('param.start', date('Y-m') . '-01');
            $end = input('param.end', date('Y-m-d'));
            $base = [
                1 => ['title' => '非常不满意', 'star_total' => 0, 'percent' => '0%'],
                2 => ['title' => '不满意', 'star_total' => 0, 'percent' => '0%'],
                3 => ['title' => '一般', 'star_total' => 0, 'percent' => '0%'],
                4 => ['title' => '满意', 'star_total' => 0, 'percent' => '0%'],
                5 => ['title' => '非常满意', 'star_total' => 0, 'percent' => '0%']
            ];

            try {

                $result = db('praise')->field('count(*) as star_total, star')->where('add_time', '>=', $start)
                    ->where('add_time', '<=', $end . ' 23:59:59')
                    ->group('star')->order('star desc')->where('seller_code', session('seller_code'))->select();

                $total = 0;
                foreach($result as $key => $vo) {
                    if(array_key_exists($vo['star'], $base)) {
                        $base[$vo['star']]['star_total'] = $vo['star_total'];
                    }

                    $total += $vo['star'];
                }

                foreach($base as $key => $vo) {
                    if(0 != $total) {
                        $base[$key]['percent'] = round($vo['star_total'] / $total * 100, 2) . '%';
                    }
                }

                return json(['code' => 0, 'msg' => 'ok', 'count' => 5, 'data' => $base]);
            } catch (\Exception $e) {

                return json(['code' => 0, 'msg' => 'ok', 'count' => 0, 'data' => []]);
            }
        }

        $this->assign([
            'start' => date('Y-m') . '-01',
            'end' => date('Y-m-d')
        ]);

        return $this->fetch('all');
    }
}