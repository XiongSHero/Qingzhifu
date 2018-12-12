<?php
// +----------------------------------------------------------------------
// | easy pay [ pay to easy ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2017 All rights reserved.
// +----------------------------------------------------------------------
// | Author: fengxing <QQ:51125330>
// +----------------------------------------------------------------------
namespace Manage\Controller;
class PayController extends BaseController {
    public function yzf(){
        $buffer = SL(CONTROLLER_NAME.'/yzf', $_REQUEST);
        $this->reback($buffer,1);
    }
    public function moneylog(){
        $buffer = SL(CONTROLLER_NAME.'/moneylog', $_REQUEST);
        $this->reback($buffer,1);
    }
    public function moneylogdelete(){
        $buffer = SL(CONTROLLER_NAME.'/moneylogdelete', $_REQUEST);
        $this->reback($buffer,!IS_AJAX);
    }
    public function dingdan(){
        $buffer = SL(CONTROLLER_NAME.'/dingdan', $_REQUEST);
        $this->reback($buffer,!IS_AJAX);
    }
    public function dingdansave(){
        $buffer = SL(CONTROLLER_NAME.'/dingdansave', $_REQUEST);
        $this->reback($buffer,!IS_AJAX);
    }
    public function dingdanbank(){
        $buffer = SL(CONTROLLER_NAME.'/dingdanbank', $_REQUEST);
        $this->reback($buffer,!IS_AJAX);
    }
    public function dingdanselect(){
        $buffer = SL(CONTROLLER_NAME.'/dingdanselect', $_REQUEST);
        $this->reback($buffer,!IS_AJAX);
    }
    public function dingdancf(){
        $buffer = SL(CONTROLLER_NAME.'/dingdancf', $_REQUEST);
        $this->reback($buffer,!IS_AJAX);
    }
    public function dingdanall(){
        $buffer = SL(CONTROLLER_NAME.'/dingdanall', $_REQUEST);
        $this->reback($buffer,!IS_AJAX);
    }
    public function checkneworder(){
        $buffer = SL(CONTROLLER_NAME.'/checkneworder', $_REQUEST);
        $this->reback($buffer,!IS_AJAX);
    }
}