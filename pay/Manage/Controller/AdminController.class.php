<?php
// +----------------------------------------------------------------------
// | easy pay [ pay to easy ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2017 All rights reserved.
// +----------------------------------------------------------------------
// | Author: fengxing <QQ:51125330>
// +----------------------------------------------------------------------
namespace Manage\Controller;
class AdminController extends BaseController {
    /**
     * 修改密码
     */
    public function pass(){
        $buffer = SL(CONTROLLER_NAME.'/pass', $_REQUEST);
        $this->reback($buffer,!IS_AJAX);
    }
}