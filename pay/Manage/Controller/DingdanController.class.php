<?php

// +----------------------------------------------------------------------
// | easy pay [ pay to easy ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2017 All rights reserved.
// +----------------------------------------------------------------------
// | Author: fengxing <QQ:51125330>
// +----------------------------------------------------------------------

namespace Manage\Controller;

class DingdanController extends BaseController {

    public function kou() {
        $buffer = SL(CONTROLLER_NAME . '/kou', $_REQUEST);
        $this->reback($buffer, 1);
    }

    public function wei() {
        $buffer = SL(CONTROLLER_NAME . '/wei', $_REQUEST);
        $this->reback($buffer, 1);
    }

    public function notify() {
        $buffer = SL(CONTROLLER_NAME . '/notify', $_REQUEST);
        $this->reback($buffer, 1);
    }

    public function notifydelete() {
        $buffer = SL(CONTROLLER_NAME . '/notifydelete', $_REQUEST);
        $this->reback($buffer, !IS_AJAX);
    }

    public function notifyedit() {
        $buffer = SL(CONTROLLER_NAME . '/notifyedit', $_REQUEST);
        $this->reback($buffer, !IS_AJAX);
    }

    public function pay() {
        $buffer = SL(CONTROLLER_NAME . '/pay', $_REQUEST);
        $this->reback($buffer, !IS_AJAX);
    }

    public function payedit() {
        $buffer = SL(CONTROLLER_NAME . '/payedit', $_REQUEST);
        $this->reback($buffer, !IS_AJAX);
    }

    public function paydelete() {
        $buffer = SL(CONTROLLER_NAME . '/paydelete', $_REQUEST);
        $this->reback($buffer, !IS_AJAX);
    }

    public function editall() {
        $buffer = SL(CONTROLLER_NAME . '/editall', $_REQUEST);
        $this->reback($buffer, 1);
    }

    public function savetz() {
        $buffer = SL(CONTROLLER_NAME . '/savetz', $_REQUEST);
        $this->reback($buffer, !IS_AJAX);
    }

    public function dingdancheck() {
        $buffer = SL(CONTROLLER_NAME . '/dingdancheck', $_REQUEST);
        $this->reback($buffer, !IS_AJAX);
    }

    //内部处理数据 改变订单表配置id
    public function changeDDPZ() {
        $buffer = SM('Dingdan')->groupData('count(ddid),zjid', 'pzid=0', 'zjid asc');
        $zjBuffer = SM('Jiekouzj')->selectData('*', '1=1');
        $zjBuffer = stringChange('arrayKey', $zjBuffer, 'zjid');
        foreach ($buffer as $iBuffer) {
            if($zjBuffer[$iBuffer['zjid']]){
                SM('Dingdan')->updateData(array('pzid'=>$zjBuffer[$iBuffer['zjid']]['pzid']),'pzid=0 and zjid='.$iBuffer['zjid']);
            }
        }
        exit('success');
    }

}
