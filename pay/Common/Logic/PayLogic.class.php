<?php

// +----------------------------------------------------------------------
// | easy pay [ pay to easy ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2017 All rights reserved.
// +----------------------------------------------------------------------
// | Author: fengxing <QQ:51125330>
// +----------------------------------------------------------------------

namespace Common\Logic;

class PayLogic extends BaseLogic {

    protected $moduleName = '账单';
    public $zt = array(
        0 => '未支付',
        1 => '已支付',
        2 => '冻结',
        3 => '取消');
    public $paylogzt = array(
        1 => '充值',
        2 => '提现',
        3 => '代理佣金');
    public $daifuzt = array(
        0 => '未提交',
        1 => '已提交',
        2 => '失败',
        3 => '成功');
    public $fanhuizt = array(
        0 => '发起支付',
        1 => '支付成功',
        2 => '支付失败');

    /**
     * 列表
     */
    public function index($request) {
        $map = array();
        $data = ' 1=1 ';
        //高级查询
        if ($request['ddh']) {
            $map['ddh'] = $request['ddh'];
            $data .= ' AND ddh = "' . $request['ddh'] . '" ';
        }
        if ($request['userid']) {
            $map['userid'] = $request['userid'];
            $data .= ' AND userid = "' . $request['userid'] . '" ';
        }
        if (!is_numeric($request['status'])) {
            $request['status'] = 0;
            $_REQUEST['status'] = 0;
        }
        $map['status'] = $request['status'];
        $data .= ' AND status ="' . $request['status'] . '" ';

        $start = $request['start'];
        if (strstr($start, '-')) {
            $start = strtotime($start);
        }
        $end = $request['end'];
        if (strstr($end, '-')) {
            $end = strtotime($end);
        }
        if ($start) {
            if (empty($end))
                $end = time();
            $map['start'] = $start;
            $map['end'] = $end;
            $request['start'] = date('Y-m-d', $start);
            $request['end'] = date('Y-m-d', $end);
            $data .= ' AND addtime between ' . ($start) . ' and ' . ($end) . ' ';
        }

        $pay = SM('Pay');
        $count = $pay->selectCount(
                $data, 'id'); // 查询满足要求的总记录
        // 进行分页数据查询 注意limit方法的参数要使用Page类的属性
        $perpage = C('FX_PERPAGE'); //每页行数
        $page = page($count, $request['p'], $perpage) . ',' . $perpage;
        $list = $pay->pageData('*', $data, 'id DESC', $page);
        foreach ($list as $i => $iList) {
            if (empty($list[$i]['notifyurl']) || $list[$i]['tz'] == 0) {
                $list[$i]['tzname'] = '-';
            } else {
                if ($list[$i]['tz'] == 1)
                    $list[$i]['tzname'] = '通知失败';
                if ($list[$i]['tz'] == 2)
                    $list[$i]['tzname'] = '通知成功';
            }

            $list[$i]['statusname'] = $this->zt[$iList['status']];
            $list[$i]['daifustatusname'] = $this->daifuzt[$iList['daifustatus']];
            $list[$i]['addtime'] = stringChange('formatDateTime', $iList['addtime']);
            $list[$i] = stringChange('formatMoneyByArray', $list[$i], array(
                'money',
                'dffl'));
        }
        $pageList = $this->pageList($count, $perpage, $map);

        $titlename = $this->zt[$request['status']];

        //统计数据
        $times = strtotime(date('Y-m-d', time()));
        $tj = array();
        $tj['today'] = $pay->sumData('money', 'status=0 and addtime>=' . $times);
        $tj['paytoday'] = $pay->sumData('money', 'status=1 and addtime>=' . $times);
        $tj['all'] = $pay->sumData('money', $data);
        $tj['payall'] = $pay->sumData('money', $data . ' and status=1');
        foreach ($tj as $i => $iTj) {
            if (empty($iTj))
                $tj[$i] = 0;
        }
        $tj = stringChange('formatMoneyByArray', $tj, array(
            'today',
            'paytoday',
            'all',
            'payall'));

        $params = array(
            'zt' => $this->zt,
            'tj' => $tj,
            'list' => $list,
            'page' => $pageList,
            'pageName' => $titlename . $this->moduleName . '管理'
        );
        return [1,
            $params];
    }

    /**
     * 保存
     */
    public function save($request) {
        $id = $request['id']; //获取数据标识
        $status = $request['status']; //获取数据标识

        if (empty($id) || !is_numeric($status)) {
            return [0,
                '数据标识不能为空！'];
        }

        if (!is_array($id)) {
            $id = explode(',', $id);
        }

        //当数据状态为3的时候不能再修改状态
        $buffer = SM('Pay')->selectData('*', 'id in (' . implode(',', $id) . ')');
        foreach ($buffer as $iBuffer) {
            if ($iBuffer['status'] == 3) {
                return [0,
                    '已取消的数据不能更改状态！'];
            }
        }

        //状态为3的时候返还用户金额
        //判断订单状态 代付中不能返还，代付成功不能返还

        if ($status == 3) {
            $buffer = SM('Pay')->findData('*', 'id=' . $id[0]);
            if ($buffer['daifustatus'] == 1 || $buffer['daifustatus'] == 3) {
                return [0,
                    '代付提交中和代付成功后不能取消支付。'];
            }
        }

        $pay = SM('Pay');
        $pay->dbStartTrans(); //开始事务

        $flag = true;
        $flag = $pay->updateData(array(
            'status' => $status), 'id in (' . implode(',', $id) . ')');

        //为用户增加金额
        if ($status == 3 && $buffer['money'] > 0) {
            $userBuffer = SM('User')->findData('*', 'userid="' . $buffer['userid'] . '"');
            $changemoney = ($buffer['money'] + $buffer['dffl']);
            $result = SM('User')->conAddData('money=money+' . $changemoney, 'userid=' . $buffer['userid'], 'money');
            if ($result === false)
                $flag = false;
            $result = SM('User')->conAddData('tx=tx-' . $buffer['money'], 'userid=' . $buffer['userid'], 'tx');
            if ($result === false)
                $flag = false;

            //添加资金变动
            $data = array(
                'userid' => $buffer['userid'],
                'leavemoney' => $userBuffer['money'] + $changemoney,
                'changemoney' => $changemoney,
                'desc' => '提现取消返还金额：' . $changemoney . '元',
                'style' => 2,
                'ddh' => $buffer['ddh'] . '_' . $buffer['id'],
            );
            $result = $this->moneylogadd($data);
            if ($result === false)
                $flag = false;
        }

        //处理事务
        if ($flag === false) {
            $pay->dbRollback();
            return [0,
                '修改失败！'];
        } else {
            $pay->dbCommit();

            //订单状态成功或失败发送异步通知


            $this->adminLog($this->moduleName, '修改账单状态id为【' . implode(',', $id) . '】的数据');
            return [1,
                '修改成功！'];
        }
    }

