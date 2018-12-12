<?php

// +----------------------------------------------------------------------
// | easy pay [ pay to easy ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2017 All rights reserved.
// +----------------------------------------------------------------------
// | Author: fengxing <QQ:51125330>
// +----------------------------------------------------------------------

namespace Index\Controller;

class PayController extends BaseController {

    //发起支付 查询订单
    public function index() {
        switch ($_REQUEST['fxaction']) {
            case 'orderquery':
                $buffer = SL('Pay/payQuery', $_REQUEST);
                if ($buffer[0] == 1) {
                    $reback = $buffer[1];
                } else {
                    $reback = array(
                        'fxstatus' => 0,
                        'error' => $buffer[1]);
                }
                break;
            case 'repay':
                $buffer = SL('Repay/paymoney', $_REQUEST);
                $reback = $buffer[1];
                break;
            case 'repayquery':
                $buffer = SL('Repay/payquery', $_REQUEST);
                $reback = $buffer[1];
                break;
            case 'money':
                $buffer = SL('User/getUserMoney', $_REQUEST);
                $reback = array();
                if ($buffer[0] == 1) {
                    $reback = array(
                        'fxid' => $_REQUEST['fxid'],
                        'fxstatus' => 1,
                        'fxmoney' => $buffer[1],
                        'fxmsg' => '查询成功'
                    );
                } else {
                    $reback = array(
                        'fxid' => $_REQUEST['fxid'],
                        'fxstatus' => 0,
                        'fxmoney' => 0,
                        'fxmsg' => $buffer[1]
                    );
                }
                $reback['fxsign'] = md5($reback['fxstatus'] . $reback['fxid'] . $reback['fxmoney']);
                break;
            default:
                $buffer = SL('Pay/payApi', $_REQUEST);
                $reback = array();
                if ($buffer[0] == 1) {
                    $reback = array(
                        'status' => 1,
                        'payurl' => $buffer[1]);
                    if($_REQUEST['fxnoback']==1){
                        header('location:'.$buffer[1]);
                        exit();
                    }
                } else {
                    $reback = array(
                        'status' => 0,
                        'error' => $buffer[1]);
                    if($_REQUEST['fxnoback']==1){
                        $this->reBack($buffer,1);
                        exit();
                    }
                }
                break;
        }
        $this->ajaxBack($reback);
    }

    //代付异步返回
    public function repay() {
        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
        $content = file_get_contents('php://input');
        file_put_contents('./repay.txt',$xml."\r\n".serialize($_GET)."\r\n".serialize($_POST)."\r\n".$content."\r\n",FILE_APPEND);

        foreach ($_REQUEST as $i => $iBuffer) {
            if (strstr(strtolower($i), '/pay/repay')) {
                $action = str_replace('/pay/repay/', '', strtolower($i));
            }
        }
        if (empty($action)) {
            $i = strtolower($_SERVER['REQUEST_URI']);
            $i = explode('?', $i);
            $action = str_replace('/pay/repay/', '', $i[0]);
        }

        $tmp=explode('/',$action);
        $action=$tmp[0];
        if(empty($action)){
            exit('action error');
        }

        $buffer = SA(ucfirst($action) . '/repaynotify', $_REQUEST);
        exit($buffer[1]); //success
    }

