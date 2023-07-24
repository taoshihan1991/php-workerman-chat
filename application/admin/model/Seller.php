<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Date: 2019/2/28
 * Time: 20:50
 */
namespace app\admin\model;

use app\model\BlackList;
use app\model\ComQuestion;
use app\model\Group;
use app\model\QuestionConf;
use app\seller\model\Word;
use think\Model;

class Seller extends Model
{
    protected $table = 'v2_seller';
    protected $autoWriteTimestamp = 'datetime';

    /**
     * 获取商户信息
     * @param $limit
     * @param $where
     * @return array
     */
    public function getSellers($limit, $where)
    {
        $kefu = new KeFu();
        try {

            $res = $this->where($where)->paginate($limit);

            $res = $res->each(function ($item, $key) use ($kefu){
                $item->kf_num = $kefu->getKeFuNumBySellerId($item->seller_id);
            });
        }catch (\Exception $e) {

            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $res, 'msg' => 'ok'];
    }

    /**
     * 获取商户信息
     * @param $limit
     * @param $where
     * @return array
     */
    public function getSellersConfig($limit, $where)
    {
        try {

            $res = $this->where($where)->order('valid_time', 'asc')->paginate($limit);
        }catch (\Exception $e) {

            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $res, 'msg' => 'ok'];
    }

    /**
     * 增加商户
     * @param $seller
     * @return array
     */
    public function addSeller($seller)
    {
        try {

            $has = $this->where('seller_name', $seller['seller_name'])->findOrEmpty()->toArray();
            if(!empty($has)) {
                return ['code' => -2, 'data' => '', 'msg' => '商户名已经存在'];
            }

            $days = config('seller.default_admin_add_day');
            $kefuNum = config('seller.default_max_kefu_num');
            $groupNum = config('seller.default_max_group_num');

            $seller['valid_time']  = date('Y-m-d H:i:s', strtotime("+" . $days . " days"));
            $seller['max_kefu_num']  = $kefuNum;
            $seller['max_group_num']  = $groupNum;
            $seller['create_time'] = date('Y-m-d H:i:s');
            $seller['update_time'] = date('Y-m-d H:i:s');

            $id = $this->insertGetId($seller);
        }catch (\Exception $e) {

            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $id, 'msg' => '添加商户成功'];
    }

    /**
     * 获取商户信息
     * @param $sellerId
     * @return array
     */
    public function getSellerById($sellerId)
    {
        try {

            $info = $this->where('seller_id', $sellerId)->findOrEmpty()->toArray();
        }catch (\Exception $e) {

            return ['code' => -1, 'data' => [], 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => $info, 'msg' => 'ok'];
    }

    /**
     * 编辑商户
     * @param $seller
     * @return array
     */
    public function editSeller($seller)
    {
        try {

            $has = $this->where('seller_name', $seller['seller_name'])->where('seller_id', '<>', $seller['seller_id'])
                ->findOrEmpty()->toArray();
            if(!empty($has)) {
                return ['code' => -2, 'data' => '', 'msg' => '商户名已经存在'];
            }

            $this->save($seller, ['seller_id' => $seller['seller_id']]);
        }catch (\Exception $e) {

            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => '', 'msg' => '编辑商户成功'];
    }

    /**
     * 删除商户
     * @param $sellerId
     * @return array
     */
    public function delSeller($sellerId)
    {
        try {

            $sellerInfo = $this->where('seller_id', $sellerId)->find();

            // 删除商户信息
            $this->where('seller_id', $sellerId)->delete();

            // 删除系统设置
            $sys = new System();
            $sys->removeConfig($sellerId);

            // 删除对应的分组
            $groupModel = new Group();
            $groupModel->delGroupBySellerId($sellerId);

            // 删除对应的客服
            $kefuModel = new \app\model\KeFu();
            $kefuModel->delKefuBySellerId($sellerId);

            // 删除设置的常用语
            db('word')->where('seller_code', $sellerInfo['seller_code'])->delete();

            // 删除常见问题
            $questionModel = new ComQuestion();
            $questionModel->delQuestionBySellerCode($sellerInfo['seller_code']);

            $questionConfModel = new QuestionConf();
            $questionConfModel->delQuestionConfBySellerCode($sellerInfo['seller_code']);

            // 删除黑名单
            $blackModel = new BlackList();
            $blackModel->delSellerBlackList($sellerInfo['seller_code']);

        } catch (\Exception $e) {

            return ['code' => -1, 'data' => '', 'msg' => $e->getMessage()];
        }

        return ['code' => 0, 'data' => '', 'msg' => '删除成功'];
    }
}