    /**
     * 已支付账单
     */
    public function yzf($request) {
        header('Location:' . U('Pay/index', array(
                    'status' => 1)));
        exit();
    }

    /**
     * 代付查看订单
     */
    public function dingdan($request) {
        $id = $request['id']; //获取数据标识

        if (empty($id)) {
            return [0,
                '数据标识不能为空！'];
        }

        $payModel = SM('Pay');
        $row = $payModel->findData('*', 'id=' . $request['id']);
        $row['info'] = '开户名:' . $row['realname'] . ' 账户:' . $row['ka'] . ' 开户行:' . $row['address'];
        $row['addtime'] = date('Y-m-d H:i:s', $row['addtime']);
        $row['statusname'] = $this->zt[$row['status']];
        $row['daifustatusname'] = $this->daifuzt[$row['daifustatus']];
        $row = stringChange('formatMoneyByArray', $row, array(
            'money'));

        $payother = SM('Jiekoupeizhi')->selectData('*', 'ifrepay=1', 'pzid desc');
        $payotherKey = stringChange('arrayKey', $payother, 'pzid');

        $list = SM('PayDingdan')->selectData('*', 'payid=' . $request['id'], 'id desc');
        foreach ($list as $i => $iList) {
            $list[$i]['statusname'] = $this->fanhuizt[$list[$i]['status']];
            if ($payotherKey[$list[$i]['pzid']])
                $list[$i]['paybankname'] = SA(ucfirst($payotherKey[$list[$i]['pzid']]['style']))->paybank[$list[$i]['paybank']];
            $list[$i]['pzname'] = $payotherKey[$list[$i]['pzid']]['pzname'];
            $list[$i]['addtime'] = stringChange('formatDateTime', $iList['addtime']);
            $list[$i]['paytime'] = stringChange('formatDateTime', $iList['paytime']);
        }

        $banklist = '';
        if ($payother) {
            $banklist = SA(ucfirst($payother[0]['style']))->paybank;
        }

        $params = array(
            'edit' => $row,
            'list' => $list,
            'payother' => $payother,
            'paybank' => $banklist,
            'act' => 'edit',
            'pageName' => '代付管理'
        );
        return [1,
            $params,
            'Pay/dingdan'];
    }

    /**
     * 代付提交
     */
    public function dingdansave($request) {
        $id = $request['id']; //获取数据标识
        $pzid = $request['pzid'];
        $paybank = $request['paybank'];

        if (empty($paybank))
            $paybank = '';

        if (empty($id)) {
            return [0,
                '数据标识不能为空！'];
        }
        if (!is_numeric($pzid)) {
            return [0,
                '请选择代付机构！'];
        }

        $result = $this->dfSave($request);
        if ($result[0] === 0) {
            return $result;
        }

        return [1,
            $result[1],
            U('Manage/Pay/dingdan/id/' . $id)];
    }

    /**
     * 代付提交
     */
    public function dfSave($request) {
        $id = $request['id']; //获取数据标识
        $pzid = $request['pzid'];
        $paybank = $request['paybank'];
        $ifnotify = $request['ifnotify'];
        if (empty($ifnotify)) {
            $ifnotify = 0; //发送异步
        }

        if (empty($paybank))
            $paybank = '';

        $pzBuffer = SM('Jiekoupeizhi')->findData('*', 'pzid="' . $pzid . '" and ifrepay=1');
        if (!$pzBuffer) {
            return [0,
                '代付账户未开启或不存在！'];
        }

        $payBuffer = SM('Pay')->findData('*', 'id="' . $id . '" and status=0 and (daifustatus=0 or daifustatus=2)');
        if (!$payBuffer) {
            return [0,
                '用户支付信息不存在或已支付！'];
        }
        //判断支付订单是否存在
        $ddBuffer = SM('PayDingdan')->findData('*', 'payid=' . $id,'id desc');
        if ($ddBuffer['status'] == 1 || $payBuffer['daifustatus'] == 3) {
            return [0,
                '代付状态为成功，请不要重复打款！'];
        }

//        if (!$ddBuffer) {
        $ddh = date("YmdHis") . rand(1000, 9999);
        //写入代付订单表
        $data = array(
            'ddh' => $ddh,
            'addtime' => time(),
            'payid' => $id,
            'pzid' => $pzid,
            'status' => 0,
            'paybank' => $paybank,
            'paytime' => 0
        );
        SM('PayDingdan')->insertData($data);
//        } else {
//            $ddh = $ddBuffer['ddh'];
//        }

        global $publicData;
        $data = array(
            'ddh' => $ddh,
            'paybuffer' => $payBuffer,
            'params' => unserialize($pzBuffer['params']),
            'paybank' => $paybank,
            'fxnotifyurl' => $publicData['peizhi']['httpstyle'] . '://' . $_SERVER['HTTP_HOST'] . "/Pay/repay/" . $pzBuffer['style'], //服务端返回地址
            'ifnotify' => $ifnotify,
        );
        $result = SA(ucfirst($pzBuffer['style']))->repay($data);
        return $result;
    }