    //异步返回
    public function notify() {
        /* $xml='<xml><appid><![CDATA[wx74fe7d5250d0ff3a]]></appid>
          <attach><![CDATA[mytest]]></attach>
          <bank_type><![CDATA[CFT]]></bank_type>
          <cash_fee><![CDATA[100]]></cash_fee>
          <fee_type><![CDATA[CNY]]></fee_type>
          <is_subscribe><![CDATA[Y]]></is_subscribe>
          <mch_id><![CDATA[1485670872]]></mch_id>
          <nonce_str><![CDATA[lpdh262zrhfds70tjzvbkti16pns2kb8]]></nonce_str>
          <openid><![CDATA[oQcbbvz29UhQDUMBs2vU3CH2EcP0]]></openid>
          <out_trade_no><![CDATA[201710015127235225812]]></out_trade_no>
          <result_code><![CDATA[SUCCESS]]></result_code>
          <return_code><![CDATA[SUCCESS]]></return_code>
          <sign><![CDATA[5A72F3144ACE7DC29E2D0F5F03C53262]]></sign>
          <time_end><![CDATA[20171208165851]]></time_end>
          <total_fee>100</total_fee>
          <trade_type><![CDATA[JSAPI]]></trade_type>
          <transaction_id><![CDATA[4200000021201712080157149404]]></transaction_id>
          </xml>';
          $GLOBALS['HTTP_RAW_POST_DATA']=$xml;
         */
//        $aa=unserialize('a:26:{s:10:"gmt_create";s:19:"2017-12-08 20:56:44";s:7:"charset";s:5:"UTF-8";s:12:"seller_email";s:17:"2977253050@qq.com";s:7:"subject";s:10:"alipay wap";s:4:"sign";s:344:"GzqoX3sA+NsSQo9/a0UaLUPn6hoOUrvtQ6XyAkgStul6FMKCpeboGpdPS7cFSzs3sPb+70a0lNlLpd2xnQv4TwG9Mhdx12a9lxCoWvvPF7y3WfWmnmyINARThwuakZwsn2/XiAF+N8CPxZ4nWA/cLrIctSA5S+zxMTSeJQUeSyOz7xL7/IrOVEtcmISM+/ClMFGM66Vl2tN6wAab544W5wxeo6uVnyeDnw+KXmYMEAXVY2jMYufGuEgOVlhvHR6bhGd8Otheu+BVNe1wpYa8rgX6UdJEo9O0IAju3cNyjTfiXi7y6AwhOC0t1N4YhgIb2A6uxSQiG1pG/Xu9wDElJg==";s:4:"body";s:6:"mytest";s:8:"buyer_id";s:16:"2088002640641753";s:14:"invoice_amount";s:4:"0.01";s:9:"notify_id";s:34:"5c80f9a3ead76b669b7e6b46b6aac79lsh";s:14:"fund_bill_list";s:57:"[{\"amount\":\"0.01\",\"fundChannel\":\"ALIPAYACCOUNT\"}]";s:11:"notify_type";s:17:"trade_status_sync";s:12:"trade_status";s:13:"TRADE_SUCCESS";s:14:"receipt_amount";s:4:"0.01";s:16:"buyer_pay_amount";s:4:"0.01";s:6:"app_id";s:16:"2015122401036066";s:9:"sign_type";s:4:"RSA2";s:9:"seller_id";s:16:"2088611386176120";s:11:"gmt_payment";s:19:"2017-12-08 20:56:44";s:11:"notify_time";s:19:"2017-12-08 20:56:45";s:7:"version";s:3:"1.0";s:12:"out_trade_no";s:21:"201710015127377852022";s:12:"total_amount";s:4:"0.01";s:8:"trade_no";s:28:"2017120821001004750589705655";s:11:"auth_app_id";s:16:"2015122401036066";s:14:"buyer_logon_id";s:14:"511***@163.com";s:12:"point_amount";s:4:"0.00";}');
//
//        $_POST=$aa;
//        $_REQUEST=  array_merge($_REQUEST,$_POST);

        foreach ($_REQUEST as $i => $iBuffer) {
            if (strstr(strtolower($i), '/pay/notify')) {
                $action = str_replace('/pay/notify/', '', strtolower($i));
            }
        }
        if (empty($action)) {
            $i = strtolower($_SERVER['REQUEST_URI']);
            $i = explode('?', $i);
            $action = str_replace('/pay/notify/', '', $i[0]);
        }

        $tmp=explode('/',$action);
        $action=$tmp[0];
        if(empty($action)){
            exit('action error');
        }

        $buffer = SA(ucfirst($action) . '/notify', $_REQUEST);
        exit($buffer[1]); //success
    }

    //同步返回
    public function backurl() {
        foreach ($_REQUEST as $i => $iBuffer) {
            if (strstr(strtolower($i), '/pay/backurl')) {
                $action = str_replace('/pay/backurl/', '', strtolower($i));
            }
        }
        if (empty($action)) {
            $i = strtolower($_SERVER['REQUEST_URI']);
            $i = explode('?', $i);
            $action = str_replace('/pay/backurl/', '', $i[0]);
        }

        $tmp=explode('/',$action);
        $action=$tmp[0];
        if(empty($action)){
            exit('action error');
        }

        $buffer = SA(ucfirst($action) . '/backurl', $_REQUEST);
        if($buffer[0]==1){
            header('Location:' . $buffer[1]); //跳转
        }else{
            $this->reback($buffer);
        }
        exit();
    }

    /**
     * 公众号类H5支付
     */
    public function jsapi() {
        $buffer = SA(ucfirst($_REQUEST['style']))->jsapi($_REQUEST);
        $this->reback($buffer, 1);
    }

