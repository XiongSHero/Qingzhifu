<?php
// +----------------------------------------------------------------------
// | easy pay [ pay to easy ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2017 All rights reserved.
// +----------------------------------------------------------------------
// | Author: fengxing <QQ:51125330>
// +----------------------------------------------------------------------
namespace Common\Controller;
use Think\Controller;
class DefaultController extends Controller {

    public function __construct(){
        header("Content-type: text/html; charset=UTF-8");
        parent::__construct();
        if(!defined('__URL__')) define('__URL__', __CONTROLLER__);
        if(!defined('__PUBLIC__')) define('__PUBLIC__', __ROOT__.'/Public');
        $this->initial();
    }

    private function initial(){
        global $publicData;
        if(empty($publicData['peizhi'])){
            $peizhi=SL('Param')->getPZ();
            if(empty($peizhi['logopath'])) $peizhi['logopath']='/Public/index/night/logo.png';
            $publicData['peizhi']=$peizhi;
        }
    }

    /**
     * 编辑器图片上传
     */
    public function upload(){
        $dir=$_GET['dir'];
        if(empty($dir)) $dir='images';
        //logo上传
        if ($_FILES) {
            $path = SL('Upload')->uploadImage($dir);
            if (!strstr($path, 'Uploads')) {
                $buffer=[0,
                    $path];
            }else{
                $buffer=[1,$path];
            }
        }else{
            $buffer=[0,'请上传文件'];
        }

        $this->reback($buffer,!IS_AJAX);
    }

    /**
     * 描述：对不存在的方法进行处理
     * @author fengxing
     */
    function __call($functionName, $args){
        //getCookie方法
        if(strpos($functionName,'getCookie')===0){
            return $this->getCookieCommon($functionName,$args);
        }
        //setCookie方法
        if(strpos($functionName,'setCookie')===0){
            return $this->setCookieCommon($functionName,$args);
        }
        //获取接口方法
        if(strpos($functionName,'getApi')===0){
            return getApi($functionName,$args);
        }

        if( 0 === strcasecmp($functionName,ACTION_NAME.C('ACTION_SUFFIX'))) {
            if(method_exists($this,'_empty')) {
                // 如果定义了_empty操作 则调用
                $this->_empty($functionName,$args);
            }elseif(file_exists_case($this->view->parseTemplate())){
                // 检查是否存在默认模版 如果有直接输出模版
                $this->display();
            }else{
                if(C('SHOW_PAGE_ERROR_MORE')==1){
                    E(L('_ERROR_ACTION_').':'.ACTION_NAME);
                    return;
                }
                //记录错误信息
                D('Base')->addErrorLog(array('description'=>'ActionName:'.ACTION_NAME.' FunctionName:'.$functionName.'('.serialize($args).') source:'.$_SERVER['HTTP_REFERER']));
                emptyUrl();
                return;
            }
        }else{
            if(C('SHOW_PAGE_ERROR_MORE')==1){
                E(__CLASS__.':'.$functionName.L('_METHOD_NOT_EXIST_'));
                return;
            }
            //记录错误信息
            D('Base')->addErrorLog(array('description'=>__CLASS__.':'.$functionName.'('.serialize($args).')'));
            emptyUrl();
            return;
        }
    }
    /**
     * 描述：获取分组下的对应Cookie内容
     * @return array
     * @author fengxing
     */
    private function getCookie($cookieName,$style='USER'){
        return cookie(C('FX_'.$style.'_AUTH_KEY').$cookieName);
    }