    /**
     * 代付获取银行
     */
    public function dingdanbank($request) {
        $pzBuffer = SM('Jiekoupeizhi')->findData('*', 'pzid="' . $request['payother'] . '"');
        if (!$pzBuffer) {
            return [0,
                '配置数据不存在，请刷新重试。'];
        }
        $buffer = SA(ucfirst($pzBuffer['style']))->paybank;
        $result = array();
        foreach ($buffer as $i => $iBuffer) {
            $result[] = array(
                'code' => $i,
                'name' => $iBuffer,
            );
        }
        return [1,
            $result];
    }

    /**
     * 发起支付
     * $check pzid支付id
     */
    public function payApi($request, $check = '') {
        //过滤request
        $request=array(
            'fxid'=>$request['fxid'],
            'fxddh'=>$request['fxddh'],
            'fxdesc'=>$request['fxdesc'],
            'fxfee'=>$request['fxfee'],
            'fxattch'=>$request['fxattch'],
            'fxnotifyurl'=>$request['fxnotifyurl'],
            'fxbackurl'=>$request['fxbackurl'],
            'fxpay'=>$request['fxpay'],
            'fxsign'=>$request['fxsign'],
            'fxip'=>$request['fxip'],
            'fxsmstyle'=>$request['fxsmstyle'],
            'fxbankcode'=>$request['fxbankcode'],
            'fxfs'=>$request['fxfs'],
            'fxuserid'=>$request['fxuserid'],
            'fxddstyle'=>$request['fxddstyle'],
            'fxnotifystyle'=>$request['fxnotifystyle'],
            'jumpflag'=>$request['jumpflag'],
        );

        $fxid = $request['fxid'];
        $fxddh = $request['fxddh'];
        $fxdesc = $request['fxdesc'];
        $fxfee = $request['fxfee'];
        $fxattch = $request['fxattch'];
        $fxnotifyurl = $request['fxnotifyurl'];
        $fxbackurl = $request['fxbackurl'];
        $fxpay = $request['fxpay'];
        $fxsign = $request['fxsign'];
        $fxip = $request['fxip'];
        $fxsmstyle = $request['fxsmstyle'];
        $fxbankcode = $request['fxbankcode'];
        $fxfs = $request['fxfs'];
        $fxuserid = $request['fxuserid'];
        $fxddstyle = $request['fxddstyle']; //0普通订单 1充值订单 2保证金 3测试体验订单 4商户二维码
        $fxnotifystyle = $request['fxnotifystyle']; //异步数据类型 默认1表单数据 2json
        $jumpflag = $request['jumpflag'];
        if (empty($fxddstyle) || !is_numeric($fxddstyle))
            $fxddstyle = 0;

        if (empty($fxid)) {
            $msg = '参数格式有误。请尝试用html表单提交。';
            $this->paylog($request, $msg);
            return [0,
                $msg];
        }

        //判断商户号 key是否存在
        $userBuffer = SM('User')->findData('*', 'userid="' . $fxid . '"');
        if (!$userBuffer || $userBuffer['status'] == 1) {
            $msg = '商户号错误。';
            $this->paylog($request, $msg);
            return [0,
                $msg];
        }
        $fxkey = $userBuffer['miyao'];

        //判断订单长度
        if (strlen($fxddh) > 22) {
            $msg = '订单号长度必须小于22位。';
            $this->paylog($request, $msg);
            return [0,
                $msg];
        }

        if (empty($fxfee) || !is_numeric($fxfee * 100) || $fxfee <= 0) {
            $msg = '支付金额有误。';
            $this->paylog($request, $msg);
            return [0,
                $msg];
        }

        //判断回调地址是否是http
        if (!checkString('checkIfHttp', $fxnotifyurl) || !checkString('checkIfHttp', $fxbackurl)) {
            $msg = '同步、异步回调网址有误。';
            $this->paylog($request, $msg);
            return [0,
                $msg];
        }

        $ddhfull = $fxid . $fxddh;
        $ddhmd5 = substr(md5($ddhfull), 8, 16);

        //判断签名是否正确 商务号+商户订单号+支付金额+异步通知地址+商户秘钥
        if ($fxsign != md5($fxid . $fxddh . $fxfee . $fxnotifyurl . $fxkey)) {
            $msg = '签名错误。';
            $this->paylog($request, $msg);
            return [0,
                $msg];
        }

        //判断订单号重复
        $ddBuffer = SM('Dingdan')->findData('*', 'ordernum="' . $ddhfull . '"');
        if ($ddBuffer['status'] > 0) {
            $msg = '订单已支付。';
            $this->paylog($request, $msg);
            return [0,
                $msg];
        }
//        if ($ddBuffer) {
//            $msg = '订单号重复，请更换后重试。';
//            $this->paylog($request, $msg);
//            return [0,
//                $msg];
//        }

        if (strstr($fxbackurl, '?') !== false) {
            $msg = '同步地址不能带问号，请确认。';
            $this->paylog($request, $msg);
            return [0,
                $msg];
        }

        //验证域名
        $http = array(
            '',
            $fxbackurl,
            $fxnotifyurl
        );
        $msg = array(
            '接入来源域名未审核。',
            '同步回调域名未审核。',
            '异步回调域名未审核。'
        );
        $result = SL('Userhttp')->checkHttp($fxid, $userBuffer['ifopenuserhttp'], $http, $msg);
        if ($result[0] !== 1) {
            $this->paylog($request, $result[1]);
            return [0,
                $result[1]];
        }

        //判断自动切换账户
        global $publicData;
        $peizhi = $publicData['peizhi'];

        if (empty($check)) { //非检测模式
            //pay是否在允许范围内 接口及配置都有数据
            $jiekou = SM('Jiekou')->selectData('*', '1=1', 'jkid asc');
            $jiekoubuffer = stringChange('arrayKey', $jiekou, 'jkstyle');
            if (!$jiekoubuffer[$fxpay]) {
                $msg = '请求类型有误。';
                $this->paylog($request, $msg);
                return [0,
                    $msg];
            }

            if ($jiekoubuffer[$fxpay]['ifopen'] != 1) {
                $msg = '该请求类型通道关闭。';
                $this->paylog($request, $msg);
                return [0,
                    $msg];
            }

            //判断当前域名是否是用户可用域名
            $httpBuffer = SL('Http')->getApiHttp($fxid);
            $thishttp = '';
            if (is_array($httpBuffer[1])) {
                $apihttp = stringChange('arrayKey', $httpBuffer[1], 'jkstyle');
                $thishttp = $apihttp[$fxpay]['thishttp'];
            } else {
                $thishttp = $httpBuffer[1];
            }
            $http = 'http://' . $_SERVER['HTTP_HOST'];
            $http1 = 'https://' . $_SERVER['HTTP_HOST'];
            if ($http != $thishttp && $http1 != $thishttp) {
                $msg = '请求域名有误。' . $_SERVER['HTTP_HOST'];
                $this->paylog($request, $msg);
                return [0,
                    $msg];
            }

            $out = 1; //判断条件是否继续 默认1继续 0跳出用于zjbuffer的获取
            //银行绑定账户高于个性设定
            if (strstr($fxpay, 'bank') !== false && !empty($fxbankcode)) {
                $bankBuffer = SM('Bank')->findData('*', 'bankcode="' . $fxbankcode . '"');
                if ($bankBuffer['pzid']) {
                    $zjbuffer = SM('Jiekouzj')->findData('*', 'jkid="' . $jiekoubuffer[$fxpay]['jkid'] . '" and pzid="' . $bankBuffer['pzid'] . '" and ifopen=1');
                    $out = 0;
                }
            }

            //获取用户配置指定接口信息
            $jkuser = SM('JiekouUser')->findData('*', 'userid=' . $fxid . ' and jkid=' . $jiekoubuffer[$fxpay]['jkid']);
            if ($jkuser && !empty($jkuser['pzid'])) {
                $zjbuffer = SM('Jiekouzj')->findData('*', 'jkid="' . $jiekoubuffer[$fxpay]['jkid'] . '" and pzid="' . $jkuser['pzid'] . '" and ifopen=1');
                $out = 0;
            }

            //获取一个对接的支付接口用于支付，如果开启轮询则不使用自定义应用接口
            if ($out && $jiekoubuffer[$fxpay]['ifround'] != 1) {
                $zjbuffer = SM('Jiekouzj')->findData('*', 'jkid="' . $jiekoubuffer[$fxpay]['jkid'] . '" and ifopen=1 and ifchoose=1');
                $out = 0;
            }

            if ($out) {
                $list = unserialize($jiekoubuffer[$fxpay]['list']);

                if (count($list) > 1) {
                    //排除用户不能使用的接口 单次支付金额超出范围 支付总限额
                    $jkzjArray = array();
                    foreach ($list as $i => $iList) {
                        $jkzjArray[] = $i;
                    }
                    $zjBufferArray = SM('Jiekouzj')->selectData('*', 'zjid in (' . implode(',', $jkzjArray) . ')');

                    $errormsg = '';
                    foreach ($zjBufferArray as $i => $zjbuffer) {
                        //接口限额
                        if ($zjbuffer['jetotal'] > 0 && $zjbuffer['jetotal'] != '0.00') {
                            $today = strtotime(date('Y-m-d'));
                            if ($zjbuffer['today'] != $today) {
                                SM('Jiekouzj')->updateData(['jetoday' => 0,
                                    'today' => $today], ['zjid' => $zjbuffer['zjid']]);
                                $zjbuffer['jetoday'] = 0;
                                $zjbuffer['today'] = strtotime(date('Y-m-d'));
                            }
                            if (!empty($zjbuffer['jetotal']) && $zjbuffer['jetotal'] != '0.00' && $zjbuffer['jetoday'] + $fxfee >= $zjbuffer['jetotal']) {
                                if (!$errormsg)
                                    $errormsg = '金额受限，接口单日限额' . $zjbuffer['jetotal'] . '元。';
                                unset($list[$zjbuffer['zjid']]);
                            }
                        }

                        //最小支付金额
                        if ($zjbuffer['je'] > $fxfee) {
                            $errormsg = '金额受限，最小支付金额' . $zjbuffer['je'] . '元。';
                            unset($list[$zjbuffer['zjid']]);
                        }
                        //最大支付金额
                        if (!empty($zjbuffer['jemax']) && $zjbuffer['jemax'] != '0.00' && $zjbuffer['jemax'] < $fxfee) {
                            $errormsg = '金额受限，最大支付金额' . $zjbuffer['jemax'] . '元。';
                            unset($list[$zjbuffer['zjid']]);
                        }
                    }
                    if (empty($list)) {
                        $this->paylog($request, $errormsg);
                        return [0,
                            $msg];
                    }
                    //获取支付接口 按照权重
                    $rand = array();
                    foreach ($list as $i => $iList) {
                        $rand = array_merge($rand, array_fill(count($rand), $iList['power'], $i));
                    }
                    $zjBufferArray = stringChange('arrayKey', $zjBufferArray, 'zjid');
                    $zjid = $rand[rand(0, count($rand) - 1)];
                    $zjbuffer = $zjBufferArray[$zjid];
                } else {
                    foreach ($list as $i => $iList) {
                        $zjbuffer = SM('Jiekouzj')->findData('*', 'zjid="' . $i . '"');
                    }
                }
            }

            if (!$zjbuffer) {
                $msg = '该请求类型暂时不可用。';
                $this->paylog($request, $msg);
                return [0,
                    $msg];
            }

            if ($jiekoubuffer[$fxpay]['ifround'] != 1) {
                $changetime = $peizhi['changeapitime'];
                if ($changetime > 0) {
                    if (empty($zjbuffer['changetime'])) {
                        SM('Jiekouzj')->updateData(array(
                            'changetime' => (time() + 60 * $changetime)), 'zjid=' . $zjbuffer['zjid']);
                    } else {
                        //支付时间
                        $tmpBuffer = SM('Dingdan')->findData('*', 'status>0 and jkstyle="' . $fxpay . '"', 'addtime desc');
                        if ($zjbuffer['changetime'] < time() && $tmpBuffer['addtime'] < (time() + 60 * $changetime)) {
                            $tmpBuffer2 = SM('Jiekouzj')->selectData('*', 'jkid=' . $zjbuffer['jkid'] . ' and ifopen=1 and ifchoose!=1', 'zjid asc');
                            $newid = $zjbuffer['zjid'];
                            if ($tmpBuffer2) {
                                foreach ($tmpBuffer2 as $iBuffer2) {
                                    if ($newid != $zjbuffer['zjid'])
                                        continue;
                                    if ($iBuffer2['zjid'] > $zjbuffer['zjid']) {
                                        $newid = $iBuffer2['zjid'];
                                        $newzjbuffer = $iBuffer2;
                                    }
                                }
                                if ($newid == $zjbuffer['zjid']) {
                                    $newid = $tmpBuffer2[0]['zjid'];
                                    $newzjbuffer = $tmpBuffer2[0];
                                }
                            }
                            if ($newid != $zjbuffer['zjid']) {
                                SM('Jiekouzj')->updateData(array(
                                    'changetime' => 0,
                                    'ifchoose' => 0), 'zjid=' . $zjbuffer['zjid']);
                                SM('Jiekouzj')->updateData(array(
                                    'changetime' => (time() + 60 * $changetime),
                                    'ifchoose' => 1), 'zjid=' . $newid);
                                $zjbuffer = $newzjbuffer;
                            }
                        }
                    }
                }
            }

            //最小支付金额
            if ($zjbuffer['je'] > $fxfee) {
                $msg = '金额受限，最小支付金额' . $zjbuffer['je'] . '元。';
                $this->paylog($request, $msg);
                return [0,
                    $msg];
            }
            //最大支付金额
            if (!empty($zjbuffer['jemax']) && $zjbuffer['jemax'] != '0.00' && $zjbuffer['jemax'] < $fxfee) {
                $msg = '金额受限，最大支付金额' . $zjbuffer['jemax'] . '元。';
                $this->paylog($request, $msg);
                return [0,
                    $msg];
            }

            //接口限额
            if ($zjbuffer['jetotal'] > 0 && $zjbuffer['jetotal'] != '0.00') {
                $today = strtotime(date('Y-m-d'));
                if ($zjbuffer['today'] != $today) {
                    SM('Jiekouzj')->updateData(['jetoday' => 0,
                        'today' => $today], ['zjid' => $zjbuffer['zjid']]);
                    $zjbuffer['jetoday'] = 0;
                    $zjbuffer['today'] = strtotime(date('Y-m-d'));
                }
                if (!empty($zjbuffer['jetotal']) && $zjbuffer['jetotal'] != '0.00' && $zjbuffer['jetoday'] + $fxfee >= $zjbuffer['jetotal']) {
                    $msg = '金额受限，接口单日限额' . $zjbuffer['jetotal'] . '元。';
                    $this->paylog($request, $msg);
                    return [0,
                        $msg];
                }
            }

            //判断用户接口权限 用户没有并且默认关闭 或者 用户关闭
            $jkuser = SM('JiekouUser')->findData('*', 'userid=' . $fxid . ' and jkid=' . $zjbuffer['jkid']);
            if ((isset($jkuser['ifopen']) && 1 != $jkuser['ifopen']) || (!$jkuser && empty($jiekoubuffer[$fxpay]['ifuseropen'])) || empty($jiekoubuffer[$fxpay]['ifopen'])) {
                $msg = '没有接口权限，请申请开通后尝试。';
                $this->paylog($request, $msg);
                return [0,
                    $msg];
            }

            if ($jkuser && !empty($jkuser['fl']))
                $fl = $jkuser['fl']; //用户自定义费率
        } else {
            $thishttp = $peizhi['httpstyle'].'://'.$_SERVER['HTTP_HOST'];
            $zjbuffer = SM('Jiekouzj')->findData('*', 'zjid=' . $check);
        }

        //查询支付账户和类型
        $jkpz = SM('Jiekoupeizhi')->findData('*', 'pzid=' . $zjbuffer['pzid']);
        if (!$jkpz) {
            $msg = '该请求类型暂时不可用。';
            $this->paylog($request, $msg);
            return [0,
                $msg];
        }

        //判断跳转
        if ($fxbankcode && empty($jumpflag)) {
            //传入的银行号是否正确
            $bankBuffer = SM('Bank')->findData('*', 'bankcode="' . $fxbankcode . '"');
            if (empty($bankBuffer))
                $request['fxbankcode'] = '';
        }
        if (!empty($zjbuffer['ifjump']) && empty($jumpflag) && empty($fxbankcode)) {
            $wg = $thishttp . '/Pay';
            $request = stringChange('removeEmpty', $request);
            return [1,
                $thishttp . '/Pay/' . $zjbuffer['ifjump'] . '?' . http_build_query($request) . '&wg=' . urlencode($wg)];
            exit();
        }

        $apihttp = $thishttp;
        //获取吊起域名的网关地址
        if (!empty($jkpz['httpid'])) {
            $httpBuffer = SM('Http')->findData('*', 'id="' . $jkpz['httpid'] . '"');
            $apihttp = $httpBuffer['http'];
        }

        /**
         * 支付总接口
         * $request = array(
          'id' => $jkpz['zjid'], 接口中间表id 获取接口账户及参数
          'peizhi' => unserialize($jkpz['params']),
          'style' => $jkpz['style'], 账户类型 alipay
          'fxddh' => $fxid . $fxddh, 订单号
          'fxdesc' => $fxdesc, 说明
          'fxfee' => $fxfee, 金额
          'fxattch' => $fxattch, 原样返回
          'fxnotifyurl' => $fxnotifyurl, 异步路径
          'fxbackurl' => $fxbackurl, 同步路径
          'fxpay' => $fxpay, 支付接口类型 wxwap
          'fxip' => $fxip  IP地址
         * 'isjsapi'=>0 或1 是否调用jsapi
          );
         */
        $jkdata = array(
            'id' => $zjbuffer['zjid'],
            'peizhi' => unserialize($jkpz['params']),
            'apihttp' => $apihttp,
            'style' => $jkpz['style'],
            'fxddh' => $ddhfull,
            'fxmd5' => $ddhmd5,
            'fxdesc' => $fxdesc,
            'fxfee' => $fxfee,
            'fxattch' => $fxattch,
            'fxnotifyurl' => $apihttp . "/Pay/notify/" . $jkpz['style'], //本站回调
            'fxbackurl' => $apihttp . "/Pay/backurl/" . $jkpz['style'] . '/1/ddh/' . $ddhfull, //本站回调 $fxbackurl, //用户同步回调 用户需要做订单查询以支持实时订单状态
            'fxpay' => $fxpay,
            'fxip' => $fxip,
            'fxbankcode' => $fxbankcode,
            'fxfs' => $fxfs,
            'fxuserid' => $fxuserid,
            'fxddstyle' => $fxddstyle
        );

        //调用接口返回
        $result = SA(ucfirst($jkpz['style']))->pay($jkdata);

        if ($result[0] == 1) {
            if (empty($fl) || $fl == '0.00')
                $fl = $zjbuffer['fl']; //没有自定义费率按照接口费率
            $havemoney = $fxfee - $fxfee * $fl / 100;
            if ($havemoney < 0.01) {
                $havemoney = 0;
            } else {
                $havemoney = round($havemoney, 2);
            }

            $fj = array(
                'fxdesc' => $fxdesc,
                'fxattch' => $fxattch,
                'fxnotifyurl' => $fxnotifyurl,
                'fxbackurl' => $fxbackurl,
                'fxip' => $fxip,
                'fxbankcode' => $fxbankcode,
                'fxfs' => $fxfs,
                'fxuserid' => $fxuserid,
                'fxnotifystyle' => $fxnotifystyle
            );

            //获取代理money 数据支付成功后计算代理数据
            $dailimoney = 0;

            $ddstyle = $fxddstyle;
            //写入订单
            $data = array(
                'status' => 0,
                'ordernum' => $ddhfull,
                'ordermd5' => $ddhmd5,
                'userid' => $fxid,
                'totalmoney' => $fxfee,
                'havemoney' => $havemoney,
                'dailimoney' => $dailimoney,
                'ddstyle' => $ddstyle,
                'tz' => 0,
                'preordernum' => '',
                'zjid' => $zjbuffer['zjid'],
                'pzid' => $zjbuffer['pzid'],
                'addtime' => time(),
                'fl' => $fl,
                'jkstyle' => $fxpay,
                'paytime' => 0,
                'fj' => serialize($fj)
            );
            if ($ddBuffer) {
                SM('Dingdan')->updateData($data, 'ddid=' . $ddBuffer['ddid']);
            } else {
                SM('Dingdan')->insertData($data);
            }

            $con = $result[1];
            if (is_array($result[1]))
                $con = serialize($result[1]);
            $this->paylog($request, '', $con);
        }else {
            $msg = $result[1];
            $this->paylog($request, $msg);
        }

        //二维码收银台
        if ($fxsmstyle == 1 && strstr($fxpay, 'sm')) {
            if ($result[0] === 1) {
                $result[1] = $apihttp . '/Pay/ewm/ddh/' . $ddhfull . '?qr=' . urlencode($result[1]) . '&md=' . md5($fxid . $fxddh . $result[1] . C('FX_QRCODE_KEY'));
            }
        }

        return $result;
    }

