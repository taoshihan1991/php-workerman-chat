<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2020/7/22
 * Time: 9:35 PM
 */
namespace app\model;

use think\Model;

class Style extends Model
{
    protected $table = 'v2_seller_box_style';

    /**
     * 获取商户的样式配置
     * @param $sellerId
     * @return array|null|\PDOStatement|string|Model
     */
    public function getSellerStyle($sellerId)
    {
        try {

            return $this->where('seller_id', $sellerId)->find();
        } catch (\Exception $e) {

            return [];
        }
    }

    /**
     * 初始化商户的样式
     * @param $sellerId
     * @return array
     */
    public function initStyle($sellerId)
    {
        try {

            $param = [
                'style_type' => 1,
                'box_color' => '#1e9fff',
                'box_icon' => 1,
                'box_title' => '咨询客服',
                'box_margin' => '20',
                'seller_id' => $sellerId,
                'create_time' => date('Y-m-d H:i:s')
            ];
            $this->insert($param);

            return $param;
        } catch (\Exception $e) {

            return [];
        }
    }
}