<?php
/**
 * 字符串验证类    验证字符串是否符合标准
 */
class StringCheck {
    /**
     * 判断用户密码是否是全数字或者全字母或者小于8位
     * @param string $passWord 密码字符串
     * @return bool
     * @author fengxing
     */
    public function checkUserPassWord($passWord){
        if(preg_match('/^\d*$/',$passWord) || preg_match('/^[a-zA-Z]+$/',$passWord) || strlen($passWord)<8){
            return false;
        }
        return true;
    }

    /**
     * 判断是否是日期
     * @param string $sDate 字符串
     * @return bool
     * @author fengxing
     * */
    public function isDate($sDate) {
        $dateArr = explode("-", $sDate);
        if (is_numeric($dateArr[0]) && is_numeric($dateArr[1]) && is_numeric($dateArr[2])) {
            return checkdate($dateArr[1], $dateArr[2], $dateArr[0]);
        }
        return false;
    }

    /**
     * 判断是否是指定长度的英文
     * @param int $num1 最小长度
     * @param int $num2 最大长度
     * @param string $str 字符串
     * @return bool
     * @author fengxing
     */
    public function isEngLength($num1, $num2, $str) {
        return (preg_match("/^[a-zA-Z0-9]{" . $num1 . "," . $num2 . "}$/", $str)) ? true : false;
    }

    /**
     * 验证是否是手机号
     * @param int $num 手机号
     * @return bool
     * @author fengxing
     */
    public function checkIfPhone($num){
        return preg_match('/^1[0-9]{10}$/',$num) ? true : false;
    }

    /**
     * 验证是否是邮箱
     * @param string $num 邮箱号
     * @return bool
     * @author fengxing
     */
    public function checkIfEmail($num){
        $pregEmail = '/^([a-z0-9_\.-]+)@([\da-z\.-]+)\.([a-z\.]{2,6})$/';
        return preg_match($pregEmail,$num) ? true : false;
    }

    /**
     * 验证是否是网址
     * @param string $http 邮箱号
     * @return bool
     * @author fengxing
     */
    public function checkIfHttp($http){
        $pregEmail = '/^(http:\/\/)|(https:\/\/)/i';
        return preg_match($pregEmail,$http) ? true : false;
    }
}
