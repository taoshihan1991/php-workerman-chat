<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Date: 2019/2/20
 * Time: 14:15
 */
namespace app\model;

use think\facade\Log;
use think\Model;

class BlackList extends Model
{
   protected $table = 'v2_black_list';

    /**
     * 更新黑名单
     * @param $param
     * @return array
     */
   public function updateBlackList($param)
   {
       try {

           $has = $this->where('seller_code', $param['seller_code'])->where('ip', $param['ip'])->findOrEmpty()->toArray();
           if (empty($has)) {

               $param['add_time'] = date('Y-m-d H:i:s');
               $this->insert($param);
           } else {

               $this->where('seller_code', $param['seller_code'])->where('ip', $param['ip'])->update($param);
           }
       }  catch (\Exception $e) {

           Log::error($e->getMessage());
           return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
       }

       return ['code' => 0, 'data' => '', 'msg' => 'ok'];
   }

    /**
     * 检测是否在黑名单中
     * @param $ip
     * @param $sellerCode
     * @return array
     */
   public function checkBlackList($ip, $sellerCode)
   {
       try {

           $has = $this->where('seller_code', $sellerCode)->where('ip', $ip)->findOrEmpty()->toArray();
           if (empty($has)) {
               return ['code' => -2, 'data' => '', 'msg' => 'ok'];
           }
       }  catch (\Exception $e) {

           Log::error($e->getMessage());
           return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
       }

       return ['code' => 0, 'data' => '', 'msg' => 'ok'];
   }

    /**
     * 删除商户的黑名单
     * @param $sellerCode
     * @return array
     */
   public function delSellerBlackList($sellerCode)
   {
       try {

           $this->where('seller_code', $sellerCode)->delete();
       } catch (\Exception $e) {

           return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
       }

       return ['code' => 0, 'data' => '', 'msg' => 'ok'];
   }
}