    /**
     * 查询订单状态
     */
    public function payQuery($request) {
        $fxid = $request['fxid'];
        $fxddh = $request['fxddh'];
        $fxsign = $request['fxsign'];
        $fxaction = $request['fxaction'];

        //判断商户号 key是否存在
        $userBuffer = SM('User')->findData('*', 'userid=' . $fxid);
        if (!$userBuffer || $userBuffer['status'] == 1) {
            return [0,
                '商户号错误。'];
        }

        $fxkey = $userBuffer['miyao'];
        //判断订单长度
        if (strlen($fxddh) > 22) {
            return [0,
                '订单号长度必须小于22位。'];
        }

        //判断签名是否正确 商务号+商户订单号+商户秘钥
        if ($fxsign != md5($fxid . $fxddh . $fxaction . $fxkey)) {
            return [0,
                '签名错误。'];
        }

        $buffer = SM('Dingdan')->findData('*', 'ordernum="' . $fxid . $fxddh . '"');
        if (!$buffer) {
            return [0,
                '订单号不存在。'];
        }

        if ($buffer['status'] == 2) {
            $buffer['status'] = 0;
            $buffer['paytime'] = 0;
            $buffer['preordernum'] = '';
        }

        $fj = unserialize($buffer['fj']);
        $data = array(
            'fxid' => $fxid,
            'fxstatus' => $buffer['status'],
            'fxddh' => $fxddh,
            'fxorder' => $buffer['preordernum'],
            'fxdesc' => $fj['fxdesc'],
            'fxfee' => $buffer['totalmoney'],
            'fxattch' => $fj['fxattch'],
            'fxtime' => $buffer['paytime'],
            'fxsign' => md5($buffer['status'] . $fxid . $fxddh . $buffer['totalmoney'] . $fxkey)
        );
        //订单状态+商务号+商户订单号+支付金额+商户秘钥

        return [1,
            $data];
    }

