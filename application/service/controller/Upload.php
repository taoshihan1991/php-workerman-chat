<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Date: 2019/2/27
 * Time: 16:58
 */
namespace app\service\controller;

class Upload extends Base
{
    //上传图片
    public function uploadImg()
    {
        $file = request()->file('file');

        $fileInfo = $file->getInfo();

        // 检测图片格式
        $ext = explode('.', $fileInfo['name']);
        $ext = array_pop($ext);

        $extArr = explode('|', 'jpg|png|gif|jpeg');
        if(!in_array($ext, $extArr)){
            return json(['code' => -3, 'data' => '', 'msg' => '只能上传jpg|png|gif|jpeg的文件']);
        }

        // 移动到框架应用根目录/public/uploads/ 目录下
        $info = $file->move('./uploads');
        if($info){
            $src =  '/uploads' . '/' . date('Ymd') . '/' . $info->getFilename();
            return json(['code' => 0, 'data' => ['src' => $src ], 'msg' => '']);
        }else{
            // 上传失败获取错误信息
            return json(['code' => -1, 'data' => '', 'msg' => $file->getError()]);
        }
    }

    //上传文件
    public function uploadFile()
    {
        $file = request()->file('file');

        $fileInfo = $file->getInfo();

        // 检测图片格式
        $ext = explode('.', $fileInfo['name']);
        $ext = array_pop($ext);

        $extArr = explode('|', 'zip|rar|txt|doc|docx|xls|xlsx');
        if(!in_array($ext, $extArr)){
            return json(['code' => -3, 'data' => '', 'msg' => '只能上传zip|rar|txt|doc|docx|xls|xlsx的文件']);
        }

        // 移动到框架应用根目录/public/uploads/ 目录下
        $info = $file->move('./uploads');
        if($info){
            $src =  '/uploads' . '/' . date('Ymd') . '/' . $info->getFilename();
            return json(['code' => 0, 'data' => ['src' => $src, 'name' => $fileInfo['name'] ], 'msg' => '']);
        }else{
            // 上传失败获取错误信息
            return json(['code' => -1, 'data' => '', 'msg' => $file->getError()]);
        }
    }
}