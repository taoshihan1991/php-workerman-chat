<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Date: 2019/2/21
 * Time: 17:15
 */
namespace app\model;

use think\facade\Log;
use think\Model;

class Chat extends Model
{
    protected $table = 'v2_chat_log';

    /**
     * 记录聊天日志
     * @param $param
     * @return int|string
     */
    public function addChatLog($param)
    {
        try {

            return $this->insertGetId($param);
        } catch (\Exception $e) {

            Log::error($e->getMessage());
        }

        return 0;
    }

    /**
     * 获取聊天日志信息[客服端]
     * @param $param
     * @return array
     */
    public function getChatLog($param)
    {
        try {

            $limit = config('service.log_page');
            $offset = ($param['page'] - 1) * $limit;

            $sellerCode = session('kf_seller_code');

            $temp = $this->where(function ($query) use($param, $sellerCode) {
                $query->where(function($query) use($param, $sellerCode) {
                    $query->where('from_id', $param['uid'])->where('seller_code', $sellerCode);
                })->whereOr(function($query) use($param, $sellerCode) {
                    $query->where('seller_code', $sellerCode)->where('to_id', $param['uid']);
                });
            })->where('valid', 1);

            $logs = $temp->limit($offset, $limit)->order('log_id', 'desc')->select()->toArray();
            sort($logs);
            $total = $temp->count();

            foreach($logs as $key => $vo) {

                $logs[$key]['type'] = 'user';

                if(strpos($vo['from_id'], 'KF_') !== false || $vo['from_id'] == '0') {
                    $logs[$key]['type'] = 'mine';
                }
            }

            return ['code' => 0, 'data' => $logs, 'msg' => intval($param['page']), 'total' => ceil($total / $limit)];
        } catch (\Exception $e) {

            return ['code' => 0, 'data' => [], 'msg' => 0, 'total' => 0];
        }
    }

    /**
     * 获取聊天日志信息[访客端]
     * @param $param
     * @return array
     */
    public function getCustomerChatLog($param)
    {
        try {

            $sellerCode = $param['u'];
            $time = $param['t'];
            $token = $param['tk'];

            // 权限校验
            if(time() - $time > 86400 * 2) {
                return ['code' => 0, 'data' => [], 'msg' => 0, 'total' => 0];
            }

            $safeToken = md5($sellerCode . config('service.salt') . $time);
            if($token != $safeToken) {
                return ['code' => 0, 'data' => [], 'msg' => 0, 'total' => 0];
            }

            $limit = config('service.log_page');
            $offset = ($param['page'] - 1) * $limit;

            $temp = $this->where(function($query) use($param, $sellerCode) {
                $query->where(function($query) use($param, $sellerCode) {
                    $query->where('from_id', $param['uid'])->where('seller_code', $sellerCode);
                })->whereOr(function($query) use($param, $sellerCode) {
                    $query->where('seller_code', $sellerCode)->where('to_id', $param['uid']);
                });
            })->where('valid', 1);

            $logs = $temp->limit($offset, $limit)->order('log_id', 'desc')->select()->toArray();
            sort($logs);
            $total = $temp->count();

            foreach($logs as $key => $vo) {

                $logs[$key]['type'] = 'user';

                if(strpos($vo['from_id'], 'KF_') === false && $vo['from_id'] != '0') {
                    $logs[$key]['type'] = 'mine';
                }
            }

            return ['code' => 0, 'data' => $logs, 'msg' => intval($param['page']), 'total' => ceil($total / $limit)];
        } catch (\Exception $e) {

            return ['code' => 0, 'data' => [], 'msg' => 0, 'total' => 0];
        }
    }

