<?php
// +----------------------------------------------------------------------
// | easy pay [ pay to easy ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2017 All rights reserved.
// +----------------------------------------------------------------------
// | Author: fengxing <QQ:51125330>
// +----------------------------------------------------------------------
namespace Manage\Controller;
use Common\Controller\DefaultController;
class BaseController extends DefaultController {

    public function __construct(){
        parent::__construct();
        $this->initial();
    }

    private function initial(){
        //屏蔽非正常访问
        $back_path=$_COOKIE["QINGZHIFU_PATH"];
        if(empty($back_path)) $back_path=QINGZHIFU_PATH;
        if($back_path!='qingzhifu'){
            throw_exception('404');
        }

        global $publicData;
        $peizhi=$publicData['peizhi'];

        //检测用户登录
        $checklogin=SL('Admin')->checklogin();
        $nowAction = MODULE_NAME.'/'.CONTROLLER_NAME.'/'.ACTION_NAME;
        if($checklogin[0]==0 && !strstr($nowAction,'Manage/Index/')){
            header('Location:'.U('/Manage'));
        }

        $this->assign('config',$peizhi);
        $this->assign('siteName','风行后台管理系统');
        $this->assign('admin',$checklogin[1]);
    }
    public function index(){
        $buffer = SL(CONTROLLER_NAME.'/index', $_REQUEST);
        $this->reback($buffer, 1);
    }
    public function add(){
        $buffer = SL(CONTROLLER_NAME.'/add', $_REQUEST);
        $this->reback($buffer,1);
    }
    public function edit(){
        $buffer = SL(CONTROLLER_NAME.'/edit', $_REQUEST);
        $this->reback($buffer, 1);
    }
    public function save(){
        $buffer = SL(CONTROLLER_NAME.'/save', $_REQUEST);
        $this->reback($buffer);
    }
    public function delete(){
        $buffer = SL(CONTROLLER_NAME.'/delete', $_REQUEST);
        $this->reback($buffer);
    }
}