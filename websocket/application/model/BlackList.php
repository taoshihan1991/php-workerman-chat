<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2020/4/22
 * Time: 8:57 PM
 */
namespace app\model;

class BlackList extends BaseModel
{
    protected $table = 'v2_black_list';

    /**
     * 检测是否在黑名单中
     * @param $ip
     * @param $sellerCode
     * @return array
     */
    public function checkBlackList($ip, $sellerCode)
    {
        try {

            $has = $this->db->select('list_id')->from($this->table)
                ->where('seller_code="' . $sellerCode . '" AND ip="' . $ip . '"')
                ->row();
            if (empty($has)) {
                return ['code' => -2, 'data' => '', 'msg' => 'ok'];
            }
        }  catch (\Exception $e) {

            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => '', 'msg' => 'ok'];
    }
}