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
use app\model\Service;
use app\seller\model\Group as GroupModel;
use app\seller\validate\GroupValidate;

class Group extends Base
{
    // 分组列表
    public function index()
    {
        if(request()->isAjax()) {

            $limit = input('param.limit');
            $groupName = input('param.group_name');

            $seller = new GroupModel();
            $list = $seller->getGroupList($limit, $groupName);

            if(0 == $list['code']) {

                return json(['code' => 0, 'msg' => 'ok', 'count' => $list['data']->total(), 'data' => $list['data']->all()]);
            }

            return json(['code' => 0, 'msg' => 'ok', 'count' => 0, 'data' => []]);
        }

        return $this->fetch();
    }

    // 添加分组
    public function addGroup()
    {
        if(request()->isPost()) {

            $param = input('post.');

            $canMake = (new Seller())->checkCanAddGroup(session('seller_user_id'));
            if (0 != $canMake['code']) {
                return ['code' => -4, 'data' => '', 'msg' => '系统错误'];
            }

            if (empty($canMake['data'])) {
                return ['code' => -5, 'data' => '', 'msg' => '分组数量已达上限，请联系管理员增加'];
            }

            $validate = new GroupValidate();
            if(!$validate->check($param)) {
                return ['code' => -3, 'data' => '', 'msg' => $validate->getError()];
            }

            isset($param['group_status']) ? $param['group_status']= 1 : $param['group_status'] = 0;
            isset($param['first_service']) ? $param['first_service']= 1 : $param['first_service'] = 0;
            $param['seller_id'] = session('seller_user_id');

            $seller = new GroupModel();
            $res = $seller->addGroup($param);

            return json($res);
        }

        $groupModel = new \app\model\Group();
        $hasFirst = $groupModel->getFirstServiceGroup(session('seller_user_id'));

        $this->assign([
            'has' => $hasFirst['data']
        ]);

        return $this->fetch('add');
    }

    // 编辑分组
    public function editGroup()
    {
        if(request()->isPost()) {

            $param = input('post.');

            $validate = new GroupValidate();
            if(!$validate->check($param)) {
                return ['code' => -3, 'data' => '', 'msg' => $validate->getError()];
            }

            isset($param['group_status']) ? $param['group_status']= 1 : $param['group_status'] = 0;
            isset($param['first_service']) ? $param['first_service']= 1 : $param['first_service'] = 0;

            $group = new GroupModel();
            $res = $group->editGroup($param);

            return json($res);
        }

        $groupId = input('param.group_id');
        $group = new GroupModel();

        $this->assign([
            'group' => $group->getGroupById($groupId)['data']
        ]);

        return $this->fetch('edit');
    }

    // 删除分组
    public function delGroup()
    {
        if(request()->isAjax()) {

            $groupId = input('param.group_id');
            $group = new GroupModel();

            $res = $group->delGroup($groupId);

            return json($res);
        }
    }
}