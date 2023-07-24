<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Date: 2019/3/1
 * Time: 14:22
 */
namespace app\seller\model;

use think\Model;

class Group extends Model
{
    protected $table = 'v2_group';
    protected $autoWriteTimestamp = 'datetime';

    /**
     * 获取分组列表
     * @param $limit
     * @param $groupName
     * @return array
     */
    public function getGroupList($limit, $groupName)
    {
        try {

            $where = [];
            if(!empty($groupName)) {
                $where[] = ['group_name', 'like', '%' . $groupName . '%'];
            }

            $keFu = new KeFu();
            $res = $this->where('seller_id', session('seller_user_id'))
                ->where($where)
                ->paginate($limit)->each(function($item, $key) use ($keFu) {

                $item['group_users'] = $keFu->getKeFuUserByGroup($item['group_id'])['data'];
                return $item;
            });
        }catch (\Exception $e) {

            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $res, 'msg' => 'ok'];
    }

    /**
     * 增加分组
     * @param $group
     * @return array
     */
    public function addGroup($group)
    {
        try {

            $has = $this->where('group_name', $group['group_name'])
                ->where('seller_id', session('seller_user_id'))
                ->findOrEmpty()->toArray();
            if(!empty($has)) {
                return ['code' => -2, 'data' => '', 'msg' => '分组名已经存在'];
            }

            if(1 == $group['first_service'] && 1 == $group['group_status']) {
                $this->where('seller_id', session('seller_user_id'))->update(['first_service' => 0]);
            } else {
                $group['first_service'] = 0;
            }

            $this->save($group);
        }catch (\Exception $e) {

            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => '', 'msg' => '添加分组成功'];
    }

    /**
     * 获取分组信息
     * @param $groupId
     * @return array
     */
    public function getGroupById($groupId)
    {
        try {

            $info = $this->where('group_id', $groupId)->where('seller_id', session('seller_user_id'))
                ->findOrEmpty()->toArray();
        }catch (\Exception $e) {

            return ['code' => -1, 'data' => [], 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $info, 'msg' => 'ok'];
    }

    /**
     * 编辑分组
     * @param $group
     * @return array
     */
    public function editGroup($group)
    {
        try {

            $has = $this->where('group_name', $group['group_name'])
                ->where('seller_id', session('seller_user_id'))
                ->where('group_id', '<>', $group['group_id'])
                ->findOrEmpty()->toArray();
            if(!empty($has)) {
                return ['code' => -2, 'data' => '', 'msg' => '分组名已经存在'];
            }

            if(1 == $group['first_service'] && 1 == $group['group_status']) {
                $this->where('seller_id', session('seller_user_id'))->update(['first_service' => 0]);
            } else {
                $group['first_service'] = 0;
            }

            $this->save($group, ['group_id' => $group['group_id']]);
        }catch (\Exception $e) {

            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => '', 'msg' => '编辑分组成功'];
    }

    /**
     * 删除分组
     * @param $groupId
     * @return array
     */
    public function delGroup($groupId)
    {
        try {

            $keFu = new KeFu();
            $num = $keFu->getKeFuUserByGroup($groupId);
            if(!empty($num['data'])) {
                return ['code' => -2, 'data' => '', 'msg' => '该分组下有客服，不可删除'];
            }

            $isFirst = $this->where('group_id', $groupId)->findOrEmpty()->toArray();
            if (1 == $isFirst['first_service']) {
                return ['code' => -3, 'data' => '', 'msg' => '前置分组不可删除'];
            }

            $this->where('group_id', $groupId)->where('first_service', 0)
                ->where('seller_id', session('seller_user_id'))->delete();
        }catch (\Exception $e) {

            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => '', 'msg' => '删除分组成功'];
    }

    /**
     * 获取当前商户所有的分组
     * @return array
     */
    public function getSellerGroup()
    {
        try {

            $info = $this->where('seller_id', session('seller_user_id'))
                ->select()->toArray();
        }catch (\Exception $e) {

            return ['code' => -1, 'data' => [], 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $info, 'msg' => 'ok'];
    }
}