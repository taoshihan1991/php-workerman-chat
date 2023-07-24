<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2020/6/30
 * Time: 9:48 PM
 */
namespace app\strategy\impl;

use app\model\Service;
use app\strategy\DistributionInterface;

/**
 * 环形分配策略
 * Class CircleImpl
 * @package app\strategy\impl
 */
class CircleImpl implements DistributionInterface
{
    private $db = null;

    private $table = '`v2_kefu_distribution`';

    public function setDb($db)
    {
        $this->db = $db;
    }

    public function doDistribute(array $kefuMap)
    {
        $this->db->beginTrans();

        try {

            // TODO 此处我的数组分配的情况放在mysql的，可以改成 redis 等更高效率的db中
            $sellerId = $kefuMap['0']['seller_id'];
            $sql = 'SELECT `kefu_map` FROM ' . $this->table . ' WHERE `seller_id`=' . $sellerId . ' FOR UPDATE';

            $sellerKeFuInfo = $this->db->query($sql);

            if (empty($sellerKeFuInfo)) {
				
                $sellerKefuMap = $kefuMap;
				$sql = 'INSERT INTO ' . $this->table . ' SET `seller_id`=' . $sellerId;
				$this->db->query($sql);
            } else {
			
                $diffMap = [];
                foreach ($kefuMap as $key => $vo) {
                    $diffMap[$vo['kefu_id']] = $vo;
                }
                $sellerKefuMap = json_decode($sellerKeFuInfo['0']['kefu_map'], true);

				$oldMap = [];
                // 刷新在线的客服列表
                foreach ($sellerKefuMap as $key => $vo) {
                    if (!isset($diffMap[$vo['kefu_id']])) {
                        unset($sellerKefuMap[$key]);
                    } else {
						$oldMap[$vo['kefu_id']] = 1;
						$sellerKefuMap[$key] = $diffMap[$vo['kefu_id']];
						unset($diffMap[$vo['kefu_id']]);
					}
                }
				
				// 将新加入的客服，补充到待接待的队列前端
				foreach($diffMap as $key => $vo) {
					if (!isset($oldMap[$key])) {
						array_unshift($sellerKefuMap, $vo);
					}
				}
				
                unset($diffMap, $oldMap);
            }

            $len = count($sellerKefuMap);
            $nowStep = 0;

            $returnKeFu = [];
            $serviceModel = new Service($this->db);
            while ($nowStep < $len) {

                $nowStep++;
                $returnKeFu = array_shift($sellerKefuMap);
                array_push($sellerKefuMap, $returnKeFu);

                $num = $serviceModel->getNowServiceNum($returnKeFu['kefu_code']);
                if(0 != $num['code']) {
                    $this->db->commitTrans();
                    return ['code' => -7, 'data' => '', 'msg' => '获取当前服务数据失败'];
                    break;
                }
			
                // 该客服空闲
                if ($returnKeFu['max_service_num'] > $num['data']) {
                    break;
                } else {
                    $returnKeFu = [];
                }
            }

            $sql = "UPDATE " . $this->table . " SET `kefu_map` = '" . json_encode($sellerKefuMap, JSON_UNESCAPED_UNICODE) . "' WHERE `seller_id`=" . $sellerId;
            $this->db->query($sql);
            $this->db->commitTrans();

            if (empty($returnKeFu)) {
                return ['code' => 202, 'data' => '', 'msg' => '客服全忙'];
            }
			
			$returnKeFu['kefu_code'] = 'KF_' . $returnKeFu['kefu_code'];
            return ['code' => 0, 'data' => $returnKeFu, 'msg' => '分配成功'];
        } catch (\Exception $e) {

            $this->db->rollBackTrans();

            return ['code' => 201, 'data' => $e->getMessage(), 'msg' => '暂无客服上班'];
        }
    }
}