<?php
// +----------------------------------------------------------------------
// | easy pay [ pay to easy ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2017 All rights reserved.
// +----------------------------------------------------------------------
// | Author: fengxing <QQ:51125330>
// +----------------------------------------------------------------------
namespace Index\Controller;
use Common\Controller\DefaultController;
class BaseController extends DefaultController {
    protected $templateIndex='auto';

    public function __construct(){
        parent::__construct();
        $this->initial();
    }

    private function initial(){
        global $publicData;
        $peizhi=$publicData['peizhi'];

        //处理模板 cookie优先
        if($peizhi['templatecookie']==1){
            $templatename=cookie('templatenow');
        }else{
            $templatename=$peizhi['template'];
        }
        $this->templateIndex=$templatename;
        $alltemplate=SL('Param')->template;
        if($templatename=='auto' || empty($alltemplate[$templatename])){
            $this->templateIndex='';
        }

        //apihttp如果与本站网址不一致对非支付接口页面进行跳转
        $httpBuffer=SM('Http')->selectData('*','locked=0','id desc');
        if(!empty($httpBuffer)){
            $httpBuffer=  stringChange('arrayKey',$httpBuffer,'http');
            $http=$httpBuffer['http://'.$_SERVER['HTTP_HOST']];
            $http1=$httpBuffer['https://'.$_SERVER['HTTP_HOST']];
            if(strtolower(CONTROLLER_NAME)!='pay' && strtolower(ACTION_NAME)!='qrcode'){
                if($http && $http['hiddenhttp']){
                    header('location:'.$http['hiddenhttp']);
                    exit();
                }
                if($http1 && $http1['hiddenhttp']){
                    header('location:'.$http1['hiddenhttp']);
                    exit();
                }
            }
        }

        if($peizhi['closeweb']==1){
            exit('网站关闭。');
        }
        //检测用户登录
        if(CONTROLLER_NAME=='Home'){
            $checklogin=SL('User')->checklogin();
            $nowAction = MODULE_NAME.'/'.CONTROLLER_NAME.'/'.ACTION_NAME;
            if($checklogin[0]==0){
                if(IS_AJAX){
                    $this->reback([0,'请登录。',U('/')]);
                }else{
                    header('Location:'.U('/'));
                }
                exit();
            }
            if(empty($publicData['user'])){
                $publicData['user']=$checklogin[1];
            }

            //认证未通过
            if($peizhi['ifopenusercheck']==1 && $checklogin[1]['ifusercheck']!=2 && ('info'!=ACTION_NAME && 'loginout'!=ACTION_NAME && 'upload'!=ACTION_NAME)){
               exit('<script>location.href="'.$publicData['peizhi']['httpstyle'].'://'.$_SERVER['HTTP_HOST'].U('Index/Home/info').'";</script>');
               exit();
            }
        }


        $this->assign('config',$peizhi);
        $this->assign('sitename',$peizhi['sitename']);
        $this->assign('user',$checklogin[1]);
    }
}