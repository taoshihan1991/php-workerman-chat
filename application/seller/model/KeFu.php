<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Date: 2019/3/1
 * Time: 14:22
 */
namespace app\seller\model;

use app\model\Service;
use think\Model;

class KeFu extends Model
{
    protected $table = 'v2_kefu';
    protected $autoWriteTimestamp = 'datetime';

    /**
     * 获取分组列表
     * @param $limit
     * @param $where
     * @return array
     */
    public function getKeFuList($limit, $where = [])
    {
        try {

            $res = $this->alias('a')->field('a.*,b.group_name')
                 ->where('a.seller_id', session('seller_user_id'))
                 ->where($where)
                 ->leftJoin(['v2_group' => 'b'], 'a.group_id = b.group_id')
                 ->paginate($limit);
        }catch (\Exception $e) {

            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $res, 'msg' => 'ok'];
    }

    /**
     * 添加客服
     * @param $param
     * @return array
     */
    public function addKeFu($param)
    {
        try {

            $has = $this->where('kefu_name', $param['kefu_name'])
                ->where('seller_id', session('seller_user_id'))
                ->findOrEmpty()->toArray();
            if(!empty($has)) {
                return ['code' => -2, 'data' => '', 'msg' => '客服已经存在'];
            }

            $this->save($param);
        }catch (\Exception $e) {

            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => '', 'msg' => '添加客服成功'];
    }

    /**
     * 获取分组中客服的数量
     * @param $groupId
     * @return array
     */
    public function getKeFuUserByGroup($groupId)
    {
        try {

            $res = $this->where('seller_id', session('seller_user_id'))->where('group_id', $groupId)->count();
        }catch (\Exception $e) {

            return ['code' => -1, 'data' => 0, 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $res, 'msg' => 'ok'];
    }

    /**
     * 获取客服信息
     * @param $keFuId
     * @return array
     */
    public function getKeFuById($keFuId)
    {
        try {

            $info = $this->where('kefu_id', $keFuId)
                ->where('seller_id', session('seller_user_id'))
                ->findOrEmpty()->toArray();
        }catch (\Exception $e) {

            return ['code' => -1, 'data' => [], 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $info, 'msg' => 'ok'];
    }

    /**
     * 编辑客服
     * @param $param
     * @return array
     */
    public function editKeFu($param)
    {
        try {

            $has = $this->where('kefu_name', $param['kefu_name'])
                ->where('seller_id', session('seller_user_id'))
                ->where('kefu_id', '<>', $param['kefu_id'])
                ->findOrEmpty()->toArray();
            if(!empty($has)) {
                return ['code' => -2, 'data' => '', 'msg' => '客服名已经存在'];
            }

            $this->save($param, ['kefu_id' => $param['kefu_id']]);
        }catch (\Exception $e) {

            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => '', 'msg' => '编辑客服成功'];
    }

    /**
     * 删除客服
     * @param $keFuId
     * @return array
     */
    public function delKeFu($keFuId)
    {
        try {

            $this->where('kefu_id', $keFuId)->where('seller_id', session('seller_user_id'))->delete();
        }catch (\Exception $e) {

            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => '', 'msg' => '删除客服成功'];
    }

    /**
     * 获取商家在线客服
     * @return array
     */
    public function getOnlineKeFu()
    {
        try {

            $keFu = $this->where('seller_id', session('seller_user_id'))->whereIn('online_status', [1, 2])
                ->where('kefu_status', 1)
                ->select()->toArray();

            $serviceModel = new Service();
            $serviceLogModel = new ServiceLog();
            foreach ($keFu as $key => $vo) {
                $keFu[$key]['service_num'] = $serviceModel->getNowServiceNum($vo['kefu_code'])['data'];
                $keFu[$key]['total_service_num'] = $serviceLogModel->getKeFuTotalServiceNum($vo['kefu_code'])['data'];
            }
        }catch (\Exception $e) {

            return ['code' => -1, 'data' => [], 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $keFu, 'msg' => 'ok'];
    }

    /**
     * 获取商户客服
     * @return array
     */
    public function getSellerKeFu()
    {
        try {

            $keFu = $this->where('seller_code', session('seller_code'))->select()->toArray();
        }catch (\Exception $e) {

            return ['code' => -1, 'data' => [], 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $keFu, 'msg' => 'ok'];
    }
}