    /**
     * 通用获取cookie方法 用于__call
     * @param string $functionName 当前调用的方法名称
     * @param string $args 参数数组
     * @return mixed
     * @author fengxing
     */
    private function getCookieCommon($functionName,$args){
        $function=preg_replace('/^getCookie/','',$functionName);
        $moduleName = '';
        if(isset($args[0])){
            $moduleName = $args[0];
        }
        //以下方法判断存在的方法
        switch($function){
            case 'UserID':
                $userID = $this->getCookie('_UID',$moduleName);
                if(!$userID && isset($_POST['userID'])) $userID = $_POST['userID'];
                return $userID;
                break;
            case 'Code':
                $code=$this->getCookie('_CODE',$moduleName);
                if(!$code && isset($_POST['userCode'])) $code=$_POST['userCode'];
                return $code;
                break;
            case 'Time':
                return $this->getCookie('_TIME',$moduleName);
                break;
            default:
                return $this->getCookie('_'.strtoupper($function),$moduleName);
        }
    }
    /**
     * 描述：设置分组下的对应Cookie内容
     * @return array
     * @author fengxing
     */
    private function setCookie($cookieName,$value='',$option=null,$moduleName='USER'){
        if(empty($moduleName)){
            $moduleName='USER';
        }
        cookie(C('FX_'.$moduleName.'_AUTH_KEY').$cookieName,$value,$option);
    }

    /**
     * 通用获取cookie方法 用于__call
     * @param string $functionName 当前调用的方法名称
     * @param string $args 参数数组
     * @return mixed
     * @author fengxing
     */
    private function setCookieCommon($functionName,$args){
        $function=preg_replace('/^setCookie/','',$functionName);
        //以下方法判断存在的方法
        switch($function){
            case 'UserID':
                return $this->setCookie('_UID',$args[0],$args[1],$args[2]);
                break;
            case 'Code':
                $code=$this->setCookie('_CODE',$args[0],$args[1],$args[2]);
                return $code;
                break;
            case 'Time':
                return $this->setCookie('_TIME',$args[0],$args[1],$args[2]);
                break;
            default:
                return $this->setCookie('_'.strtoupper($function),$args[0],$args[1],$args[2]);
        }
    }
    /**
     * 返回错误码
     * @param string $errorNum 错误码 多个则以逗号间隔
     * @param int $flag 类型 默认0返回错误页面 1返回ajax数据 2返回字符串
     * @param string $url 跳转路径
     * @param string $replace 错误码中%s替换 多个则以逗号间隔
     * @return string|json
     * @author fengxing
     */
    protected function setError($errorNum,$flag=0,$url='',$replace='') {
        $this->ajaxSetError($errorNum,$flag,$url,$replace);
    }

    /**
     * 返回正确数据
     * @param string $data 需要返回的数据
     * @return json
     * @author fengxing
     */
    protected function setBack($data, $url = '', $second = 3, $moreData = array()) {
        if( IS_AJAX || $data['return']==2) {
            $return = [$data, $url, $second, $moreData];
            $this->ajaxSetBack($return);
            exit();
        }
        $this->ajaxSetBack($data, $url, $second, $moreData);
        exit();
    }

    /**
     * 通用返回数据
     * @param array $buffer 返回数组
     *              错误array(0,'错误编号','跳转地址',替换数据，跳转默认时间)
     *              正确array(1,'正确提示|模板数据','跳转地址','跳转默认时间','更多数据包括cookie数据')
     *              模板数据格式 array('pageName'=>'页面标题','buffer'=>array('数据内容'))
     * @param int $ifTemplate 是否有可能模板输出 1是 0否
     * @param int $ifFetch 是否返回模板输出数据 1是 0否
     * @param array $moreData 更多的数据输出
     * @return null
     * @author fengxing
     */
    public function reback($buffer, $ifTemplate = 0, $ifFetch = 0) {
        if ($buffer[0] === 0) {  //输出错误
            if (empty($buffer[4]) || !is_numeric($buffer[4]))
            $this->setError($buffer[1], IS_AJAX, $buffer[2], $buffer[3], $buffer[4]);
        }else {
            if ($ifTemplate == 1) {  //输出模板
                /* 载入模板标签 */
                if ($ifFetch)
                    return $this->loadTemplate($buffer[1], $buffer[2], $ifFetch);
                $this->loadTemplate($buffer[1], $buffer[2]);
            }else { //输出成功
                if (empty($buffer[3]) || !is_numeric($buffer[3]))
                    $buffer[3] = 3;
                $this->setBack($buffer[1], $buffer[2], $buffer[3],$buffer[4]);
            }
        }
    }
    /**
     * 载入模板数据
     * @param int $buffer 模板数据
     * @param int $tempFile 模板文件
     * @param int $ifFetch 是否返回模板输出数据 1是 0否
     * @author fengxing
     */
    public function loadTemplate($params, $tempFile = '', $ifFetch = 0) {
        foreach ($params as $i => $param) {
            $this->assign($i, $param); //模板标识
        }
        if ($ifFetch === 0)
            $this->display($tempFile);
        return $this->fetch($tempFile);
    }

