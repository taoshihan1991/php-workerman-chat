<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2019/3/3
 * Time: 11:44 AM
 */
namespace app\seller\controller;

use app\seller\model\Style;
use app\seller\model\System as SystemModel;
use app\seller\validate\SystemValidate;

class System extends Base
{
    public function index()
    {
        if(request()->isPost()) {

            $param = input('post.');

            $validate = new SystemValidate();
            if(!$validate->check($param)) {
                return ['code' => -3, 'data' => '', 'msg' => $validate->getError()];
            }

            isset($param['hello_status']) ? $param['hello_status']= 1 : $param['hello_status'] = 0;
            isset($param['relink_status']) ? $param['relink_status']= 1 : $param['relink_status'] = 0;
            isset($param['auto_link']) ? $param['auto_link']= 1 : $param['auto_link'] = 0;
            isset($param['robot_open']) ? $param['robot_open']= 1 : $param['robot_open'] = 0;
            isset($param['pre_input']) ? $param['pre_input']= 1 : $param['pre_input'] = 0;
            isset($param['auto_remark']) ? $param['auto_remark']= 1 : $param['auto_remark'] = 0;

            $sys = new SystemModel();
            $res = $sys->editSystem($param);

            return json($res);
        }

        $system = new SystemModel();
        $this->assign([
            'system' => $system->getSellerConfig()['data']
        ]);

        return $this->fetch();
    }

    public function myStyle()
    {
        $styleModel = new Style();
        if (request()->isPost()) {

            $param = input('post.');
            $styleModel->editStyle($param);

            return json(['code' => 0, 'data' => '', 'msg' => '设置成功']);
        }

        $myStyle = $styleModel->getSellerStyle();
        if (empty($myStyle)) {

            $myStyle = $styleModel->initStyle();
        }

        $this->assign([
            'baseCss1' => getBaseCss(1) . 'right:' . $myStyle['box_margin'] . 'px;background:' . $myStyle['box_color'] . ';',
            'baseCss2' => getBaseCss(2) . 'bottom:' . $myStyle['box_margin'] . 'px;background:' . $myStyle['box_color'] . ';',
            'style' => $myStyle
        ]);

        return $this->fetch('style');
    }
}