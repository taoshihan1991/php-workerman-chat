<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Date: 2019/3/1
 * Time: 14:22
 */
namespace app\seller\model;

use think\Model;

class BlackList extends Model
{
    protected $table = 'v2_black_list';

    /**
     * 获取黑名单列表
     * @param $limit
     * @param $groupName
     * @return array
     */
    public function getBlackList($limit, $ip)
    {
        try {

            $where = [];
            if(!empty($ip)) {
                $where[] = ['ip', '=', $ip];
            }

            $res = $this->where('seller_code', session('seller_code'))
                ->where($where)
                ->paginate($limit);
        }catch (\Exception $e) {

            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $res, 'msg' => 'ok'];
    }

    /**
     * 删除黑名单
     * @param $listId
     * @return array
     */
    public function delBlackList($listId)
    {
        try {

            $this->where('list_id', $listId)->where('seller_code', session('seller_code'))->delete();
        }catch (\Exception $e) {

            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => '', 'msg' => '删除成功'];
    }
}