    /**
     * 获取聊天日志信息[商户端]
     * @param $param
     * @return array
     */
    public function getSellerChatLog($param)
    {
        try {

            $limit = config('service.log_page');
            $offset = ($param['page'] - 1) * $limit;

            $sellerCode = session('seller_code');

            $temp = $this->where(function($query) use($param, $sellerCode) {
                $query->where('from_id', $param['uid'])->where('seller_code', $sellerCode);
            })->whereOr(function($query) use($param, $sellerCode) {
                $query->where('seller_code', $sellerCode)->where('to_id', $param['uid']);
            });

            $logs = $temp->limit($offset, $limit)->order('log_id', 'desc')->select()->toArray();
            sort($logs);
            $total = $temp->count();

            foreach($logs as $key => $vo) {

                $logs[$key]['type'] = 'user';

                if(strpos($vo['from_id'], 'KF_') !== false || $vo['from_id'] == '0') {
                    $logs[$key]['type'] = 'mine';
                }
            }

            return ['code' => 0, 'data' => $logs, 'msg' => intval($param['page']), 'total' => ceil($total / $limit)];
        } catch (\Exception $e) {

            return ['code' => 0, 'data' => [], 'msg' => 0, 'total' => 0];
        }
    }

    /**
     * 新版商户后台获取聊天数据 -- 2020-06-07
     * @param $param
     * @return array
     */
    public function getSellerChatLogBackend($param)
    {
        try {

            $limit = config('service.log_page');
            $offset = ($param['page'] - 1) * $limit;

            $sellerCode = session('seller_code');

            $where = [];
            if (!empty($param['content'])) {
                $where[] = ['content', 'like', $param['content'] . '%'];
            }

            $sql = "SELECT ### FROM `v2_chat_log` 
                WHERE  
                (( `from_id` = '" . $param['customer_id'] . "'  AND `to_id` = '" . "KF_" . $param['kefu_code'] . "'  AND `seller_code` = '" . $sellerCode . "')  
                OR 
                ( `seller_code` = '" . $sellerCode . "' AND `from_id` = '" . "KF_" . $param['kefu_code'] . "'  AND `to_id` = '" . $param['customer_id'] . "'))";

            if (!empty($param['content'])) {

                $sql .= " AND `content` LIKE '%" . $param['content'] . "%'";
            }

            $total = $this->query(str_replace("###", "count(*) as t_total", $sql))['0']['t_total'];
            $sql .= "ORDER BY `log_id` DESC LIMIT " . $offset . "," . $limit . ";";

            $logs = $this->query(str_replace("###", "*", $sql));
            sort($logs);

            foreach($logs as $key => $vo) {

                $logs[$key]['type'] = 'user';

                if(strpos($vo['from_id'], 'KF_') !== false || $vo['from_id'] == '0') {
                    $logs[$key]['type'] = 'mine';
                }
            }

            return ['code' => 0, 'data' => $logs, 'msg' => intval($param['page']), 'total' => ceil($total / $limit)];
        } catch (\Exception $e) {

            return ['code' => 0, 'data' => [], 'msg' => 0, 'total' => 0];
        }
    }

    /**
     * 批量更新阅读状态
     * @param $ids
     * @return array
     */
    public function updateReadStatusBatch($ids)
    {
        try {

            $this->whereIn('log_id', $ids)->setField('read_flag', 2);

            return ['code' => 0, 'data' => [], 'msg' => 0, 'total' => 0];
        }  catch (\Exception $e) {
            Log::error($e->getMessage());
            return ['code' => -1, 'data' => [], 'msg' => 0, 'total' => 0];
        }
    }

    /**
     * 获取聊天日志信息[PC客服端]
     * @param $param
     * @return array
     */
    public function getChatLogByClint($param)
    {
        try {

            $limit = config('service.log_page');
            $offset = ($param['page'] - 1) * $limit;

            $sellerCode = $param['seller_code'];

            $temp = $this->where(function($query) use($param, $sellerCode) {
                $query->where('from_id', $param['uid'])->where('seller_code', $sellerCode);
            })->whereOr(function($query) use($param, $sellerCode) {
                $query->where('seller_code', $sellerCode)->where('to_id', $param['uid']);
            });

            $logs = $temp->limit($offset, $limit)->order('log_id', 'desc')->select()->toArray();
            sort($logs);
            $total = $temp->count();

            foreach($logs as $key => $vo) {

                $logs[$key]['type'] = 'user';

                if(strpos($vo['from_id'], 'KF_') !== false) {
                    $logs[$key]['type'] = 'mine';
                }
            }

            return ['code' => 0, 'data' => $logs, 'msg' => intval($param['page']), 'total' => ceil($total / $limit)];
        } catch (\Exception $e) {

            return ['code' => 0, 'data' => [], 'msg' => 0, 'total' => 0];
        }
    }
}