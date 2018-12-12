<?php
// +----------------------------------------------------------------------
// | easy pay [ pay to easy ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2017 All rights reserved.
// +----------------------------------------------------------------------
// | Author: fengxing <QQ:51125330>
// +----------------------------------------------------------------------
namespace Index\Controller;
class IndexController extends BaseController {
    public function reback($buffer){
        if(empty($buffer[2]) && !IS_AJAX) $buffer[2]='Index'.$this->templateIndex.'/'.ACTION_NAME;
        parent::reback($buffer,!IS_AJAX);
    }

    public function cookietemplate(){
        if($_GET['t']){
            cookie('templatenow',$_GET['t']);
        }
        header('Location:http://'.$_SERVER['HTTP_HOST']);
        exit();
    }

    public function index(){
        $this->reback($buffer,!IS_AJAX);
    }
    public function reg(){
        $buffer = SL('User/reg', $_REQUEST);
        $this->reback($buffer,!IS_AJAX);
    }
    public function login(){
        $buffer = SL('User/login', $_REQUEST);
        $this->reback($buffer,!IS_AJAX);
    }
    public function pass(){
        $buffer = SL('User/pass', $_REQUEST);
        $this->reback($buffer,!IS_AJAX);
    }
    public function sendsms(){
        $buffer = SL('User/sendsms', $_REQUEST);
        $this->reback($buffer,!IS_AJAX);
    }
    public function qrcode(){
        $buffer = SL('Api/qrcode', $_REQUEST);
        $this->reback($buffer,!IS_AJAX);
    }
    //获取协议
    public function getxy(){
        global $publicData;
        $xieyi=$publicData['peizhi']['xieyi'];
        $xieyi=str_replace("\r\n","<br/>",$xieyi);
        $buffer = [1,$xieyi];
        $this->reback($buffer);
    }
}