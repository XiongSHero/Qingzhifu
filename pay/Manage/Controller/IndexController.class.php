<?php
// +----------------------------------------------------------------------
// | easy pay [ pay to easy ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2017 All rights reserved.
// +----------------------------------------------------------------------
// | Author: fengxing <QQ:51125330>
// +----------------------------------------------------------------------
namespace Manage\Controller;
class IndexController extends BaseController {
    public function index(){
        $this->assign('pageName','后台登录');
        $this->display();
    }
    public function login(){
        if(IS_POST){
            $adminLogic=SL('Admin');
            $buffer=$adminLogic->login($_REQUEST);
            if( $buffer[0]==1){
                $buffer[1]='登录成功';
                $buffer[2]=U('Main/index');
            }
            $this->reback($buffer);
        }
    }
    /**
     * 退出功能
     * @param int $who 身份，1为教师，0为学生
     * @author demo
     */
    public function loginOut(){
        $this->setCookieCode(null,null,'MANAGE');
        $this->setCookieUserID(null,null,'MANAGE');
        $this->setCookieUserName(null,null,'MANAGE');
        header('Location:'.U('/Manage'));
        exit();
    }
    /**
     * 验证码显示
     * @param $imageMsg array 验证码设置属性
     * @author demo
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