    /**
     * 支付日志
     * $data 内容数组，提交的参数
     * $msg 错误信息 为空代表正确
     * $result 支付正确的返回信息
     */
    private function paylog($data, $msg = '', $result = '') {

        //判断系统开关
        global $publicData;
        if ($publicData['peizhi']['ifpaylog'] != 1) {
            return false;
        }

        $ip = get_client_ip(0, true);
        $http = $_SERVER['HTTP_REFERER'];
        if (empty($http))
            $http = '';
        $content = serialize($data);
        $userid = $data['fxid'];
        $fxfee = $data['fxfee'];
        $fxddh = $data['fxddh'];
        $fxpay = $data['fxpay'];
        $status = 0;
        if (empty($msg)) {
            $status = 1;
            $msg = $result;
        }


        $arr = array(
            'userid' => $userid,
            'http' => $http,
            'content' => $content,
            'status' => $status,
            'result' => $msg,
            'fxddh' => $fxddh,
            'fxfee' => $fxfee,
            'fxpay' => $fxpay,
            'ip' => $ip,
            'addtime' => time()
        );

        foreach ($arr as $i => $iArr) {
            if (is_null($iArr))
                $arr[$i] = 0;
        }

        SM('DingdanPay')->insertData($arr);
    }

    /**
     * 资金变动日志
     */
    public function moneylog($request) {
        $perpage = C('FX_PERPAGE'); //每页行数
        $map = array();
        $data = ' 1=1 ';
        //高级查询
        if ($request['userid']) {
            $map['userid'] = $request['userid'];
            $data .= ' AND userid = "' . $request['userid'] . '" ';
        }
        if (is_numeric($request['style'])) {
            $map['style'] = $request['style'];
            $data .= ' AND style ="' . $request['style'] . '" ';
        }
        $start = $request['start'];
        if (strstr($start, '-')) {
            $start = strtotime($start);
        }
        $end = $request['end'];
        if (strstr($end, '-')) {
            $end = strtotime($end);
        }
        if ($start) {
            if (empty($end))
                $end = time();
            $map['start'] = $start;
            $map['end'] = $end;
            $request['start'] = date('Y-m-d', $start);
            $request['end'] = date('Y-m-d', $end);
            $data .= ' AND addtime between ' . ($start) . ' and ' . ($end) . ' ';
        }
        $log = SM('PayLog');
        $count = $log->selectCount(
                $data, 'id'); // 查询满足要求的总记录
        // 进行分页数据查询 注意limit方法的参数要使用Page类的属性
        $page = page($count, $request['p'], $perpage) . ',' . $perpage;
        $list = $log->pageData(
                '*', $data, 'id desc', $page);
        foreach ($list as $i => $iList) {
            $list[$i]['stylename'] = $this->paylogzt[$iList['style']];
            $list[$i] = stringChange('formatMoneyByArray', $list[$i], array(
                'changemoney',
                'leavemoney'));
        }

        $pageList = $this->pageList($count, $perpage, $map);

        /* 载入模板标签 */
        $params = array(
            'list' => $list,
            'page' => $pageList,
            'style' => $this->paylogzt,
            'pageName' => '资金变动管理'
        );
        return [1,
            $params];
    }

