<?php
// +----------------------------------------------------------------------
// | easy pay [ pay to easy ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2017 All rights reserved.
// +----------------------------------------------------------------------
// | Author: fengxing <QQ:51125330>
// +----------------------------------------------------------------------
/**
 * @author fengxing
 * @date 2014年8月4日
 */
/**
 * 管理员模型类，用于处理管理员相关操作
 */
namespace Common\Model;
class AdminModel extends BaseModel{
    /**
     * 生成安全码
     * @param int $length 安全码长度
     * @return String
     * @author fengxing
     */
    public function saveCode($length=15){
        return stringChange('saveCode',$length);
    }
}