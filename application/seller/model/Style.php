<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2020/7/22
 * Time: 9:35 PM
 */
namespace app\seller\model;

use think\Model;

class Style extends Model
{
    protected $table = 'v2_seller_box_style';

    /**
     * 获取商户的样式配置
     * @return array|null|\PDOStatement|string|Model
     */
    public function getSellerStyle()
    {
        try {

            return $this->where('seller_id', session('seller_user_id'))->find();
        } catch (\Exception $e) {

            return [];
        }
    }

    /**
     * 初始化商户的样式
     * @return array
     */
    public function initStyle()
    {
        try {

            $param = [
                'style_type' => 1,
                'box_color' => '#1e9fff',
                'box_icon' => 1,
                'box_title' => '咨询客服',
                'box_margin' => '20',
                'seller_id' => session('seller_user_id'),
                'create_time' => date('Y-m-d H:i:s')
            ];
            $this->insert($param);

            return $param;
        } catch (\Exception $e) {

            return [];
        }
    }

    /**
     * 编辑样式
     * @param $param
     * @return array
     */
    public function editStyle($param)
    {
        try {

            if (empty($param['style_type'])) {
                unset($param['style_type']);
            }

            if (empty($param['box_color'])) {
                unset($param['box_color']);
            }

            if (empty($param['box_icon'])) {
                unset($param['box_icon']);
            }

            if (empty($param['box_title'])) {
                unset($param['box_title']);
            }

            if (empty($param['box_margin'])) {
                unset($param['box_margin']);
            }

            $param['update_time'] = date('Y-m-d H:i:s');
            $this->where('seller_id', session('seller_user_id'))->update($param);
        } catch (\Exception $e) {

            return [];
        }
    }
}