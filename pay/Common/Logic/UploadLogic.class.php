<?php

// +----------------------------------------------------------------------
// | easy pay [ pay to easy ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2017 All rights reserved.
// +----------------------------------------------------------------------
// | Author: fengxing <QQ:51125330>
// +----------------------------------------------------------------------

namespace Common\Logic;

class UploadLogic extends BaseLogic {

    protected $moduleName = '用户';

    /**
     * 通用上传方法
     * @return string
     * @author mabo
     */
    private function upload($path, $maxSize, $allowExts) {
        $upload = useToolFunction('UploadFile'); // 实例化上传类

        if (empty($maxSize))
            $maxSize = 11457280;
        $upload->maxSize = $maxSize; // 设置附件上传大小
        $upload->allowExts = $allowExts; // 设置附件上传类型
        $path = $path . '/' . date('Y/md', time());
        $realpath = realpath('./') . $path;

        if (!file_exists($realpath)) {
            $this->createPath($realpath);
        }
        $upload->savePath = $realpath . '/'; // 设置附件上传目录

        if (!$upload->upload()) { // 上传错误提示错误信息
            return $upload->getErrorMsg();
        } else { // 上传成功 获取上传文件信息
            $info = $upload->getUploadFileInfo();

            return $path . '/' . $info[0]['savename'];
        }
    }

    /**
     * 递归创建路径
     * @param string $path 路径
     * @return Null
     * @author mabo
     */
    private function createPath($path) {
        mkdir($path, 0755, true);
    }

    /**
     * 对上传方法扩展 用于excel
     * @param int $maxSize 限制大小
     * @param string $path 文件路径
     * @return mixed 上传结果
     * @author mabo
     */
    public function uploadExcel($dir = 'excel', $maxSize = 1048576) {
        $allowExts = array(
            'xls',
            'xlsx'
        ); // 设置附件上传类型

        return $this->upload('/Uploads/' . $dir, $maxSize, $allowExts);
    }

    /**
     * 对上传方法扩展 用于image
     * @param int $maxSize 限制大小
     * @param string $path 文件路径
     * @return mixed 上传结果
     * @author mabo
     */
    public function uploadImage($dir = 'images', $maxSize = 1048576) {
        $allowExts = array(
            'jpg',
            'jpeg',
            'png',
            'gif'
        ); // 设置附件上传类型

        return $this->upload('/Uploads/' . $dir, $maxSize, $allowExts);
    }

}