    /**
     * 资金变动日志清除
     */
    public function moneylogdelete($request) {
        $logID = $request['clear']; //获取数据标识
        if (!$logID) {
            return [0,
                '数据标识不能为空！',
                U('Pay/moneylog')];
        }

        $log = SM('PayLog');
        if ($log->deleteData('addtime < ' . (time() - 30 * 24 * 3600)) === false) {
            return [0,
                '删除失败'];
        } else {
            //写入日志
            $this->adminLog($this->moduleName, '删除最近30天资金变动日志记录');
            return [1,
                '删除成功！',
                U('Pay/moneylog')];
        }
    }

    /**
     * 资金变动日志添加
     */
    public function moneylogadd($data) {
        $data['addtime'] = time();
        if ($data['ddh']) {
            $buffer = SM('PayLog')->findData('*', 'ddh="' . $data['ddh'] . '" and userid="' . $data['userid'] . '"');
            if ($buffer)
                return true;
        }
        return SM('PayLog')->insertData($data);
    }

    /**
     * 判断是否有新订单
     */
    public function checkneworder($request) {
        $time = $request['times'];
        $status = $request['status'];
        $where = 'status=' . $status . ' and addtime>' . strtotime($time);
        if (empty($time)) {
            $where = 'status=' . $status;
        }
        $buffer = SM('Pay')->findData('*', $where);
        if ($buffer) {
            return [1,
                1];
        }
        return [1,
            0];
    }