    /**
     * ajax 返回所有错误码
     * @param string $errorNum 错误码 多个则以逗号间隔
     * @param int $flag=0 类型 默认0返回错误页面 1返回ajax数据 2返回字符串
     * @param string $url 跳转路径
     * @param string $replace 错误码中%s替换 多个则以逗号间隔
     * @param string $diplayContent='Public/error' 默认加载模板
     * @return string|json
     * @author demo
     */
    public function ajaxSetError($errorNum,$flag=0,$url='',$replace='',$displayContent=''){

        if(!$errorNum) return ; //错误码为空

        //兼容多个错误码
        if(!is_array($errorNum)) $numArray=explode(',',$errorNum);
        else $numArray=$errorNum;

        $error = implode(',', $numArray);
        if (!$error)
            $error = '未知错误！'; //错误描述为空

        if(empty($error)){
            $error=$errorNum; //错误描述为空
            $errorNum=0;
        }
        if ($flag === false)
            $flag = 0;
        if ($flag === true)
            $flag = 1;


        //返回类型
        switch($flag){
            case 0:
                $this->showPageMsg($error, $url,3,$displayContent);
                break;
            case 1:
                if($url){
                    $data=[$error, $url, 3];
                }else{
                    $data=$error;
                }

                $newData['data']=$data;
                $newData['status']=0;
                $this->ajaxReturn($newData,'json',JSON_UNESCAPED_UNICODE);
                break;
            case 2:
                return $error;
                break;
        }
    }
    /**
     * 返回正确数据
     * @param string $data 需要返回的数据
     * @param string $url 跳转地址
     * @param int $second 跳转间隔时间
     * @return json
     * @author demo
     */
    public function ajaxSetBack($data,$url='',$second=3, $moreData = array()) {
        if( IS_AJAX || $data['return']==2) {
            $newData['data']=$data;
            $newData['status']=1;
            if(!empty($moreData)) $newData['code']=$moreData;
            $this->ajaxReturn($newData,'json',JSON_UNESCAPED_UNICODE);
        }
        $this->showPageMsg($data,$url,$second);
    }
    /**
     * 返回json数据
     * @param array $data 需要返回的数据
     * @return json
     * @author demo
     */
    public function ajaxBack($data) {
        $this->ajaxReturn($data,'json',JSON_UNESCAPED_UNICODE);
    }

    /**
     * 提示信息
     * @param string $msgDetail 错误提示标题
     * @param string $link 跳转地址
     * @param bool $autoRedirect = true 跳转地址
     * @param int $seconds=3 等待时间
     * @param sting $displayContent 调取模板名称
     * @return bool
     * @author demo
     */
    public function showPageMsg($msgDetail, $link='',  $seconds = 3,$displayContent=''){
        if(empty($displayContent)) $displayContent=MODULE_NAME.'@Public/msg';
        if ($link) {
            $links[0]['text'] = '进入>>';
            $links[0]['href'] = $link;
            $links[0]['target'] = '_self';
        }else{
            $links[0]['text'] = '返回上一页';
            $links[0]['href'] = 'javascript:history.go(-1)';
            $links[0]['target'] = '_self';
        }
        $this->assign('msg', $msgDetail);
        $this->assign('links', $links);
        $this->assign('jumpUrl', $links[0]['href']);
        $this->assign('waitSecond', $seconds);
        $this->display($displayContent);
        exit;
    }
    /**
     * 验证码显示
     * @param $imageMsg array 验证码设置属性
     * @author fengxing
     */
    public function verify($imageMsg=''){
        import("Common.Tool.Image");
        if(!empty($imageMsg)){
            extract($imageMsg);
            \Image :: buildImageVerify($total,$num,$style,$width,$height,$action);
        }else{
            \Image :: buildImageVerify();
        }
    }
}