<?php
// +----------------------------------------------------------------------
// | easy pay [ pay to easy ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2017 All rights reserved.
// +----------------------------------------------------------------------
// | Author: fengxing <QQ:51125330>
// +----------------------------------------------------------------------
namespace Common\Logic;

/**
 * 基础逻辑类
 *
 * @author fengxing
 * @date 2016年8月31日
 */
class BaseLogic {

    /**
     * 当前页码
     * @var int
     */
    public $_page = 1;

    /**
     * 构造函数
     */
    public function __construct() {
        $page = I(C('VAR_PAGE'));
        if ($page) {
            $this->_page = $page;
        }
    }

    /**
     * 过滤字段
     * @param array $filterArr 过滤字段数组
     * @param array $valueArr 全部字段数组
     * @return array
     * @author fengxing
     */
    protected function filterField($filterArr, $valueArr) {
        foreach ($valueArr as $key => $value) {
            if (!in_array($key, $filterArr) || $value === '') {
                unset($valueArr[$key]);
            }
        }
        return $valueArr;
    }

    /**
     * 描述：对不存在的方法进行处理
     * @author fengxing
     */
    function __call($functionName, $args) {
        //getCookie方法
        if (strpos($functionName, 'getCookie') === 0) {
            return $this->getCookieCommon($functionName, $args);
        }
        //setCookie方法
        if (strpos($functionName, 'setCookie') === 0) {
            return $this->setCookieCommon($functionName, $args);
        }
        //加入错误日志
        if(IS_AJAX){
            $newData=array();
            $newData['data']='function [' . $functionName . '] is nothing';
            $newData['status']=0;
            $this->ajaxReturn($newData,'json');
            exit();
        }else{
            exit('function [' . $functionName . '] is nothing');
        }
    }
    /**
     * 通用获取cookie方法 用于__call
     * @param string $functionName 当前调用的方法名称
     * @param string $args 参数数组
     * @return mixed
     * @author fengxing
     */
    private function getCookieCommon($functionName, $args) {
        $function = preg_replace('/^getCookie/', '', $functionName);
        $moduleName=$args[0];
        if (empty($args[0])) {
            $moduleName = MODULE_NAME;
        }
        //以下方法判断存在的方法
        switch ($function) {
            case 'UserID':
                $userID = $this->getCookie('_UID', $moduleName);
                return $userID;
                break;
            case 'UserName':
                $userID = $this->getCookie('_UNAME', $moduleName);
                return $userID;
                break;
            case 'Code':
                $code = $this->getCookie('_CODE', $moduleName);
                return $code;
                break;
        }
    }

    /**
     * 通用获取cookie方法 用于__call
     * @param string $functionName 当前调用的方法名称
     * @param string $args 参数数组
     * @return mixed
     * @author fengxing
     */
    private function setCookieCommon($functionName, $args) {
        $function = preg_replace('/^setCookie/', '', $functionName);
        //以下方法判断存在的方法
        if (empty($args[2])) {
            $args[2] = MODULE_NAME;
        }
        $args[2] = strtoupper($args[2]);
        switch ($function) {
            case 'UserID':
                return $this->setCookie('_UID', $args[0], $args[1], $args[2]);
                break;
            case 'UserName':
                return $this->setCookie('_UNAME', $args[0], $args[1], $args[2]);
                break;
            case 'Code':
                return $this->setCookie('_CODE', $args[0], $args[1], $args[2]);
                break;
        }
    }

    /**
     * 描述：获取分组下的对应Cookie内容
     * @return array
     * @author fengxing
     */
    private function getCookie($cookieName, $moduleName='USER') {
        return cookie(C('FX_'.$moduleName.'_AUTH_KEY').$cookieName);
    }

    /**
     * 描述：设置分组下的对应Cookie内容
     * @return array
     * @author fengxing
     */
    private function setCookie($cookieName, $value = '', $option = null, $moduleName='USER') {
        cookie(C('FX_'.$moduleName.'_AUTH_KEY').$cookieName, $value, $option);
    }

    /**
     * 记录用户日志
     * @param string $module 模型名称
     * @param string $content 日志内容名称
     * @param string $userID 用户名 兼容没有用户名的情况
     * @author fengxing
     */
    public function userLog($module, $content, $userID = '') {
        $data = array();
        $data['ifadmin'] = MODULE_NAME == 'Manage' ? 1 : 0;
        $data['module'] = $module;
        $data['content'] = $content;
        if (!$userID) {
            $userID = $this->getCookieUserName();
        }
        $data['addtime'] = time();
        $data['ip'] = get_client_ip(0, true);
        $data['userid'] = $userID ? $userID : 0;
        SM('Rizhi')->insertData($data);
    }
    public function adminLog($module,$content){
        $this->userLog($module,$content);
    }

    /**
     * 分页方法
     * @param int $total 总数
     * @param int $pageSize 每页显示数
     * @return string
     */
    public function pageList($total = 0, $pageSize = 0,$map=array()) {
        if (empty($pageSize)) {
            $pageSize = C('FX_PAGESIZE');
        }
        import('Common.Tool.ThisPage');
        $page = new \ThisPage($total, $pageSize,$map);
        return $page->show();
    }

    /**
     * 生成安全码
     * @param int $length 安全码长度
     * @return String
     * @author fengxing
     */
    protected function saveCode($length = 15) {
        return stringChange('saveCode', $length);
    }

    /**
     * 设置错误信息
     * @param array $params 错误参数
     * @return void
     */
    public function addErrorLog($params) {
        SM('LogError')->setLine($params);
    }

}