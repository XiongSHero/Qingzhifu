<?php
// +----------------------------------------------------------------------
// | easy pay [ pay to easy ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2017 All rights reserved.
// +----------------------------------------------------------------------
// | Author: fengxing <QQ:51125330>
// +----------------------------------------------------------------------
namespace Manage\Controller;
class ApiController extends BaseController {

    public function user(){
        $buffer = SL(CONTROLLER_NAME.'/user', $_REQUEST);
        $this->reback($buffer,1);
    }

    public function adduser(){
        $buffer = SL(CONTROLLER_NAME.'/adduser', $_REQUEST);
        $this->reback($buffer,1);
    }

    public function edituser(){
        $buffer = SL(CONTROLLER_NAME.'/edituser', $_REQUEST);
        $this->reback($buffer,1);
    }

    public function saveuser(){
        $buffer = SL(CONTROLLER_NAME.'/saveuser', $_REQUEST);
        $this->reback($buffer);
    }

    public function deleteuser(){
        $buffer = SL(CONTROLLER_NAME.'/deleteuser', $_REQUEST);
        $this->reback($buffer);
    }

    //切换账户接口开启
    public function userchangeopen(){
        $buffer = SL(CONTROLLER_NAME.'/userchangeopen', $_REQUEST);
        $this->reback($buffer);
    }
    //切换账户接口应用
    public function userchangechoose(){
        $buffer = SL(CONTROLLER_NAME.'/userchangechoose', $_REQUEST);
        $this->reback($buffer);
    }

    //接口检测
    public function check(){
        $buffer = SL(CONTROLLER_NAME.'/check', $_REQUEST);
        $this->reback($buffer,!IS_AJAX);
    }
    //轮询配置
    public function round(){
        $buffer = SL(CONTROLLER_NAME.'/round', $_REQUEST);
        $this->reback($buffer,!IS_AJAX);
    }
}