    /**
     * 代付查询
     */
    public function dingdanselect($request) {
        $id = $request['id']; //获取数据标识

        if (empty($id)) {
            return [0,
                '数据标识不能为空！'];
        }

        $buffer = SM('PayDingdan')->findData('*', 'ddh="' . $id . '"');
        $pzbuffer = SM('Jiekoupeizhi')->findData('*', 'pzid="' . $buffer['pzid'] . '"');
        $paybuffer = SM('Pay')->findData('*', 'id="' . $buffer['payid'] . '"');
        $params = unserialize($pzbuffer['params']);
        $arr = array(
            'ddh' => $id,
            'peizhi' => $params,
            'paybuffer' => $paybuffer,
        );

        $result = SA(ucfirst($pzbuffer['style']))->repayselect($arr);
        return $result;
    }

    /**
     * 代付异步重发
     */
    public function dingdancf($request) {
        if (IS_POST) {
            $ddh = $request['ddh'];
            $url = $request['url'];
            $params = $request['params']; //获取模板标识
            $notifystyle = $request['notifystyle'];
            //判断数据标识
            if (empty($ddh)) {
                return [0,
                    '订单号不能为空！'];
            }
            if (empty($params)) {
                return [0,
                    '参数不能为空！'];
            }
            if (empty($url)) {
                return [0,
                    '返回地址不能为空！'];
            }

            $dingdan = SM('Pay');
            $buffer = $dingdan->findData('*', 'ddh="' . $ddh . '"');
            if (!$buffer) {
                return [0,
                    '订单号不存在！'];
            }

            $params = urldecode(htmlspecialchars_decode($params));
            $tmparr = explode('&', $params);
            $arr = array();
            foreach ($tmparr as $i => $iTmparr) {
                $tmp = explode('=', $iTmparr);
                $arr[$tmp[0]] = $tmp[1];
            }
            $params = $arr;

            $json = '';
            if ($notifystyle == 2)
                $json = 'json';
            $result = CURL($url, $params, $json);
            //返回数据正常则修改订单返回通知
            if (strtolower($result) == 'success' && $buffer['tz'] != 2) {
                $dingdan->updateData(array(
                    'tz' => '2'), 'id="' . $buffer['id'] . '"');
            }
            return [1,
                $result];
        }
        if (!$request['id']) {
            return [0,
                '参数错误！'];
        }
        $order = SM('Pay');
        $payBuffer = $order->findData('*', 'id=' . $request['id']);
        $userBuffer = SM('User')->findData('*', 'userid="' . $payBuffer['userid'] . '"');
        switch ($payBuffer['status']) {
            case '0':
                $status = 2;
                break;
            case '1':
                $status = 1;
                break;
            default:
                $status = 0;
                break;
        }

        $userid = $userBuffer["userid"];
        $key = $userBuffer["miyao"];
        $ordermoney = $row['money'];
        $ddh = $row['ordernum'];
        $fj = unserialize($row['fj']);

        $ddhYuan = substr($payBuffer['ddh'], strlen($payBuffer['userid']));
        $pp = $payBuffer['userid'] . $ddhYuan . $payBuffer['ddh'] . $status . $payBuffer['money'] . $payBuffer['dffl'] . $payBuffer['ka'] . $payBuffer['realname'] . $payBuffer['address'] . $userBuffer['miyao'];
        $k = md5($pp);
        $post_data = array(
            'fxid' => $payBuffer['userid'],
            'fxddh' => $ddhYuan,
            'fxorder' => $payBuffer['ddh'],
            'fxfee' => $payBuffer['money'],
            'fxdffee' => $payBuffer['dffl'],
            'fxstatus' => $status,
            'fxbody' => $payBuffer['ka'],
            'fxname' => $payBuffer['realname'],
            'fxaddress' => $payBuffer['address'],
            'fxzhihang' => $payBuffer['zhihang'],
            'fxsheng' => $payBuffer['sheng'],
            'fxshi' => $payBuffer['shi'],
            'fxlhh' => $payBuffer['lhh'],
            'fxsign' => $k
        );
        $str = array();
        foreach ($post_data as $k => $buffer) {
            $str[] = $k . '=' . urlencode($buffer);
        }

        $fj = unserialize($payBuffer['fj']);

        $payBuffer['params'] = implode('&', $str);
        $payBuffer['sigleddh'] = $ddhYuan;
        $payBuffer['notifystyle'] = $fj['fxnotifystyle'];

        $params = array(
            'edit' => $payBuffer,
            'act' => 'edit',
            'pageName' => '订单重发'
        );
        return [1,
            $params];
    }