    /**
     * 跳转
     */
    public function go() {
        $http = $_GET['u'];
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
            header("Content-type: text/html; charset=utf-8");
            exit('<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>请使用浏览器打开。');
        }else{
            exit('<script>location.href="' . $http . '";</script>');
        }
    }

    /**
     * 提交
     */
    public function formpost() {
        $http = $_GET;
        $tjurl = $http['wg'];
        unset($http['wg']);
        header("Content-type: text/html; charset=utf-8");
        $str = '<form id="Form1" name="Form1" method="post" action="' . $tjurl . '">';
        foreach ($http as $key => $val) {
            if ($val==='')
                continue;
            $str = $str . '<input type="hidden" name="' . $key . '" value=\'' . stripslashes($val) . '\'/>';
        }
        //$str = $str . '<input type="submit" style="width:20%;height:40px;" value="确认支付"/>';
        $str = $str . '</form>';
        $str = $str . '<script>';
        $str = $str . 'document.Form1.submit();';
        $str = $str . '</script>';
        exit($str);
    }

    //商户二维码
    public function qrcode() {
        $userid = $_GET['uid'];
        $userkey = $_GET['key'];
        $arr = unserialize(S('userinfo' . $userkey));
        if (empty($arr['str']))
            $str = '快捷支付';
        else
            $str = $arr['str'];

        //过滤接口
        //判断是否有可用的接口
        $list = SL('Api')->getOpenApi();

        $leave = 'wap';
        $display='none';
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
            $leave = 'gzh';
        } else if (strpos($_SERVER['HTTP_USER_AGENT'], 'Alipay') !== false) {
            $leave = 'zfbwap';
        }else{
            $display='block';
        }

        foreach ($list as $i => $iList) {
            if (!strstr($iList['jkstyle'], $leave))
                unset($list[$i]);
        }

        $this->assign('list', $list);
        $this->assign('display', $display);
        $this->assign('userid', $userid);
        $this->assign('userkey', $userkey);
        $this->assign('pageName', $str);
        $this->display();
    }

    //收银台
    public function gateway() {
        $http = $_GET;
        $tjurl=$http['wg'];
        unset($http['wg']);
        header("Content-type: text/html; charset=utf-8");
        foreach ($http as $key => $val) {
            if(empty($val)) continue;
            $str = $str . '<input type="hidden" name="' . $key . '" value="' . $val . '"/>';
        }

        //获取银行数据
        $buffer=SM('Bank')->selectData('*','status=0','orderid asc,id asc');

        $this->assign('list',$buffer);
        $this->assign('wg',$tjurl);
        $this->assign('hidden',$str);
        $this->assign('data',$http);
        $this->display();
    }

    //二维码
    public function ewm() {
        $ddh = $_GET['ddh'];
        $qr = $_GET['qr'];
        $md = $_GET['md'];

        //验证数据完整性
        $sign=md5($ddh.$qr.C('FX_QRCODE_KEY'));
        if($sign!=$md){
            $this->reback([0,'二维码信息有误，请重新获取支付链接。'], 1);
            exit();
        }

        $buffer=SM('Dingdan')->findData('*','ordernum="'.$ddh.'"');
        $pzBuffer=SM('Jiekoupeizhi')->findData('*','pzid="'.$buffer['pzid'].'"');
        $jkBuffer=SM('Jiekou')->findData('*','jkstyle="'.$buffer['jkstyle'].'"');
        $buffer['pzstyle']=$pzBuffer['style'];
        $buffer['shutname']=$jkBuffer['jkname'];
        $buffer['addtime'] = stringChange('formatDateTime', $buffer['addtime']);
        $buffer['statusname'] = '等待支付';

        //加密数据供获取订单状态使用
        $buffer['md']=md5($ddh.$buffer['userid'].C('FX_QRCODE_KEY'));

        //已经支付 转入支付成功界面
        if($buffer['status']>0){
            $params=array('ddh'=>$ddh);
            $buffer = SA(ucfirst($pzBuffer['style']) . '/backurl', $params);
            if($buffer[0]==1){
                header('Location:' . $buffer[1]); //跳转
            }else{
                $this->reback($buffer);
            }
        }

        $this->assign('data',$buffer);
        $this->assign('qr',$qr);
        $this->display();
    }

    //获取订单号状态
    public function getddhstatus() {
        $ddh=$_REQUEST['ddh'];
        $md=$_REQUEST['md'];
        $buffer=SM('Dingdan')->findData('userid,status','ordernum="'.$ddh.'"');

        $mymd=md5($ddh.$buffer['userid'].C('FX_QRCODE_KEY'));
        if($mymd!=$md){
            $buffer=[0,'数据异常'];
        }else{
            $buffer=[1,$buffer];
        }

        $this->reback($buffer,!IS_AJAX);
    }
}
