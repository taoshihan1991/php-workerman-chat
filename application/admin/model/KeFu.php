<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Date: 2019/3/1
 * Time: 14:22
 */
namespace app\admin\model;

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
                 ->where($where)
                 ->leftJoin(['v2_group' => 'b'], 'a.group_id = b.group_id')
                 ->paginate($limit);
        }catch (\Exception $e) {

            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $res, 'msg' => 'ok'];
    }

    /**
     * 获取商户下的客服数量
     * @param $sellerId
     * @return float|string
     */
    public function getKeFuNumBySellerId($sellerId)
    {
        return $this->where('seller_id', $sellerId)->count();
    }
}