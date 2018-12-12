<?php
// +----------------------------------------------------------------------
// | easy pay [ pay to easy ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2017 All rights reserved.
// +----------------------------------------------------------------------
// | Author: fengxing <QQ:51125330>
// +----------------------------------------------------------------------
/**
 * 用户模型
 * @author fengxing
 */
namespace Common\Model;
class UserModel extends BaseModel {

    /**
     * 生成安全码
     * @param int $length 安全码长度
     * @return String
     * @author fengxing
     */
    public function saveCode($length=15){
        return stringChange('saveCode',$length);
    }


    /**
     * 生成商户id
     */
    public function createUserID() {
        $buffer = $this->findData('userid', '1=1', 'userid desc');
        $year = (string) date('Y', time());
        if (empty($buffer) || substr($buffer['userid'], 0, 4) != $year) {
            return $year . '100';
        }
        $rstr = substr($buffer['userid'], count($year));
        $n = str_repeat('9', count($rstr));
        if ($rstr == $n) {
            return $year . '1' . str_repeat('0', count($rstr));
        }
        return (int) $buffer['userid'] + 1;
    }
}