    /**
     *  代付订单列表
     */
    public function dingdanall($request) {
        $map = array();
        $data = ' 1=1 ';
        //高级查询
        if ($request['ddh']) {
            $map['ddh'] = $request['ddh'];
            $data .= ' AND ddh = "' . $request['ddh'] . '" ';
        }
        if (is_numeric($request['status'])) {
            $map['status'] = $request['status'];
            $data .= ' AND status ="' . $request['status'] . '" ';
        }

        $start = $request['start'];
        if (strstr($start, '-')) {
            $start = strtotime($start);
        }
        $end = $request['end'];
        if (strstr($end, '-')) {
            $end = strtotime($end);
        }
        if ($start) {
            if (empty($end))
                $end = time();
            $map['start'] = $start;
            $map['end'] = $end;
            $request['start'] = date('Y-m-d', $start);
            $request['end'] = date('Y-m-d', $end);
            $data .= ' AND addtime between ' . ($start) . ' and ' . ($end) . ' ';
        }

        $payother = SM('Jiekoupeizhi')->selectData('*', '1=1', 'pzid desc');
        $payotherKey = stringChange('arrayKey', $payother, 'pzid');

        $pay = SM('PayDingdan');
        $count = $pay->selectCount(
                $data, 'id'); // 查询满足要求的总记录
        // 进行分页数据查询 注意limit方法的参数要使用Page类的属性
        $perpage = C('FX_PERPAGE'); //每页行数
        $page = page($count, $request['p'], $perpage) . ',' . $perpage;
        $list = $pay->pageData('*', $data, 'id DESC', $page);
        foreach ($list as $i => $iList) {
            $list[$i]['statusname'] = $this->fanhuizt[$list[$i]['status']];
            if ($payotherKey[$list[$i]['pzid']])
                $list[$i]['paybankname'] = SA(ucfirst($payotherKey[$list[$i]['pzid']]['style']))->paybank[$list[$i]['paybank']];
            $list[$i]['pzname'] = $payotherKey[$list[$i]['pzid']]['pzname'];
            $list[$i]['addtime'] = stringChange('formatDateTime', $iList['addtime']);
            $list[$i]['paytime'] = stringChange('formatDateTime', $iList['paytime']);
        }

        $pageList = $this->pageList($count, $perpage, $map);


        $params = array(
            'list' => $list,
            'payother' => $payother,
            'zt' => $this->fanhuizt,
            'page' => $pageList,
            'pageName' => '代付订单'
        );
        return [1,
            $params,
            'Pay/dingdanall'];
    }

    /**
     * 删除
     */
    public function delete($request) {
        $orderID = $request['id']; //获取数据标识
        $clear = $request['clear']; //获取数据标识
        //清除三天以上的失败订单
        if ($clear) {
            if (SM('PayDingdan')->deleteData('addtime<' . (time() - 3 * 24 * 3600) . ' and status=2') === false) {
                return [0,
                    '删除失败'];
            } else {
                //写入日志
                $this->adminLog($this->moduleName, '清除三天以上的失败订单');
                return [1,
                    '删除成功',
                    U('Pay/dingdanall')];
            }
        }

        $idArray = explode(',', $orderID);

        if (!$orderID) {
            return [0,
                '数据标识不能为空',
                U('Pay/dingdanall')];
        }

        //只能删除未支付订单
        if (SM('PayDingdan')->deleteData(
                        'status=2 and id in (' . implode(',', $idArray) . ')') === false) {
            return [0,
                '删除失败'];
        } else {
            //写入日志
            $this->adminLog($this->moduleName, '删除失败代付订单ID为【' . implode(',', $idArray) . '】的数据');
            return [1,
                '删除成功',
                U('Pay/dingdanall')];
        }
    }
}
