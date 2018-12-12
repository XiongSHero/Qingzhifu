<?php

// +----------------------------------------------------------------------
// | easy pay [ pay to easy ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2017 All rights reserved.
// +----------------------------------------------------------------------
// | Author: fengxing <QQ:51125330>
// +----------------------------------------------------------------------

namespace Index\Controller;

class TestController extends BaseController {

    protected $userTest; //测试用户信息

    public function __construct() {
        parent::__construct();
        global $publicData;

        $this->userTest = array(
            'notifyUrl' => $publicData['peizhi']['httpstyle'] . '://' . $_SERVER['HTTP_HOST'] . "/Test/notifyUrl", //异步回调地址，外网能访问
            'backUrl' => $publicData['peizhi']['httpstyle'] . '://' . $_SERVER['HTTP_HOST'] . "/Test/backUrl", //同步回调地址，外网能访问
            'fxid' => "2017100", //商户号
            'fxkey' => "", //商户秘钥key 从用户后台获取
            'fxloaderror' => 0 //是否开启数据记录 用于排错 0不开启 1开启
        );
        //获取后台配置的收钱账户
        $thisuserid = $publicData['peizhi']['bzjuserid'];
        if (!$thisuserid) {
            $thisuserid = '2017100';
        }

        $userBuffer = SM('User')->findData('userid,miyao', 'userid=' . $thisuserid);
        $this->userTest['fxid'] = $userBuffer['userid'];
        $this->userTest['fxkey'] = $userBuffer['miyao'];
    }

    //支付体验
    public function index() {
        if (!IS_POST) {
            global $publicData;
            $paytest = stringChange('formatMoney', $publicData['peizhi']['paytest']);
            if (empty($paytest))
                $paytest = '0.01';

            //判断是否有可用的接口
            $list = SL('Api')->getOpenApi();
            $this->assign('list', $list);
            $this->assign('paytest', $paytest);
            $this->display();
            exit();
        }

        $userid = $_REQUEST['userid'];
        $userkey = $_REQUEST['userkey'];
        $title = $_REQUEST['title'];
        $fxdesc = $_REQUEST['fxdesc'];
        $fxfee = $_REQUEST['fxfee'];
        $fxpay = $_REQUEST['fxpay'];
        $fxddstyle = $_REQUEST['fxddstyle'];
        if (empty($fxpay))
            $fxpay = 'wxwap';
        if (empty($fxfee))
            $fxfee = 1;
        if (!$title) {
            global $publicData;
            $title = $publicData['peizhi']['sitename'] . '体验';
            $fxddstyle = 3;
        }
        if (empty($fxdesc))
            $fxdesc = $this->userTest['fxid'] . '|' . $fxpay . '|' . $fxfee;

        if (!empty($userid)) {
            $userBuffer = SM('User')->findData('userid,miyao,savecode', 'userid=' . $userid);
            if ($userkey != md5($userBuffer['userid'] . $userBuffer['savecode'])) {
                $this->reback([0,
                    '数据异常，请重试。']);
            }
            $this->userTest['fxid'] = $userBuffer['userid'];
            $this->userTest['fxkey'] = $userBuffer['miyao'];
        }

        //发起支付
        session_start();
        $ddh = time() . getDingdanRand(); //商户订单号
        session('ddh', $this->userTest['fxid'] . $ddh); //session存储商户订单号

        $ip = get_client_ip(0, true);
        if (!preg_match('/^((?:(?:25[0-5]|2[0-4]\d|((1\d{2})|([1-9]?\d)))\.){3}(?:25[0-5]|2[0-4]\d|((1\d{2})|([1 -9]?\d))))$/', $ip)) {
            $ip = $_SERVER['SERVER_ADDR'];
        }
        $data = array(
            "fxid" => $this->userTest['fxid'], //商户号
            "fxddh" => $ddh, //商户订单号
            "fxdesc" => $title, //商品名
            "fxfee" => $fxfee, //支付金额 单位元
            "fxattch" => $fxdesc, //附加信息
            "fxnotifyurl" => $this->userTest['notifyUrl'], //异步回调 , 支付结果以异步为准
            "fxbackurl" => $this->userTest['backUrl'], //同步回调 不作为最终支付结果为准，请以异步回调为准
            "fxpay" => $fxpay, //支付类型 此处可选项为 微信公众号：wxgzh   微信H5网页：wxwap  微信扫码：wxsm   支付宝H5网页：zfbwap  支付宝扫码：zfbsm 等参考API
            "fxip" => $ip, //支付端ip地址
            "fxddstyle" => $fxddstyle
        );

        if(strstr($fxpay,'sm')){
            $data['fxsmstyle']=1;
        }

        //获取支付网关
        $httpBuffer = SL('Http')->getApiHttp($this->userTest['fxid']);
        $wg = '';
        if (is_array($httpBuffer[1])) {
            $apihttp = stringChange('arrayKey', $httpBuffer[1], 'jkstyle');
            $wg = $apihttp[$fxpay]['thishttp'];
        } else {
            $wg = $httpBuffer[1];
        }
        $wg.='/Pay';

        $data["fxsign"] = md5($data["fxid"] . $data["fxddh"] . $data["fxfee"] . $data["fxnotifyurl"] . $this->userTest['fxkey']); //加密
        $r = curl($wg, $data);
        $backr = json_decode($r, true); //json转数组
        $return = array();
        //验证返回信息
        if ($backr["status"] == 1) {
            //转入支付页面
            $return = [1,
                $backr["payurl"]];
        } else {
            //exit(print_r($r));
            //$return=[0,$backr['error'].print_r($r)];//输出详细信息
            if ($backr["error"])
                $return = [0,
                    $backr["error"]]; //输出错误信息
            else
                $return = [0,
                    $r]; //输出错误信息
        }
        $this->reback($return);
    }

