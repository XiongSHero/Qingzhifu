<?php
// +----------------------------------------------------------------------
// | easy pay [ pay to easy ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2017 All rights reserved.
// +----------------------------------------------------------------------
// | Author: fengxing <QQ:51125330>
// +----------------------------------------------------------------------
namespace Manage\Controller;
class UserController extends BaseController {
    /**
     * 费率
     */
    public function fl(){
        $buffer = SL(CONTROLLER_NAME.'/fl', $_REQUEST);
        $this->reback($buffer,!IS_AJAX);
    }
    public function info(){
        $buffer = SL(CONTROLLER_NAME.'/info', $_REQUEST);
        $this->reback($buffer,!IS_AJAX);
    }
    public function sendmoney(){
        $buffer = SL(CONTROLLER_NAME.'/sendmoney', $_REQUEST);
        $this->reback($buffer,!IS_AJAX);
    }
}