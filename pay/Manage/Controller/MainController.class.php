<?php
// +----------------------------------------------------------------------
// | easy pay [ pay to easy ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2017 All rights reserved.
// +----------------------------------------------------------------------
// | Author: fengxing <QQ:51125330>
// +----------------------------------------------------------------------
namespace Manage\Controller;
class MainController extends BaseController {
    public function index(){
        $buffer = SL(CONTROLLER_NAME.'/index', $_REQUEST);
        $this->reback($buffer,1);
    }
}