    public function notifyUrl() {
        session_start();
        $fxid = $_REQUEST['fxid']; //商户编号
        $fxddh = $_REQUEST['fxddh']; //商户订单号
        $fxorder = $_REQUEST['fxorder']; //平台订单号
        $fxdesc = $_REQUEST['fxdesc']; //商品名称
        $fxfee = $_REQUEST['fxfee']; //交易金额
        $fxattch = $_REQUEST['fxattch']; //附加信息
        $fxstatus = $_REQUEST['fxstatus']; //订单状态
        $fxtime = $_REQUEST['fxtime']; //支付时间
        $fxsign = $_REQUEST['fxsign']; //md5验证签名串
        //获取商户编号对应key
        $userBuffer = SM('User')->findData('*', 'userid=' . $fxid);

        $mysign = md5($fxstatus . $fxid . $fxddh . $fxfee . $userBuffer['miyao']); //验证签名
        //记录回调数据到文件，以便排错
        if ($this->userTest['fxloaderror'] == 1)
            file_put_contents('./demo.txt', '异步：' . serialize($_REQUEST) . "\r\n", FILE_APPEND);

        if ($fxsign == $mysign) {
            if ($fxstatus == '1') {//支付成功
                global $publicData;
                //支付成功 更改支付状态 完善支付逻辑
                if (strstr($fxattch, 'baozhengjin')) {
                    $bzjuserid = str_replace('baozhengjin', '', $fxattch);
                    $userBuffer = SM('User')->findData('*', 'userid=' . $bzjuserid);
                    if ($fxfee == $publicData['peizhi']['baozhengjin']) {
                        if ($userBuffer['regmoney'] != $fxfee) {
                            $result = SM('User')->updateData(array(
                                'regmoney' => $fxfee), 'userid=' . $bzjuserid);
                            if ($result === false)
                                exit('fail');
                        }
                        exit('success');
                    }
                }else if (strstr($fxattch, 'recharge')) {
                    $bzjuserid = explode('@', $fxattch);
                    $dingdanBuffer = SM('Dingdan')->findData('*', 'ordernum="' . $fxid . $fxddh . '"');

                    if ($fxfee == $dingdanBuffer['totalmoney']) {
                        //判断用户的支付状态
                        if ($dingdanBuffer['status'] == 1) {
                            //添加资金变动
                            $data = array(
                                'userid' => $bzjuserid[0],
                                'leavemoney' => $userBuffer['money'],
                                'changemoney' => $dingdanBuffer['havemoney'],
                                'desc' => '充值：' . $dingdanBuffer['totalmoney'] . '元，扣除手续费后实际到账：' . $dingdanBuffer['havemoney'] . '元',
                                'style' => 1,
                            );
                            $result = SL('Pay')->moneylogadd($data);
                            exit('success');
                        }
                        exit('status fail');
                    }
                    exit('money fail');
                } else {
                    //仅为用户增加金额 在支付系统回调的时候已经为用户增加过金额了
                    exit('success');
                }
                echo 'logic fail';
            } else { //支付失败
                echo 'fail';
            }
        } else {
            echo 'sign error';
        }
        exit();
    }

