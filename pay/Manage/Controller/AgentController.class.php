<?php
// +----------------------------------------------------------------------
// | easy pay [ pay to easy ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2017 All rights reserved.
// +----------------------------------------------------------------------
// | Author: fengxing <QQ:51125330>
// +----------------------------------------------------------------------
namespace Manage\Controller;
class AgentController extends BaseController {
    /**
     * 代理账单
     */
    public function fandian(){
        $buffer = SL(CONTROLLER_NAME.'/fandian', $_REQUEST);
        $this->reback($buffer,1);
    }
    /**
     * 状态
     */
    public function status(){
        $buffer = SL(CONTROLLER_NAME.'/status', $_REQUEST);
        $this->reback($buffer,!IS_AJAX);
    }
}