    public function backUrl() {
        session_start();
        $fxid = $_REQUEST['fxid']; //商户编号
        $fxddh = $_REQUEST['fxddh']; //商户订单号
        $fxorder = $_REQUEST['fxorder']; //平台订单号
        $fxdesc = $_REQUEST['fxdesc']; //商品名称
        $fxfee = $_REQUEST['fxfee']; //交易金额
        $fxattch = $_REQUEST['fxattch']; //附加信息
        $fxstatus = $_REQUEST['fxstatus']; //订单状态
        $fxtime = $_REQUEST['fxtime']; //支付时间
        $fxsign = $_REQUEST['fxsign']; //md5验证签名串

        $mysign = md5($fxstatus . $fxid . $fxddh . $fxfee . $this->userTest['fxkey']); //验证签名
        //记录回调数据到文件，以便排错
        if ($this->userTest['fxloaderror'] == 1)
            file_put_contents('./demo.txt', '同步：' . serialize($_REQUEST) . "\r\n", FILE_APPEND);

        $orderddh = $fxid .$fxddh;
        if (empty($orderddh))
            $orderddh = session('ddh'); //获取session订单号
        if (empty($fxddh))
            $fxddh = session('ddh'); //获取session订单号

        $buffer = SM('Dingdan')->findData('*', 'ordernum="' . $orderddh . '"');
        if (!$buffer) {
            $this->reback([0,
                '订单号不存在.']);
        }

        $result = 0;
        if ($fxsign == $mysign) {
            if ($fxstatus == '1' || $buffer['status']>=1) {//支付成功
                //支付成功 转入支付成功页面
                $result = 1;
            }
        } else {
            /** 判断订单是否已经支付成功 如果不成功等待10秒刷新* */
            //验证订单号是否支付成功
            if ($buffer['status'] == 1) { //支付成功
                //跳转到支付成功后的页面
                session('ddhft', NULL);
                $result = 1;
            }
        }

        if ($result != 1) {
            //支付失败等待刷新验证
            //完善流程 刷新3次跳出刷新
            $ddhft = session('ddhft'); //订单刷新次数
            if (!empty($ddhft) && $ddhft > 2) {
                session('ddhft', NULL);
                $result = 0;
            } else {
                $ddhft = empty($ddhft) ? 1 : $ddhft + 1;
                session('ddhft', $ddhft);
                $result = 2;
            }
        }

        $this->assign('ddh', $fxddh);
        $this->assign('money', $buffer['totalmoney']);
        $this->assign('result', $result);
        $this->display('Test/result');
    }

    public function preorder() {
        $ddh = $_REQUEST['ddh']; //需要查询的订单号
        $data = array(
            "fxid" => $this->userTest['fxid'], //商户号
            "fxddh" => $ddh, //商户订单号
            "fxaction" => "orderquery"//查询动作
        );

        //获取支付网关
        $httpBuffer = SL('Http')->getApiHttpOther($this->userTest['fxid']);
        $wg = $httpBuffer[1] . '/Pay';

        $data["fxsign"] = md5($data["fxid"] . $data["fxddh"] . $data["fxaction"] . $this->userTest['fxkey']); //加密
        $r = file_get_contents($wg . "?" . http_build_query($data));
        $backr = json_decode($r, true); //json转数组
        if ($backr['fxstatus'] == 1) {
            //支付成功
            exit('订单支付成功');
        } else {
            //支付失败
            //exit(print_r($r)); //返回的详细信息
            exit('订单支付失败：' . $backr['error']); //返回的错误信息
        }
    }

}
