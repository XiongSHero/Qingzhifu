<?php

// +----------------------------------------------------------------------
// | easy pay [ pay to easy ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2017 All rights reserved.
// +----------------------------------------------------------------------
// | Author: fengxing <QQ:51125330>
// +----------------------------------------------------------------------

namespace Common\Logic;

class HomeLogic extends BaseLogic {

    protected $peizhi = array();
    protected $user = array();

    /**
     * 构造函数
     */
    public function __construct() {
        global $publicData;
        if (empty($publicData['peizhi'])) {
            $this->peizhi = SL('Param')->getPZ();
            $publicData['peizhi'] = $this->peizhi;
        } else {
            $this->peizhi = $publicData['peizhi'];
        }
        $publicData['user']['money'] = sprintf("%.2f", $publicData['user']['money']);
        $this->user = $publicData['user'];
    }

    /**
     * 用户首页
     */
    public function index($request) {

        //获取统计信息
        $today = strtotime(date('Y-m-d', time()));
        $yes = strtotime(date('Y-m-d', time() - 24 * 3600));
        $tj = array();
        $tj['payhave'] = SM('Pay')->sumData('money', 'status=0 and userid=' . $this->user['userid']); //等待支付
        //$tj['dingdanall'] = SM('Dingdan')->sumData('havemoney', 'status=1 and userid=' . $this->user['userid']); //订单总金额
        $tj['dingdantoday'] = SM('Dingdan')->sumData('havemoney', 'status=1 and userid=' . $this->user['userid'] . ' and addtime>=' . $today); //今日收益
        $tj['dingdanyes'] = SM('Dingdan')->sumData('havemoney', 'status=1 and userid=' . $this->user['userid'] . ' and (addtime between ' . $yes . ' and ' . $today . ')'); //昨日收益
        $tj['dingdantodaycount'] = SM('Dingdan')->selectCount('status=1 and userid=' . $this->user['userid'] . ' and addtime>=' . $today, 'ddid'); //今日订单笔数
        $tj['dingdanyescount'] = SM('Dingdan')->selectCount('status=1 and userid=' . $this->user['userid'] . ' and (addtime between ' . $yes . ' and ' . $today . ')', 'ddid'); //昨日订单笔数
        foreach ($tj as $i => $iTj) {
            if (empty($iTj))
                $tj[$i] = 0;
        }

        $tj = stringChange('formatMoneyByArray', $tj, 'payhave,dingdanall,dingdantoday,dingdanyes');

        //判断保证金是否开启
        $hidden = 0;
        if ($this->peizhi['baozhengjin'] > 0 && $this->user['regmoney'] < $this->peizhi['baozhengjin']) {
            $hidden = 1;
        }

        //网关地址
        $httpBuffer = SL('Http')->getApiHttp($this->user['userid']);
        $apihttp = '';
        if (is_array($httpBuffer[1])) {
            $apihttp = '<a href="' . U('Index/Home/fl') . '">网关地址</a>';
        } else {
            $apihttp = $httpBuffer[1] . '/Pay';
        }

        //可用金额冻结金额
        $balancestyle = $this->getBalanceStyle();
        $result = SL('Dingdan')->getFrozenMoney($this->user['userid'], $this->user['ifagent'], $balancestyle);
        $todaymoney = 0; //冻结金额
        if ($result[0] == 1)
            $todaymoney = $result[1];
        $nowmoney = $this->user['money'] - (int) $todaymoney; //可用金额

        $params = array(
            'tj' => $tj,
            'apihttp' => $apihttp,
            'baozhengjin' => $hidden,
            'todaymoney' => $todaymoney,
            'nowmoney' => $nowmoney,
            'balancestyle' => $this->getBalanceStyle(),
            'pageName' => '用户中心'
        );
        return [1,
            $params];
    }

    //获取用户结算周期
    private function getBalanceStyle() {
        $balancestyle = $this->peizhi['balancestyle'] . '+' . $this->peizhi['balancetime'];
        if (!empty($this->user['balancestyle'])) {
            $balancestyle = $this->user['balancestyle'] . '+' . $this->user['balancetime'];
        }
        return $balancestyle;
    }

    /**
     * 修改密码
     */
    public function pass($request) {
        if (IS_POST) {
            $thisstyle = $request['thisstyle'];
            $user = SM('User');
            $data = array();
            $edittitle = '修改个人信息';
            $userBuffer = $user->findData('*', 'userid="' . $this->user['userid'] . '"');

            switch ($thisstyle) {
                case '1':
                    $data['qq'] = $request['qq'];
                    $data['email'] = $request['email'];
                    if (!empty($data['email']) && !checkString('checkIfEmail', $data['email']))
                        return [0,
                            '请输入正确的邮箱地址！'];

                    if ($this->peizhi['ifsms'] != '1') {
                        $data['phone'] = $request['phone'];
                        if (!empty($data['phone']) && !checkString('checkIfPhone', $data['phone']))
                            return [0,
                                '请输入正确的手机号！'];
                        if ($userBuffer["phone"] != $data['phone']) {
                            $data['ifcheckphone'] = 0;
                        }
                    }
                    break;
                case '2':
                    $edittitle = '修改登录密码';
                    //判断新密码
                    $passwordy = $request['passwordy'];
                    $password = $request['password'];
                    $password2 = $request['password2'];
                    if (empty($passwordy)) {
                        return [0,
                            '请输入原密码！'];
                    }
                    if ($password2 != $password) {
                        return [0,
                            '两次输入的密码不一致！'];
                    }

                    //密码规范
                    if (!checkString('checkUserPassWord', $password)) {
                        return [0,
                            '密码长度大于8，数字，字母组合！'];
                    }
                    if ($userBuffer['password'] != md5($userBuffer['username'] . $passwordy)) {
                        return [0,
                            '原密码错误！'];
                    }
                    $data['password'] = md5($userBuffer['username'] . $password);
                    break;
                case '3':
                    $edittitle = '修改支付密码';
                    //判断支付密码
                    $txmmy = $request['txmmy'];
                    $txmm = $request['txmm'];
                    $txmm2 = $request['txmm2'];
                    if (empty($txmmy)) {
                        return [0,
                            '请输入支付密码！'];
                    }
                    if ($txmm2 != $txmm) {
                        return [0,
                            '两次输入的支付密码不一致！'];
                    }

                    //密码规范
                    if (!checkString('checkUserPassWord', $txmm)) {
                        return [0,
                            '支付密码长度大于8，数字，字母组合！'];
                    }
                    if ($userBuffer['txpassword'] != md5($userBuffer['username'] . $txmmy)) {
                        return [0,
                            '原支付密码错误！'];
                    }
                    $data['txpassword'] = md5($userBuffer['username'] . $txmm);
                    break;
                case '4':
                    if ($userBuffer['ifcheckphone'] == 1) {
                        $edittitle = '解绑手机号';
                        $data['ifcheckphone'] = 0;
                    } else {
                        $edittitle = '绑定手机号';
                        $data['phone'] = $request['phone'];
                        $data['ifcheckphone'] = 1;
                        if (!empty($data['phone']) && !checkString('checkIfPhone', $data['phone']))
                            return [0,
                                '请输入正确的手机号！'];
                    }
                    break;
            }

            if ($this->peizhi['ifsms'] == '1' && $thisstyle > 2) {
                $code = $request['code'];
                $phone = $userBuffer['phone'];
                if ($data['phone'])
                    $phone = $data['phone'];
                //判断短信密码
                $result = SL('Logsms')->checksms(array(
                    'code' => $code,
                    'phone' => $phone));
                if ($result[0] != 1) {
                    return $result;
                }
            }

            if ($user->updateData(
                            $data, 'userid=' . $userBuffer['userid']) === false) {
                return [0,
                    '修改失败'];
            } else {
                //写入日志
                $this->userLog('用户账户', $edittitle . '修改用户UserID为【' . $userBuffer['userid'] . '】的数据', $userBuffer['userid']);
                return [1,
                    '修改成功！',
                    U('Index/Home/pass')];
            }
        }

        $params = array(
            'pageName' => '账户修改'
        );
        return [1,
            $params];
    }

    /**
     * 用户信息
     */
    public function info($request) {
        if ($this->peizhi['ifopenusercheck'] != 1) {
            return [0,
                '商户认证未开启！'];
        }
        $usercheck = SM('Usercheck');
        $userid = $this->user['userid'];
        $usercheckbuffer = $usercheck->findData('*', 'userid="' . $userid . '"');
        if ($usercheckbuffer) {
            $tmp = unserialize($usercheckbuffer['photos']);
            if ($tmp) {
                $usercheckbuffer = array_merge($usercheckbuffer, $tmp);
            }
            $statusarr = array(
                '待审核',
                '审核通过',
                '审核失败',
            );
            $usercheckbuffer['statusname'] = $statusarr[$usercheckbuffer['status']];
        } else {
            $usercheckbuffer['statusname'] = '待提交';
            $usercheckbuffer['checkstyle'] = 0;
        }

        if (IS_POST) {
            $data = array();
            $data['myid'] = $request['myid'];
            $data['myname'] = $request['myname'];
            $data['editionid'] = $request['editionid'];
            $data['editionname'] = $request['editionname'];
            $data['address'] = $request['address'];
            $data['email'] = $request['email'];
            $data['phone'] = $request['phone'];
            $data['checkstyle'] = $request['checkstyle'];

            $photos = array(
                'sfzzm' => $request['sfzzm'],
                'sfzfm' => $request['sfzfm'],
                'sfzsc' => $request['sfzsc'],
                'yyzz' => $request['yyzz'],
                'mtz' => $request['mtz'],
                'bgz1' => $request['bgz1'],
                'bgz2' => $request['bgz2']
            );
            $data['photos'] = serialize($photos);

            if (!empty($data['phone']) && !checkString('checkIfPhone', $data['phone']))
                return [0,
                    '请输入正确的手机号！'];
            if (!empty($data['email']) && !checkString('checkIfEmail', $data['email']))
                return [0,
                    '请输入正确的邮箱地址！'];

            $status = 0;
            $ifusercheck = 1;
            $successstr = '提交信息成功，请等待审核。';
            if (empty($this->peizhi['ifopenusercheckauto'])) {
                $status = 1;
                $ifusercheck = 2;
                $successstr = '审核成功。';
            }

            if (!$usercheckbuffer['userid']) {
                $data['userid'] = $userid;
                $data['addtime'] = time();
                $data['status'] = $status;
                $result = $usercheck->insertData($data);
            } else {
                $data['addtime'] = time();
                $data['status'] = $status;
                $result = $usercheck->updateData($data, 'userid="' . $userid . '"');
            }
            if ($result === false) {
                return [0,
                    '提交信息失败，请重试.'];
            }
            //改变用户认证状态
            SM('User')->updateData(['ifusercheck' => $ifusercheck], 'userid="' . $userid . '"');

            return [1,
                $successstr,
                U('Index/Home/info')];
        }

        $params = array(
            'edit' => $usercheckbuffer,
            'pageName' => '商户认证'
        );
        return [1,
            $params];
    }

    /**
     * 退出登录
     */
    public function loginout($request) {
        $this->setCookieCode(null, null);
        $this->setCookieUserID(null, null);
        $this->setCookieUserName(null, null);
        header('Location:' . U('/'));
        exit();
    }

    /**
     * 交易订单
     */
    public function dingdan($request) {

        $return = $this->dingdanwhere($request);
        $map = $return[0];
        $data = $return[1];

        $perpage = C('FX_PERPAGE'); //每页行数
        $order = SM('Dingdan');
        $count = $order->selectCount(
                $data, 'ddid'
        ); // 查询满足要求的总记录数
        // 进行分页数据查询 注意limit方法的参数要使用Page类的属性
        $page = page($count, $request['p'], $perpage) . ',' . $perpage;

        $list = $order->pageData(
                '*', $data, 'ddid DESC', $page
        );

        $jiekou = SM('Jiekou')->selectData('*', '1=1', 'jkid asc');
        $jiekoubuffer = stringChange('arrayKey', $jiekou, 'jkstyle');

        $dingdanLogic = SL('Dingdan');
        foreach ($list as $i => $iList) {
            if ($iList['status'] == 2) {
                $list[$i]['status'] = 0;
                $list[$i]['paytime'] = 0;
                $list[$i]['tz'] = 0;
                $list[$i]['preordernum'] = '';
            }

            $list[$i]['ordernum'] = $this->dingdanchangenum($list[$i]['ordernum']);
            $list[$i]['addtime'] = stringChange('formatDateTime', $list[$i]['addtime']);
            $list[$i]['paytime'] = stringChange('formatDateTime', $list[$i]['paytime']);
            if ($list[$i]['preordernum'] == '')
                $list[$i]['preordernum'] = '-';
            $list[$i]['tzzt'] = $dingdanLogic->tzzt[$list[$i]['tz']];
            $list[$i]['status'] = $dingdanLogic->zt[$list[$i]['status']];
            $list[$i]['ddstylename'] = $dingdanLogic->ddstyle[$list[$i]['ddstyle']];
            $list[$i]['jkstyle'] = $jiekoubuffer[$list[$i]['jkstyle']]['jkname'];
            $list[$i] = stringChange('formatMoneyByArray', $list[$i], array(
                'totalmoney',
                'havemoney'));
        }
        $pageList = $this->pageList($count, $perpage, $map);

        //统计数据 今日订单笔数     昨日订单笔数     今日订单总金额     今日支出金额     昨日订单总金额     昨日支出金额     历史总笔数     历史总金额     历史总支出
        $today = strtotime(date('Y-m-d', time()));
        $yes = strtotime(date('Y-m-d', time() - 24 * 3600));
        $tj = array();
        $tj['today'] = $order->sumData('havemoney', 'userid=' . $this->user['userid'] . ' and status=1 and addtime>=' . $today);
        $tj['yes'] = $order->sumData('havemoney', 'userid=' . $this->user['userid'] . ' and status=1 and (addtime between ' . $yes . ' and ' . $today . ')');
        $tj['all'] = $order->sumData('havemoney', 'userid=' . $this->user['userid'] . ' and status=1');
        foreach ($tj as $i => $iTj) {
            if (empty($iTj))
                $tj[$i] = 0;
        }
        $tj = stringChange('formatMoneyByArray', $tj, array(
            'today',
            'yes',
            'all'));

        $params = array(
            'list' => $list,
            'tj' => $tj,
            'page' => $pageList,
            'jiekou' => $jiekou,
            'pageName' => '交易记录'
        );
        return [1,
            $params];
    }

    /**
     * 交易订单重发通知
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

            $dingdan = SM('Dingdan');
            $buffer = $dingdan->findData('*', 'ordernum="' . $ddh . '"');
            if (!$buffer) {
                return [0,
                    '订单号不存在！'];
            }
            if (1 != $buffer['status']) {
                return [0,
                    '订单未支付！'];
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
                    'tz' => '2'), 'ddid="' . $buffer['ddid'] . '"');
            }
            return [1,
                $result];
        }
        if (!$request['id']) {
            return [0,
                '参数错误！'];
        }
        $order = SM('Dingdan');
        $row = $order->findData('*', 'ddid=' . $request['id']);
        if ($row['status'] == 2) {
            $row['status'] = 0;
            $row['paytime'] = 0;
            $row['tz'] = 0;
            $row['preordernum'] = '';
        }

        $userBuffer = SM('User')->findData('*', 'userid="' . $row['userid'] . '"');
        $status = '1';
        $userid = $userBuffer["userid"];
        $key = $userBuffer["miyao"];
        $ordermoney = $row['totalmoney'];
        $ddh = $row['ordernum'];
        $fj = unserialize($row['fj']);

        $ddh = substr($ddh, strlen($userid));
        $k = md5($status . $userid . $ddh . $ordermoney . $key);
        $post_data = array(
            'fxid' => $userid,
            'fxddh' => $ddh,
            'fxdesc' => $fj['fxdesc'],
            'fxorder' => $row['preordernum'],
            'fxfee' => $ordermoney,
            'fxattch' => $fj['fxattch'],
            'fxtime' => $row['paytime'],
            'fxstatus' => $status,
            'fxsign' => $k
        );
        $str = array();
        foreach ($post_data as $k => $buffer) {
            $str[] = $k . '=' . urlencode($buffer);
        }

        $row['params'] = implode('&', $str);
        $row['notifyurl'] = $fj['fxnotifyurl'];
        $row['notifystyle'] = $fj['fxnotifystyle'];
        $row['sigleddh'] = substr($row['ordernum'], strlen($row['userid']));

        $params = array(
            'edit' => $row,
            'act' => 'edit',
            'pageName' => '订单重发'
        );
        return [1,
            $params];
    }

    /**
     * 银行卡管理
     */
    public function yhk($request) {
        if ($request['id']) { //删除数据
            $zijianID = $request['id']; //获取数据标识
            $idArray = explode(',', $zijianID);

            if (!$zijianID) {
                return [0,
                    '数据标识不能为空'];
            }
            $idstr = implode(',', $idArray);
            $ka = SM('Ka');
            $buffer = $ka->selectData('*', 'id in (' . $idstr . ')');
            foreach ($buffer as $iBuffer) {
                if ($iBuffer['userid'] != $this->user['userid']) {
                    return [0,
                        '数据id错误。'];
                }
            }

            if ($ka->deleteData(
                            'id in (' . $idstr . ')') === false) {
                return [0,
                    '删除失败'];
            } else {
                //写入日志
                $this->userLog('银行卡', '删除银行卡ID为【' . $idstr . '】的数据');
                return [1,
                    '删除成功',
                    U('Index/Home/yhk')];
            }
        }
        $map = array();
        $data = ' userid=' . $this->user['userid'] . ' ';
        //高级查询
        if ($request['ka']) {
            $map['ka'] = $request['ka'];
            $data.=' AND ka ="' . $request['ka'] . '" ';
        }
        if ($request['username']) {
            $map['username'] = $request['username'];
            $data.=' AND username ="' . $request['username'] . '" ';
        }
        if (is_numeric($request['ifcheck'])) {
            $map['ifcheck'] = $request['ifcheck'];
            $data.=' AND ifcheck ="' . $request['ifcheck'] . '" ';
        }
        $perpage = C('FX_PERPAGE'); //每页行数
        $zijian = SM('Ka');
        $count = $zijian->selectCount(
                $data, 'id'
        ); // 查询满足要求的总记录数
        // 进行分页数据查询 注意limit方法的参数要使用Page类的属性
        $page = page($count, $request['p'], $perpage) . ',' . $perpage;

        $list = $zijian->pageData(
                '*', $data, 'id DESC', $page
        );

        foreach ($list as $i => $iList) {
            $list[$i]['addtime'] = stringChange('formatDateTime', $iList['addtime']);
            $list[$i]['ifcheckname'] = $iList['ifcheck'] == 1 ? '通过' : '未通过';
            $list[$i]['checktime'] = stringChange('formatDateTime', $iList['checktime']);
        }

        $pageList = $this->pageList($count, $perpage, $map);

        $params = array(
            'list' => $list,
            'page' => $pageList,
            'pageName' => '支付账户管理'
        );
        return [1,
            $params];
    }

    /**
     * 银行卡添加
     */
    public function yhkadd($request) {
        if (IS_POST) {
            $zjid = $request['id']; //获取数据标识
            $act = $request['act']; //获取模板标识
            //判断数据标识
            if (empty($zjid) && $act == 'edit') {
                return [0,
                    '数据标识不能为空！'];
            }
            if (empty($act)) {
                return [0,
                    '模板标识不能为空！'];
            }
            $zijian = SM('Ka');
            $data = array();
            $data['userid'] = $this->user['userid'];
            $data['username'] = $request['username'];
            $data['ka'] = $request['ka'];
            $data['address'] = $request['address'];
            $data['zhihang'] = $request['zhihang'];
            $data['sheng'] = $request['sheng'];
            $data['shi'] = $request['shi'];
            $data['lhh'] = $request['lhh'];
            $data['ifcheck'] = 0;
            if ($this->peizhi['ifcheckka'] != 1)
                $data['ifcheck'] = 1;

            if ($act == 'add') {
                $data['addtime'] = time();
                if ($zijian->insertData($data) === false) {
                    return [0,
                        '添加失败'];
                } else {
                    //写入日志
                    $this->userLog('银行卡', '添加银行卡【' . $data['ka'] . '】', $data['userid']);
                    return [1,
                        '添加成功！',
                        U('Index/Home/yhk')];
                }
            } elseif ($act == 'edit') {
                $data['id'] = $zjid;
                $buffer = $zijian->findData(
                        'userid,checktime', 'id="' . $data['id'] . '"');
                if (!$buffer) {
                    return [0,
                        '银行卡不存在'];
                }
                if ($buffer['userid'] != $this->user['userid']) {
                    return [0,
                        '修改数据有误。'];
                }
                if ($zijian->updateData(
                                $data, 'id=' . $data['id']) === false) {
                    return [0,
                        '修改失败'];
                } else {
                    //写入日志
                    $this->userLog('银行卡', '修改银行卡ID为【' . $data['id'] . '】的数据', $data['userid']);
                    return [1,
                        '修改成功！',
                        U('Index/Home/yhk')];
                }
            }
        }

        if (!$request['id']) {
            $params = array(
                'act' => 'add',
                'pageName' => '添加账户'
            );
            return [1,
                $params];
        }
        $zijianModel = SM('Ka');
        $row = $zijianModel->findData('*', 'id=' . $request['id']);
        if ($row['userid'] != $this->user['userid']) {
            return [0,
                '修改数据有误。'];
        }

        $params = array(
            'edit' => $row,
            'act' => 'edit',
            'pageName' => '修改账户'
        );
        return [1,
            $params];
    }

    /**
     * 域名审核管理
     */
    public function http($request) {
        $map = array();
        $data = ' userid=' . $this->user['userid'] . ' ';
        $perpage = C('FX_PERPAGE'); //每页行数
        $userHttp = SM('UserHttp');
        $count = $userHttp->selectCount(
                $data, 'id'
        ); // 查询满足要求的总记录数
        // 进行分页数据查询 注意limit方法的参数要使用Page类的属性
        $page = page($count, $request['p'], $perpage) . ',' . $perpage;

        $list = $userHttp->pageData(
                '*', $data, 'id DESC', $page
        );
        $userHttpLogic = SL('Userhttp');
        foreach ($list as $i => $iList) {
            $list[$i]['addtime'] = stringChange('formatDateTime', $iList['addtime']);
            $list[$i]['statusname'] = $userHttpLogic->statusArray[$iList['status']];
            $list[$i]['checktime'] = stringChange('formatDateTime', $iList['checktime']);
        }

        $pageList = $this->pageList($count, $perpage, $map);

        $params = array(
            'list' => $list,
            'page' => $pageList,
            'pageName' => '接入域名管理'
        );
        return [1,
            $params];
    }

    /**
     * 域名审核添加
     */
    public function httpadd($request) {
        if (!$request['id']) {
            $params = array(
                'act' => 'add',
                'pageName' => '添加域名'
            );
            return [1,
                $params];
        }
        $userHttp = SM('UserHttp');
        $row = $userHttp->findData('*', 'id=' . $request['id']);
        if ($row['userid'] != $this->user['userid']) {
            return [0,
                '修改数据有误。'];
        }

        $params = array(
            'edit' => $row,
            'act' => 'edit',
            'pageName' => '修改域名'
        );
        return [1,
            $params];
    }

    /**
     * 域名审核保存
     */
    public function httpsave($request) {
        if (IS_POST) {
            $zjid = $request['id']; //获取数据标识
            $act = $request['act']; //获取模板标识
            //判断数据标识
            if (empty($zjid) && $act == 'edit') {
                return [0,
                    '数据标识不能为空！'];
            }
            if (empty($act)) {
                return [0,
                    '模板标识不能为空！'];
            }
            $userHttp = SM('UserHttp');
            $data = array();
            $data['userid'] = $this->user['userid'];
            $data['sitename'] = $request['sitename'];
            $data['sitestyle'] = $request['sitestyle'];
            $data['beian'] = $request['beian'];
            $data['siteadmin'] = $request['siteadmin'];
            $data['sitephone'] = $request['sitephone'];
            $data['siteqq'] = $request['siteqq'];

            if ($act == 'add') {
                $data['addtime'] = time();
                $data['http'] = $request['http'];
                if ($userHttp->insertData($data) === false) {
                    return [0,
                        '添加失败'];
                } else {
                    //写入日志
                    $this->userLog('接入域名', '添加接入域名【' . $data['http'] . '】', $data['userid']);
                    return [1,
                        '添加成功！',
                        U('Index/Home/http')];
                }
            } elseif ($act == 'edit') {
                $data['id'] = $zjid;
                $buffer = $userHttp->findData(
                        'userid,checktime', 'id="' . $data['id'] . '"');
                if (!$buffer) {
                    return [0,
                        '您要修改的域名不存在'];
                }
                if ($buffer['userid'] != $this->user['userid']) {
                    return [0,
                        '修改数据有误。'];
                }
                if ($userHttp->updateData(
                                $data, 'id=' . $data['id']) === false) {
                    return [0,
                        '修改失败'];
                } else {
                    //写入日志
                    $this->userLog('接入域名', '修改接入域名ID为【' . $data['id'] . '】的数据', $data['userid']);
                    return [1,
                        '修改成功！',
                        U('Index/Home/http')];
                }
            }
        }
    }

    //域名审核删除
    public function httpdelete($request) {
        if ($request['id']) { //删除数据
            $httpID = $request['id']; //获取数据标识
            $idArray = explode(',', $httpID);

            if (!$httpID) {
                return [0,
                    '数据标识不能为空'];
            }
            $idstr = implode(',', $idArray);
            $userHttp = SM('UserHttp');
            $buffer = $userHttp->selectData('*', 'id in (' . $idstr . ')');
            foreach ($buffer as $iBuffer) {
                if ($iBuffer['userid'] != $this->user['userid']) {
                    return [0,
                        '数据id错误。'];
                }
            }

            if ($userHttp->deleteData(
                            'id in (' . $idstr . ')') === false) {
                return [0,
                    '删除失败'];
            } else {
                //写入日志
                $this->userLog('接入域名', '删除接入域名ID为【' . $idstr . '】的数据');
                return [1,
                    '删除成功',
                    U('Index/Home/http')];
            }
        }
    }

    /**
     * 申请支付
     */
    public function tx($request) {
        //可申请金额，减去冻结金额
        $balancestyle = $this->getBalanceStyle();
        $result = SL('Dingdan')->getFrozenMoney($this->user['userid'], $this->user['ifagent'], $balancestyle);
        $todaymoney = 0;
        if ($result[0] == 1)
            $todaymoney = $result[1];
        $nowmoney = $this->user['money'] - (int) $todaymoney;
        $dffl = SL('User')->getdffl($this->user['iffl'], $this->user['dffl']);

        //获取支付时间
        $result = SL('Param')->formatPayTime();
        $txpaytime = implode('-', $result);

        if (IS_POST) {
            $act = $request['act']; //获取模板标识
            $code = $request['code'];
            //判断数据标识
            if (empty($act)) {
                return [0,
                    '模板标识不能为空！'];
            }

            //判断支付密码
            $txmm = $request['txmm'];
            if ($this->user['txpassword'] != md5($this->user['username'] . $txmm)) {
                return [0,
                    '支付密码错误！'];
            }

            //判断支付时间
            $result = SL('Param')->checkPayTime();
            if (!$result)
                return [0,
                    '请在支付允许时间段' . $txpaytime . '操作！'];

            //判断是否是批量结算
            if ($act == 'list') {
                $path = SL('Upload')->uploadExcel('excel');
                if (!strstr($path, 'Uploads')) {
                    return [0,
                        $path];
                }

                $path = realpath('./') . $path;
                //解析excel
                include_once COMMON_PATH . '/Tool/Excel/Classes/PHPExcel/IOFactory.php';
                $filePath = iconv('utf-8', 'gbk//IGNORE', $path);
                $aa = new \PHPExcel_Reader_Excel2007;
                $bb = new \PHPExcel_Reader_Excel5;
                if (!$aa->canRead($filePath) && !$bb->canRead($filePath)) {
                    return [0,
                        '文件无法读取'];
                }
                $PHPExcel = \PHPExcel_IOFactory::load($filePath);
                if (!$PHPExcel)
                    return [0,
                        '文件读取失败'];
                $currentSheet = $PHPExcel->getSheet(0); // **取得一共有多少列*
                $allColumn = $currentSheet->getHighestColumn(); // **取得一共有多少行*
                $allRow = $currentSheet->getHighestRow();
                $allColumn++;
                //起始点的横纵坐标
                $startl = -1;
                $starth = -1;
                $arr = array();
                for ($currentRow = 1; $currentRow <= $allRow; $currentRow++) {
                    $tmp = [];
                    for ($currentColumn = 'A'; $currentColumn != $allColumn; $currentColumn++) {
                        $address = $currentColumn . $currentRow;
                        $tmp[] = $currentSheet->getCell($address)->getValue();
                    }
                    $arr[] = $tmp;
                }

                $newArr = array(); //结算信息
                $totalMoney = 0; //总金额
                $tixianMoney = 0; //支付金额
                $shouxuMoney = 0; //手续金额
                foreach ($arr as $i => $iArr) {
                    if ($i == 0) {
                        foreach ($iArr as $j => $jArr) {
                            switch ($jArr) {
                                case '银行名称':
                                    $yinhang = $j;
                                    break;
                                case '支行名称':
                                    $zhihang = $j;
                                    break;
                                case '开户名':
                                    $kaihu = $j;
                                    break;
                                case '银行帐号':
                                    $yinhangzhanghu = $j;
                                    break;
                                case '开户行所在省':
                                    $sheng = $j;
                                    break;
                                case '开户行所在市':
                                    $shi = $j;
                                    break;
                                case '金额':
                                    $jine = $j;
                                    break;
                                case '联行号':
                                    $lhh = $j;
                                    break;
                            }
                        }
                        continue;
                    }
                    if (!is_numeric($iArr[$jine])) {
                        return [0,
                            '金额格式有误，请确保金额为数字。'];
                    }

                    $sxf = SL('User')->calcdffl($iArr[$jine], $dffl);
                    $newArr[] = array(
                        'userid' => $this->user['userid'],
                        'money' => $iArr[$jine],
                        'realname' => $iArr[$kaihu],
                        'ka' => $iArr[$yinhangzhanghu],
                        'address' => $iArr[$yinhang],
                        'zhihang' => $iArr[$zhihang],
                        'sheng' => $iArr[$sheng],
                        'shi' => $iArr[$shi],
                        'lhh' => $iArr[$lhh],
                        'addtime' => time(),
                        'status' => 0,
                        'ddh' => 'qzf' . time() . getDingdanRand(),
                        'dffl' => $sxf
                    );
                    $tixianMoney+=$iArr[$jine];
                    $totalMoney+=$iArr[$jine] + $sxf;
                    $shouxuMoney+=$sxf;
                }

                //判断结算金额是否超出
                if ($totalMoney > $nowmoney) {
                    return [0,
                        '支付金额不足！当前需要' . $totalMoney . '元，手续费：' . $shouxuMoney . '元'];
                }

                if ($tixianMoney < $this->peizhi['minpay']) {
                    return [0,
                        '支付金额小于最小要求金额！最小支付金额' . $this->peizhi['minpay'] . '元'];
                }

                if ($this->peizhi['ifsms'] == 1) {
                    if ($this->user['ifcheckphone'] != 1) {
                        return [0,
                            '该账户未开启手机认证，请先绑定手机号！'];
                    }

                    //判断短信密码
                    $result = SL('Logsms')->checksms(array(
                        'code' => $code,
                        'phone' => $this->user['phone']));
                    if ($result[0] != 1) {
                        return $result;
                    }
                }

                $zijian = SM('Pay');
                $zijian->dbStartTrans(); //开始事务

                $userBuffer = SM('User')->findData('*', 'userid="' . $this->user['userid'] . '"');
                $flag = false;
                if ($zijian->addAllData($newArr) === false)
                    $flag = true;

                //支付后更新金额
                $newmoney = $userBuffer['money'] - $totalMoney;
                $newtx = $userBuffer['tx'] + $tixianMoney;
                $result = SM('User')->conAddData('money=money-' . $totalMoney, 'userid=' . $userBuffer['userid'], 'money');
                if ($result === false)
                    $flag = true;
                $result = SM('User')->conAddData('tx=tx+' . $tixianMoney, 'userid=' . $userBuffer['userid'], 'tx');
                if ($result === false)
                    $flag = true;

                //添加资金变动
                $data = array(
                    'userid' => $this->user['userid'],
                    'leavemoney' => $newmoney,
                    'changemoney' => 0 - $totalMoney,
                    'desc' => '支付：' . $tixianMoney . '元，手续费：' . $shouxuMoney . '元',
                    'style' => 2,
                    'ddh' => 'pldf' . $i . time(),
                );
                $result = SL('Pay')->moneylogadd($data);
                if ($result === false)
                    $flag = true;

                if ($flag) {
                    $zijian->dbRollback();
                    return [0,
                        '申请失败。'];
                } else {
                    $zijian->dbCommit();
                    //自动代付提交
                    if (($this->peizhi['ifdaifuauto'] == 1 && $this->user['ifdaifuauto'] == -1) || $this->user['ifdaifuauto'] == 1) {
                        $thisdaifuid = $this->peizhi['daifuid'];
                        $thisdaifubank = $this->peizhi['daifubank'];
                        if ($this->user['daifuid'] > 0) {
                            $thisdaifuid = $this->user['daifuid'];
                            $thisdaifubank = $this->user['daifubank'];
                        }
                        if ($thisdaifuid > 0) {
                            //代付提交
                            foreach ($newArr as $ii => $iiNewArr) {
                                $tmpBuffer = SM('Pay')->findData('ddh="' . $iiNewArr['ddh'] . '"');
                                $data = ['id' => $tmpBuffer['id'],
                                    'pzid' => $thisdaifuid,
                                    'paybank' => $thisdaifubank,
                                    'ifnotify' => 1 //不发送异步
                                ];
                                $result = SL('Pay')->dfSave($data);
                            }
                        }
                    }
                }

                //写入日志
                $this->userLog('申请支付', '申请结算【' . (string) $tixianMoney . '】手续费：' . (string) $shouxuMoney . '元,内容：' . serialize($newArr), $userBuffer['userid']);
                exit(header('location:' . U('Index/Home/txjl')));
            }

            //获取银行卡
            $yhk = $request['yhk'];
            if (empty($yhk)) {
                return [0,
                    '请选择银行卡！'];
            }
            $kaBuffer = SM('Ka')->findData('*', 'ifcheck=1 and userid=' . $this->user['userid'] . ' and id=' . $yhk);
            if (!$kaBuffer) {
                return [0,
                    '银行卡无法支付，请更换！'];
            }

            $sxf = SL('User')->calcdffl($request['money'], $dffl);
            $ddh = 'qzf' . time() . getDingdanRand();
            $data = array();
            $data['userid'] = $this->user['userid'];
            $data['money'] = $request['money'];
            $data['status'] = 0;
            $data['realname'] = $kaBuffer['username'];
            $data['ka'] = $kaBuffer['ka'];
            $data['address'] = $kaBuffer['address'];
            $data['zhihang'] = $kaBuffer['zhihang'];
            $data['sheng'] = $kaBuffer['sheng'];
            $data['shi'] = $kaBuffer['shi'];
            $data['lhh'] = $kaBuffer['lhh'];
            $data['ddh'] = $ddh;
            $data['dffl'] = $sxf;

            $needmoney = $data['money'] + $sxf;
            //判断支付金额
            if ($needmoney > $nowmoney) {
                return [0,
                    '支付金额不足,当前需要' . $needmoney . '元,手续费：' . $sxf . '元！'];
            }

            if ($data['money'] < $this->peizhi['minpay']) {
                return [0,
                    '支付金额小于最小要求金额！最低支付' . $this->peizhi['minpay'] . '元'];
            }

            if ($this->peizhi['ifsms'] == 1) {
                if ($this->user['ifcheckphone'] != 1) {
                    return [0,
                        '该账户未开启手机认证，请先绑定手机号！'];
                }

                //判断短信密码
                $result = SL('Logsms')->checksms(array(
                    'code' => $code,
                    'phone' => $this->user['phone']));
                if ($result[0] != 1) {
                    return $result;
                }
            }

            $zijian = SM('Pay');
            if ($act == 'add') {
                $data['addtime'] = time();
                $zijian->dbStartTrans(); //开始事务

                $userBuffer = SM('User')->findData('*', 'userid="' . $this->user['userid'] . '"');
                $flag = false;
                if (($daifuid = $zijian->insertData($data)) === false)
                    $flag = true;

                //支付后更新金额
                $newmoney = $userBuffer['money'] - $needmoney;
                $newtx = $userBuffer['tx'] + $data['money'];
                $result = SM('User')->conAddData('money=money-' . $needmoney, 'userid=' . $userBuffer['userid'], 'money');
                if ($result === false)
                    $flag = true;
                $result = SM('User')->conAddData('tx=tx+' . $data['money'], 'userid=' . $userBuffer['userid'], 'tx');
                if ($result === false)
                    $flag = true;

                //添加资金变动
                $data = array(
                    'userid' => $this->user['userid'],
                    'leavemoney' => $newmoney,
                    'changemoney' => 0 - $needmoney,
                    'desc' => '支付：' . $data['money'] . '元，手续费：' . $sxf . '元',
                    'style' => 2,
                    'ddh' => $ddh,
                );
                $result = SL('Pay')->moneylogadd($data);

                if ($result === false || $flag) {
                    $zijian->dbRollback();
                    return [0,
                        '申请失败。'];
                } else {
                    $zijian->dbCommit();

                    //自动代付提交
                    if (($this->peizhi['ifdaifuauto'] == 1 && $this->user['ifdaifuauto'] == -1) || $this->user['ifdaifuauto'] == 1) {
                        $thisdaifuid = $this->peizhi['daifuid'];
                        $thisdaifubank = $this->peizhi['daifubank'];
                        if ($this->user['daifuid'] > 0) {
                            $thisdaifuid = $this->user['daifuid'];
                            $thisdaifubank = $this->user['daifubank'];
                        }
                        if ($thisdaifuid > 0) {
                            $data = [
                                'id' => $daifuid,
                                'pzid' => $thisdaifuid,
                                'paybank' => $thisdaifubank,
                                'ifnotify' => 1 //不发送异步
                            ];
                            $result = SL('Pay')->dfSave($data);
                        }
                    }
                }

                //写入日志
                $this->userLog('申请支付', '申请支付【' . $request['money'] . '元】手续费：' . $sxf . '元', $data['userid']);
                return [1,
                    '申请成功！',
                    U('Index/Home/txjl')];
            } elseif ($act == 'edit') {

            }
        }
        $kaBuffer = SM('Ka')->selectData('*', 'userid=' . $this->user['userid'] . ' and ifcheck=1');

        $params = array(
            'act' => 'add',
            'txpaytime' => $txpaytime,
            'nowmoney' => stringChange('formatMoney', $nowmoney),
            'ka' => $kaBuffer,
            'dffl' => $dffl,
            'todaymoney' => $todaymoney,
            'balancestyle' => $balancestyle,
            'pageName' => '代付信息'
        );
        return [1,
            $params];
    }

    /**
     * 支付记录
     */
    public function txjl($request) {
        $map = array();

        $data = ' userid=' . $this->user['userid'] . ' ';

        //高级查询
        if (!empty($request['ddh'])) {
            $map['ddh'] = $request['ddh'];
            $data .=' AND ddh = "' . $request['ddh'] . '" ';
        }
        if (is_numeric($request['status'])) {
            $map['status'] = $request['status'];
            $data .=' AND status = "' . $request['status'] . '" ';
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

        $perpage = C('FX_PERPAGE'); //每页行数
        $pay = SM('Pay');
        $count = $pay->selectCount(
                $data, 'id'
        ); // 查询满足要求的总记录数
        // 进行分页数据查询 注意limit方法的参数要使用Page类的属性
        $page = page($count, $request['p'], $perpage) . ',' . $perpage;

        $list = $pay->pageData(
                '*', $data, 'id DESC', $page
        );
        $payLogic = SL('Pay');
        foreach ($list as $i => $iList) {
            if (empty($list[$i]['notifyurl']) || $list[$i]['tz'] == 0) {
                $list[$i]['tzname'] = '-';
            } else {
                if ($list[$i]['tz'] == 1)
                    $list[$i]['tzname'] = '通知失败';
                if ($list[$i]['tz'] == 2)
                    $list[$i]['tzname'] = '通知成功';
            }
            $list[$i]['addtime'] = stringChange('formatDateTime', $iList['addtime']);
            $list[$i]['statusname'] = $payLogic->zt[$list[$i]['status']];
            $list[$i] = stringChange('formatMoneyByArray', $list[$i], array(
                'money',
                'dffl'));
        }
        $pageList = $this->pageList($count, $perpage, $map);

        //统计数据
        $today = strtotime(date('Y-m-d', time()));
        $yes = strtotime(date('Y-m-d', time() - 24 * 3600));
        $tj = array();
        $tj['today'] = $pay->sumData('money', 'status<3 and userid=' . $this->user['userid'] . ' and addtime>=' . $today);
        $tj['yes'] = $pay->sumData('money', 'status<3 and userid=' . $this->user['userid'] . ' and (addtime between ' . $yes . ' and ' . $today . ')');
        $tj['all'] = $pay->sumData('money', 'status<3 and userid=' . $this->user['userid']);
        foreach ($tj as $i => $iTj) {
            if (empty($iTj))
                $tj[$i] = 0;
        }
        $tj = stringChange('formatMoneyByArray', $tj, array(
            'today',
            'yes',
            'all'));

        $params = array(
            'list' => $list,
            'tj' => $tj,
            'page' => $pageList,
            'balancestyle' => $this->getBalanceStyle(),
            'pageName' => '支付记录'
        );
        return [1,
            $params];
    }

    /**
     * 代付订单重发
     */
    public function txdingdancf($request) {
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
        $payBuffer['notifystyle'] = $fj['fxnotifystyle'];
        $payBuffer['sigleddh'] = $ddhYuan;

        $params = array(
            'edit' => $payBuffer,
            'act' => 'edit',
            'pageName' => '订单重发'
        );
        return [1,
            $params];
    }

    /**
     * 代付查询记录
     */
    public function txselect($request) {
        if (!is_numeric($request['id'])) {
            return [0,
                '数据标识有误。'];
        }

        $pay = SM('PayDingdan');
        $list = $pay->selectData(
                '*', 'payid="' . $request['id'] . '"', 'id DESC'
        );

        $payother = SM('Jiekoupeizhi')->selectData('*', 'ifrepay=1', 'pzid desc');
        $payotherKey = stringChange('arrayKey', $payother, 'pzid');

        foreach ($list as $i => $iList) {
            $list[$i]['statusname'] = SL('Pay')->fanhuizt[$list[$i]['status']];
            if ($payotherKey[$list[$i]['pzid']])
                $list[$i]['paybankname'] = SA(ucfirst($payotherKey[$list[$i]['pzid']]['style']))->paybank[$list[$i]['paybank']];
            $list[$i]['pzname'] = $payotherKey[$list[$i]['pzid']]['pzname'];
            $list[$i]['addtime'] = stringChange('formatDateTime', $iList['addtime']);
            $list[$i]['paytime'] = stringChange('formatDateTime', $iList['paytime']);
        }

        $params = array(
            'list' => $list,
            'pageName' => '代付查询记录'
        );
        return [1,
            $params];
    }

    /**
     * 代付查询记录
     */
    public function txdingdanselect($request) {
        return SL('Pay')->dingdanselect($request);
    }

    /**
     * 我的费率
     * 费率相关 用户自定义 > 接口配置
     * 用户接口开关 用户自定义 > 接口类型配置
     * 接口开关 接口配置
     * 接口名称 接口状态 接口费率 接口开关
     */
    public function fl($request) {
        $httpBuffer = SL('Http')->getApiHttp($this->user['userid']);
        $apihttp = '';
        if (is_array($httpBuffer[1])) {
            $apihttp = stringChange('arrayKey', $httpBuffer[1], 'jkstyle');
        }

        $jiekouBuffer = SL('Api')->getUserOpenApi($this->user['userid']);
        if ($this->peizhi['hiddenclosejiekou'] == 1) {
            foreach ($jiekouBuffer as $i => $iJiekouBuffer) {
                if (strstr($jiekouBuffer[$i]['ifjkopen'], '关闭'))
                    unset($jiekouBuffer[$i]);
            }
        }

        $params = array(
            'list' => $jiekouBuffer,
            'apihttp' => $apihttp,
            'pageName' => '我的费率'
        );
        return [1,
            $params];
    }

    /**
     * 代理详情
     */
    public function dl($request) {

        $today = strtotime(date('Y-m-d'), time());
        $tom = strtotime(date('Y-m-d', time() + 24 * 3600));
        $month = strtotime(date('Y-m-1'), time());
        $year = strtotime(date('Y-1-1'), time());

        //总代理金
        $tj = array();
        $dailimoney = SM('DingdanAgent')->sumData('agentmoney', 'agent="' . $this->user['userid'] . '"');
        $tj['all'] = SM('Fandian')->sumData('havemoney', 'userid=' . $this->user['userid'] . ' and status=2');
        $tj['all']+=$dailimoney;

        //当天代理金
        $dailimoney = SM('DingdanAgent')->sumData('agentmoney', 'agent="' . $this->user['userid'] . '" and addtime between ' . $today . ' and ' . $tom);
        $tj['day'] = SM('Fandian')->sumData('havemoney', 'userid=' . $this->user['userid'] . ' and status=2 and addtime>=' . $today);
        $tj['day']+=$dailimoney;

        //当月代理金
        $dailimoney = SM('DingdanAgent')->sumData('agentmoney', 'agent="' . $this->user['userid'] . '" and addtime between ' . $month . ' and ' . $tom);
        $tj['month'] = SM('Fandian')->sumData('havemoney', 'userid=' . $this->user['userid'] . ' and status=2 and addtime>=' . $month);
        $tj['month']+=$dailimoney;

        //商户总数
        $tj['user'] = SM('User')->selectCount('agent=' . $this->user['userid'], 'id');
        //商户当天充值量
        $tj['usertoday'] = SM('Dingdan')->getAgentMoneyByAgent($this->user['userid'], $today, $tom);
        //商户当月充值量
        $tj['usermonth'] = SM('Dingdan')->getAgentMoneyByAgent($this->user['userid'], $month, $tom);
        //商户当年充值量
        $tj['useryear'] = SM('Dingdan')->getAgentMoneyByAgent($this->user['userid'], $year, $tom);
        foreach ($tj as $i => $iTj) {
            if (empty($iTj))
                $tj[$i] = 0;
        }

        $tj = stringChange('formatMoneyByArray', $tj, 'all,month,day,usertoday,usermonth,useryear');

        $params = array(
            'tj' => $tj,
            'pageName' => '代理详情'
        );
        return [1,
            $params];
    }

    /**
     * 代理商户列表
     */
    public function dluser($request) {
        $showpz = 0; //是否显示配置
        if ($this->peizhi['openagentfl'] == 1) {
            $showpz = 1;
        }

        $level = $request['level']; //显示层级
        $sn = $request['sn']; //当前秘钥
        if ($sn)
            $map['sn'] = $sn;
        if (empty($level))
            $level = 1;
        $map['level'] = $level;

        $map = array();
        $data = ' agent=' . $this->user['userid'] . ' ';

        //判断是否显示下级商户
        $showuser = 0;
        if ($this->peizhi['ifagentlevel'] > 1) {
            $id = $request['id'];
            if ($id > 0) {
                if (md5($level . $id . $this->user['id'] . C('FX_QRCODE_KEY')) != $sn) {
                    return [0,
                        '数据异常，请刷新重试。'];
                }
                $userBuffer = SM('User')->findData('userid', 'id=' . $id);
                if (!$userBuffer) {
                    return [0,
                        '用户不存在。'];
                }

                $map['id'] = $id;
                $data = ' agent=' . $userBuffer['userid'] . ' ';

                $showpz = 0;
                $level+=1;
            }

            if ($level < $this->peizhi['ifagentlevel']) {
                $showuser = 1;
            }

            //判断当前是第几级，控制显示范围
            if ($level > $this->peizhi['ifagentlevel']) {
                return [0,
                    '您不能查看该用户的信息。'];
            }
        }

        //高级查询
        if ($request['userid']) {
            $map['userid'] = $request['userid'];
            $data.=' AND userid ="' . $request['userid'] . '" ';
        }
        if ($request['qq']) {
            $map['qq'] = $request['qq'];
            $data.=' AND qq ="' . $request['qq'] . '" ';
        }
        if (is_numeric($request['status'])) {
            $map['status'] = $request['status'];
            $data.=' AND status ="' . $request['status'] . '" ';
        }
        $perpage = C('FX_PERPAGE'); //每页行数
        $user = SM('User');
        $count = $user->selectCount(
                $data, 'id'
        ); // 查询满足要求的总记录数
        // 进行分页数据查询 注意limit方法的参数要使用Page类的属性
        $page = page($count, $request['p'], $perpage) . ',' . $perpage;

        $list = $user->pageData(
                '*', $data, 'id DESC', $page
        );

        foreach ($list as $i => $iList) {
            $list[$i]['addtime'] = stringChange('formatDateTime', $iList['addtime']);
            $list[$i]['statusname'] = $iList['status'] == 1 ? '锁定' : '正常';
            $list[$i] = stringChange('formatMoneyByArray', $list[$i], array(
                'money',
                'tx'));
            $list[$i]['sn'] = md5($level . $iList['id'] . $this->user['id'] . C('FX_QRCODE_KEY')); //当前秘钥
        }

        $pageList = $this->pageList($count, $perpage, $map);

        //代理开户
        $openuser = 0;
        if ($this->user['ifagentopenuser'] == 1 || ($this->peizhi['ifagentopenuser'] == 1 && $this->user['ifagentopenuser'] == -1)) {
            $openuser = 1;
        }

        $params = array(
            'list' => $list,
            'openuser' => $openuser,
            'page' => $pageList,
            'showuser' => $showuser, //是否显示代理下级用户
            'showpz' => $showpz, //是否显示配置 仅显示用户直属下级配置
            'level' => $level, //当前等级
            'pageName' => '商户列表'
        );
        return [1,
            $params];
    }

    /**
     * 代理开户
     */
    public function dladduser($request) {
        //是否开启
        if ($this->user['ifagentopenuser'] === 0 || ($this->peizhi['ifagentopenuser'] == 0 && $this->user['ifagentopenuser'] == -1)) {
            return [0,
                '代理开户功能未开启，请联系管理员。'];
        }

        if (IS_POST) {
            $ifagent = $request['ifagent'];
            if (empty($ifagent) || !is_numeric($ifagent))
                $ifagent = 0;
            $phone = $request['phone'];
            $userName = $phone;
            $password = $request['pass'];
            $password1 = $request['pass1'];
            $txmm = $request['txmm'];
            $txmm1 = $request['txmm1'];
            $qq = $request['qq'];
            $email = $request['email'];
            $yzm = $request['yzm'];
            $ifcheckphone = 0;

            //获取配置信息
            $peizhi = $this->peizhi;

            //密码规范
            if (!checkString('checkUserPassWord', $password)) {
                return [0,
                    '密码长度大于8，数字，字母组合！'];
            }
            if ($password1 != $password) {
                return [0,
                    '两次输入的密码不一致！'];
            }
            if (!checkString('checkUserPassWord', $txmm)) {
                return [0,
                    '支付密码长度大于8，数字，字母组合！'];
            }
            if ($txmm != $txmm1) {
                return [0,
                    '两次输入的支付密码不一致！'];
            }
            if (is_numeric($qq) && strlen($qq) < 5) {
                return [0,
                    '请输入正确的qq号！'];
            } else {
                if (!is_numeric($qq))
                    $qq = '';
            }
            if (empty($phone) || !checkString('checkIfPhone', $phone))
                return [0,
                    '请输入正确的手机号！'];
            if (!empty($email) && !checkString('checkIfEmail', $email)) {
                return [0,
                    '请输入正确的邮箱地址！'];
            } else {
                if (empty($email))
                    $email = '';
            }

            $user = SM('User');
            //检查名称重复
            $buffer = $user->selectData(
                    'userid', 'username="' . $userName . '" or phone="' . $phone . '"');
            if ($buffer) {
                return [0,
                    '用户名或手机号重复请更换'];
            }

            //验证码
            if (md5($yzm) != session('verify')) {
                return [0,
                    '验证码错误！'];
            }

            $data = array();
            $data['savecode'] = $user->saveCode();
            $data['username'] = $userName;
            $data['addtime'] = time();
            $data['password'] = md5($data['username'] . $password);
            $data['txpassword'] = md5($data['username'] . $txmm);
            $data['qq'] = $qq;
            $data['phone'] = $phone;
            $data['email'] = $email;
            $data['miyao'] = stringChange('randString', 32);
            $data['lastip'] = '';
            $data['addtime'] = time();
            $data['ifagent'] = $ifagent;
            $data['agent'] = $this->user['userid'];
            $status = 0;
            if ($this->peizhi['ifagentopenusercheck'] != 1)
                $status = 1;
            $data['status'] = $status;
            $data['ifcheckphone'] = $ifcheckphone;

            $data['userid'] = $user->createUserID();
            if ($user->insertData($data) === false) {
                return [0,
                    '添加失败'];
            } else {
                //写入用户扣量
                SL('Kou')->addUser($data['userid']);

                //写入日志
                $this->userLog('代理管理', '代理注册商户【' . $data['userid'] . '】', $data['userid']);
                return [1,
                    '添加下级商户成功！',
                    U("Index/Home/dluser")];
            }
        }

        $params = array(
            'act' => 'add',
            'pageName' => '添加下级商户'
        );
        return [1,
            $params];
    }

    /**
     * 代理商户费率调整
     */
    public function dlfl($request) {
        if ($this->peizhi['openagentfl'] != 1) {
            return [0,
                '该功能未开启。'];
        }

        $id = $request['id'];
        if (empty($id) || !is_numeric($id)) {
            return array(
                0,
                '数据标识错误。');
        }
        $user = SM('User');
        $userBuffer = $user->findData('*', 'id=' . $id);

        if ($this->user['userid'] != $userBuffer['agent']) {
            return array(
                0,
                '数据不存在。');
        }

        if (!$userBuffer) {
            return array(
                0,
                '数据不存在。');
        }


        $userJk = SL('Api')->getUserOpenApi($userBuffer['userid']);
        $agentJk = SL('Api')->getUserOpenApi($this->user['userid']);
        if (IS_POST) {
            //去掉没有权限的jkid
            $agentJiekouBuffer = stringChange('arrayKey', $agentJk, 'jkid');

            $jkid = $request['jkid'];
            $zjBuffer = array();
            foreach ($jkid as $iJkid) {
                if (empty($request['flselect_' . $iJkid]))
                    $fl = 0;
                else
                    $fl = $request['fl_' . $iJkid];
                if (empty($fl))
                    $fl = 0;

                if ($agentJiekouBuffer[$iJkid]['ifshowopen'] == 0)
                    continue;

                //提示费率错误的接口信息
                if ($agentJiekouBuffer[$iJkid]['flnum'] > $fl && $fl != 0) {
                    return [0,
                        '操作失败，代理费率不能设置过低！'];
                }
                if ($fl > 100) {
                    return [0,
                        '操作失败，代理费率不能设置超过100！'];
                }

                $zjBuffer[] = array(
                    'jkid' => $iJkid,
                    'userid' => $userBuffer['userid'],
                    'fl' => $fl,
                    'ifopen' => $request['ifopen_' . $iJkid]
                );
            }

            $jiekouUser = SM('JiekouUser');
            $row = $jiekouUser->selectData('*', 'userid="' . $userBuffer['userid'] . '"');
            if ($row)
                $row = stringChange('arrayKey', $row, 'jkid');
            foreach ($zjBuffer as $i => $iZjBuffer) {
                if ($row[$iZjBuffer['jkid']]['id']) { //修改数据
                    $jiekouUser->updateData($iZjBuffer, 'id=' . $row[$iZjBuffer['jkid']]['id']);
                } else {
                    //写入数据
                    $jiekouUser->insertData($iZjBuffer);
                }
            }
            //写入日志
            $this->userLog('代理管理', '代理修改用户费率【' . $userBuffer['userid'] . '】的数据', $this->user['userid']);
            return [1,
                '保存成功！',
                U('Index/Home/dluser')];
        }

        //隐藏本站关闭的接口
        if ($this->peizhi['hiddenclosejiekou'] == 1) {
            foreach ($userJk as $i => $iUserJk) {
                if ($iUserJk['ifjkopennum'] == 0) {
                    unset($userJk[$i]);
                }
            }
        }

        $params = array(
            'edit' => $userBuffer,
            'act' => 'edit',
            'list' => $userJk,
            'agentList' => $agentJk,
            'pageName' => $this->moduleName . '费率管理'
        );
        return [1,
            $params];
    }

    /**
     * 代理订单
     */
    public function dldingdan($request) {
        $map = array();
        $data = ' 1=1 ';
        if (is_numeric($request['status'])) {
            $map['status'] = $request['status'];
            if ($request['status'] == 0) {
                $data .=' AND d.status in (0,2) ';
            } else {
                $data .=' AND d.status = "1" ';
            }
        }

        //高级查询
        if ($request['ordernum']) {
            $map['ordernum'] = $request['ordernum'];
            $data.=' AND d.ordernum = "' . $request['ordernum'] . '" ';
        }
        if ($request['jkstyle']) {
            $map['jkstyle'] = $request['jkstyle'];
            $data.=' AND d.jkstyle = "' . $request['jkstyle'] . '" ';
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
            $data .= ' AND d.paytime between ' . ($start) . ' and ' . ($end) . ' ';
        }

        $perpage = C('FX_PERPAGE'); //每页行数
        $order = SM('Dingdan');
        $count = $order->getAgentCount($this->user['userid'], $data); // 查询满足要求的总记录数
        // 进行分页数据查询 注意limit方法的参数要使用Page类的属性
        $page = page($count, $request['p'], $perpage) . ',' . $perpage;

        $list = $order->getAgentList($this->user['userid'], $data, $page);

        $jiekou = SM('Jiekou')->selectData('*', '1=1', 'jkid asc');
        $jiekoubuffer = stringChange('arrayKey', $jiekou, 'jkstyle');

        $dingdanLogic = SL('Dingdan');
        foreach ($list as $i => $iList) {
            if ($iList['status'] == 2) {
                $list[$i]['status'] = 0;
                $list[$i]['paytime'] = 0;
                $list[$i]['tz'] = 0;
                $list[$i]['preordernum'] = '';
            }

            $list[$i]['addtime'] = stringChange('formatDateTime', $list[$i]['addtime']);
            $list[$i]['paytime'] = stringChange('formatDateTime', $list[$i]['paytime']);
            if ($list[$i]['preordernum'] == '')
                $list[$i]['preordernum'] = '-';
            $list[$i]['tzzt'] = $dingdanLogic->tzzt[$list[$i]['tz']];
            $list[$i]['status'] = $dingdanLogic->zt[$list[$i]['status']];
            $list[$i]['jkstyle'] = $jiekoubuffer[$list[$i]['jkstyle']]['jkname'];
            $list[$i]['dailimoney'] = $list[$i]['agentmoney'];
            $list[$i] = stringChange('formatMoneyByArray', $list[$i], array(
                'totalmoney',
                'havemoney',
                'dailimoney'));
        }
        $pageList = $this->pageList($count, $perpage, $map);

        //统计数据 今日订单笔数     昨日订单笔数     今日订单总金额     今日支出金额     昨日订单总金额     昨日支出金额     历史总笔数     历史总金额     历史总支出
        $today = strtotime(date('Y-m-d', time()));
        $tom = strtotime(date('Y-m-d', time() + 24 * 3600));
        $yes = strtotime(date('Y-m-d', time() - 24 * 3600));
        $tj = array();
        $tj['today'] = $order->getAgentSum($this->user['userid'], $today, $tom);
        $tj['yes'] = $order->getAgentSum($this->user['userid'], $yes, $today);
        $tj['all'] = $order->getAgentSum($this->user['userid']);
        foreach ($tj as $i => $iTj) {
            if (empty($iTj))
                $tj[$i] = 0;
        }
        $tj = stringChange('formatMoneyByArray', $tj, array(
            'today',
            'yes',
            'all'));

        $params = array(
            'list' => $list,
            'tj' => $tj,
            'page' => $pageList,
            'jiekou' => $jiekou,
            'pageName' => '商户交易记录'
        );
        return [1,
            $params];
    }

    /**
     * 代理返点
     */
    public function dlfandian($request) {
        $fandianLogic = SL('Agent');
        $fandianLogic->fandianupdate(); //更新返点

        $data = ' userid=' . $this->user['userid'];

        $fandian = SM('Fandian');
        $count = $fandian->selectCount(
                $data, 'id'); // 查询满足要求的总记录
        // 进行分页数据查询 注意limit方法的参数要使用Page类的属性
        $perpage = C('FX_PERPAGE'); //每页行数
        $page = page($count, $request['p'], $perpage) . ',' . $perpage;
        $list = $fandian->pageData(
                '*', $data, 'id DESC', $page
        );

        foreach ($list as $i => $iList) {
            $list[$i]['statusname'] = $fandianLogic->zt[$iList['status']];
            $list[$i]['addtime'] = stringChange('formatDateTime', $iList['addtime']);
            $list[$i] = stringChange('formatMoneyByArray', $list[$i], array(
                'totalmoney',
                'havemoney',
                'fl'));
        }
        $pageList = $this->pageList($count, $perpage, $map);

        $params = array(
            'list' => $list,
            'page' => $pageList,
            'pageName' => '代理返点'
        );
        return [1,
            $params];
    }

    /**
     * 接入支付
     */
    public function api($request) {
        if (IS_POST) { //调用支付接口
            $pay = $request['pay'];
            if ($this->peizhi['baozhengjin'] > 0) {
                if ($this->peizhi['bzjuserid'] == 0)
                    $this->peizhi['bzjuserid'] = 2017100;
                $userBuffer = SM('User')->findData('userid,miyao', 'userid=' . $this->peizhi['bzjuserid']);
                if (empty($userBuffer)) {
                    return [0,
                        '收款账户不存在，请联系客服。'];
                }
                $ddh = time() . getDingdanRand(); //商户订单号
                session('ddh', $ddh); //session存储商户订单号
                $data = array(
                    "fxid" => $userBuffer['userid'], //商户号
                    "fxkey" => $userBuffer['miyao'], //商户秘钥key 从用户后台获取
                    "fxddh" => $ddh, //商户订单号
                    "fxdesc" => "baozhengjin", //商品名
                    "fxfee" => $this->peizhi['baozhengjin'], //支付金额 单位元
                    "fxattch" => $this->user['userid'] . 'baozhengjin', //附加信息
                    "fxnotifyurl" => $this->peizhi['httpstyle'] . '://' . $_SERVER['HTTP_HOST'] . "/Test/notifyUrl", //异步回调 , 支付结果以异步为准
                    "fxbackurl" => $this->peizhi['httpstyle'] . '://' . $_SERVER['HTTP_HOST'] . "/Test/backUrl", //同步回调 不作为最终支付结果为准，请以异步回调为准
                    "fxpay" => $pay, //支付类型 此处可选项为 微信公众号：wxgzh   微信H5网页：wxwap  微信扫码：wxsm   支付宝H5网页：zfbwap  支付宝扫码：zfbsm 等参考API
                    "fxip" => get_client_ip(0, true), //支付端ip地址
                    "fxddstyle" => 2
                );

                //获取支付网关
                $httpBuffer = SL('Http')->getApiHttp($userBuffer['userid']);
                $wg = '';
                if (is_array($httpBuffer[1])) {
                    $apihttp = stringChange('arrayKey', $httpBuffer[1], 'jkstyle');
                    $wg = $apihttp[$pay]['thishttp'];
                } else {
                    $wg = $httpBuffer[1];
                }
                $wg.='/Pay';

                $data["fxsign"] = md5($data["fxid"] . $data["fxddh"] . $data["fxfee"] . $data["fxnotifyurl"] . $data["fxkey"]); //加密
                $r = curl($wg, $data);
                $backr = json_decode($r, true); //json转数组
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
                return $return;
            }
            return [0,
                '支付成功'];
        }


        //系统配置保证金 用户没有支付保证金
        if ($this->peizhi['baozhengjin'] > 0 && $this->user['regmoney'] < $this->peizhi['baozhengjin']) {
            //$jiekou = SM('Jiekou')->selectData('*', 'jkstyle like "%sm"', 'jkid asc');
            //$jiekoubuffer = stringChange('arrayKey', $jiekou, 'jkstyle');
            //判断是否有可用的接口
            $list = SL('Api')->getOpenApi();

            $params = array(
                'baozhengjin' => 1,
                'list' => $list,
                'pageName' => '支付保证金'
            );
            return [1,
                $params];
        }

        $httpBuffer = SL('Http')->getApiHttp($this->user['userid']);
        $apihttp = '';
        if (is_array($httpBuffer[1])) {
            $apihttp = '<a href="' . U('Index/Home/fl') . '">网关地址</a>';
        } else {
            $apihttp = $httpBuffer[1] . '/Pay';
        }

        //获取支付时间
        $result = SL('Param')->formatPayTime();
        $txpaytime = implode('-', $result);

        //获取银行列表
        $bankBuffer = SM('Bank')->selectData('bankname,bankcode', 'status=0', 'orderid asc,id asc');

        $list = SL('Api')->getUserOpenApi($this->user['userid'], 1);
        $params = array(
            'jiekou' => $list,
            'apihttp' => $apihttp,
            'txpaytime' => $txpaytime,
            'bank' => $bankBuffer,
            'balancestyle' => $this->getBalanceStyle(),
            'pageName' => '接口Api'
        );
        return [1,
            $params];
    }

    /**
     * 接入支付
     */
    public function money($request) {
        if (IS_POST) { //调用支付接口
            $pay = $request['pay'];
            $money = $request['money'];
            if ($money < $this->peizhi['minrecharge'] || !is_numeric($money)) {
                return [0,
                    '充值金额有误，充值金额不能小于' . $this->peizhi['minrecharge'] . '元。'];
            }
            $fxkey = $this->user['miyao'];

            $ddh = time() . getDingdanRand(); //商户订单号
            session('ddh', $this->user['userid'] . $ddh); //session存储商户订单号
            $data = array(
                "fxid" => $this->user['userid'], //商户号
                "fxddh" => $ddh, //商户订单号
                "fxdesc" => '用户：' . $this->user['userid'] . "充值" . $money . '元', //商品名
                "fxfee" => $money, //支付金额 单位元
                "fxattch" => $this->user['userid'] . "@recharge@" . $money, //附加信息
                "fxnotifyurl" => $this->peizhi['httpstyle'] . "://" . $_SERVER['HTTP_HOST'] . "/Test/notifyUrl", //异步回调 , 支付结果以异步为准
                "fxbackurl" => $this->peizhi['httpstyle'] . "://" . $_SERVER['HTTP_HOST'] . "/Test/backUrl", //同步回调 不作为最终支付结果为准，请以异步回调为准
                "fxpay" => $pay, //支付类型 此处可选项为 微信公众号：wxgzh   微信H5网页：wxwap  微信扫码：wxsm   支付宝H5网页：zfbwap  支付宝扫码：zfbsm 等参考API
                "fxip" => get_client_ip(0, true), //支付端ip地址
                "fxddstyle" => 1
            );

            //获取支付网关
            $httpBuffer = SL('Http')->getApiHttp($this->user['userid']);
            $wg = '';
            if (is_array($httpBuffer[1])) {
                $apihttp = stringChange('arrayKey', $httpBuffer[1], 'jkstyle');
                $wg = $apihttp[$pay]['thishttp'];
            } else {
                $wg = $httpBuffer[1];
            }
            $wg.='/Pay';

            $data["fxsign"] = md5($data["fxid"] . $data["fxddh"] . $data["fxfee"] . $data["fxnotifyurl"] . $fxkey); //加密
            $r = curl($wg, $data);
            $backr = json_decode($r, true); //json转数组
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
            return $return;
        }

        $buffer = SM('User')->findData('*', 'userid="' . $this->user['userid'] . '"');
        $list = SL('Api')->getUserOpenApi($this->user['userid'], 1);
        $params = array(
            'yue' => $buffer['money'],
            'list' => $list,
            'pageName' => '充值中心'
        );
        return [1,
            $params];
    }

    /**
     * 商户二维码
     */
    public function myqrcode($request) {
        $userMd5 = md5($this->user['userid'] . $this->user['savecode']);
        $cache = unserialize(S('userinfo' . $userMd5));
        if (IS_POST) {
            $width = $_POST['width'];
            $height = $_POST['height'];
            $str = $_POST['str'];
            $name = $userMd5 . rand(1, 10);
            S('userinfo' . $userMd5, serialize(array(
                'str' => $str,
                'width' => $width,
                'height' => $height,
                'name' => $name
            )));
        } elseif ($cache) {
            $width = $cache['width'];
            $height = $cache['height'];
            $str = $cache['str'];
            $name = $cache['name'];
        } else {
            $width = 200;
            $height = 200;
            $name = $userMd5 . rand(1, 10);
        }

        if ($width < 200) {
            $width = 200;
        }
        if ($height < 200) {
            $height = 200;
        }

        //图片路径
        $name = '/Uploads/qrcode/' . ($this->user['userid'] % 10) . '/' . $name . '.png';
        $path = realpath(APP_PATH) . '/../www' . $name;
        $url = $this->peizhi['httpstyle'] . "://" . $_SERVER['HTTP_HOST'] . $name;
        $topurl = $this->peizhi['httpstyle'] . "://" . $_SERVER['HTTP_HOST'] . '/Pay/qrcode/uid/' . $this->user['userid'] . '/key/' . $userMd5;
        //$topurl = 'http://192.168.0.103:11002/Pay/qrcode/uid/' . $this->user['userid'] . '/key/' . $userMd5;

        if (IS_POST || !file_exists($path)) {
            if (empty($str)) {
                $width = '200';
                $height = '200';
                $str = '快捷支付';
            }

            //生成二维码图像
            import('Common.Tool.Image');
            \Image::moreQrcode($str, getQrcode($topurl, $width, $height), $path, '由' . $this->peizhi['sitename'] . '提供技术支持', $width);
        }

        $params = array(
            'img' => $url,
            'width' => $width,
            'str' => $str,
            'height' => $height,
            'pageName' => '商户二维码'
        );

        return [1,
            $params];
    }

    /**
     * 重置商户秘钥
     */
    public function resetkey($request) {
        $txmm = $request['paypass'];
        //验证用户支付密码
        if ($this->user['txpassword'] != md5($this->user['username'] . $txmm)) {
            return [0,
                '支付密码错误！'];
        }
        $miyao = stringChange('randString', 32);
        $result = SM('User')->updateData(['miyao' => $miyao], ['userid' => $this->user['userid']]);
        if ($result === false) {
            return [0,
                '秘钥重置失败！请重试。'];
        }
        return [1,
            $miyao];
    }

    /**
     * 订单导出
     */
    public function dingdanExplode($request) {
        //对账数据
        if ($request['time']) {
            $return = $this->dingdancheckwhere($request);
            $excelName = array(
                'title' => '对账列表' . date('Y-m-d', $request['time']),
                'excelName' => '对账列表导出Excel' . "_" . date("Y-m-d", $request['time']) . "_" . time());
        } else {
            $excelName = array(
                'title' => '订单列表',
                'excelName' => '订单列表导出Excel' . "_" . date("Y-m-d", time()) . "_" . time());
            $return = $this->dingdanwhere($request);
        }
        $map = $return[0];
        $data = $return[1];

        $perpage = 3000; //每页行数
        $order = SM('Dingdan');
        $count = $order->selectCount(
                $data, 'ddid'
        ); // 查询满足要求的总记录数
        // 进行分页数据查询 注意limit方法的参数要使用Page类的属性
        $page = page($count, $request['p'], $perpage) . ',' . $perpage;

        $list = $order->pageData(
                '*', $data, 'ddid DESC', $page
        );

        $jiekou = SM('Jiekou')->selectData('*', '1=1', 'jkid asc');
        $jiekoubuffer = stringChange('arrayKey', $jiekou, 'jkstyle');

        $dingdanLogic = SL('Dingdan');
        $excelData = array();
        foreach ($list as $i => $iList) {
            if ($iList['status'] == 2) {
                $list[$i]['status'] = 0;
                $list[$i]['paytime'] = 0;
                $list[$i]['tz'] = 0;
                $list[$i]['preordernum'] = '';
            }

            $list[$i] = stringChange('formatMoneyByArray', $list[$i], array(
                'totalmoney',
                'havemoney'));
            $excelData[$i][] = $iList['ddid'];
            $excelData[$i][] = $iList['userid'];
            $excelData[$i][] = ' ' . $this->dingdanchangenum($iList['ordernum']);
            $excelData[$i][] = ' ' . $iList['preordernum'];
            $excelData[$i][] = $iList['totalmoney'];
            $excelData[$i][] = $iList['havemoney'];
            $excelData[$i][] = $dingdanLogic->zt[$list[$i]['status']];
            $excelData[$i][] = stringChange('formatDateTime', $iList['addtime']);
            $excelData[$i][] = stringChange('formatDateTime', $iList['paytime']);
            $excelData[$i][] = $jiekoubuffer[$list[$i]['jkstyle']]['jkname'];
            $excelData[$i][] = $dingdanLogic->tzzt[$list[$i]['tz']];
        }

        $keyName = array(
            'ID',
            '商户id',
            '订单号',
            '平台订单号',
            '实收金额',
            '支出金额',
            '状态',
            '添加时间',
            '支付时间',
            '通道',
            '通知');
        $keyWidth = array(
            '10',
            '10',
            '20',
            '30',
            '10',
            '10',
            '10',
            '40',
            '40',
            '20',
            '20');
        $excelMsg = array();
        for ($i = 0; $i < count($keyName); $i++) {
            $excelMsg[$i]['keyNum'] = chr(65 + $i);
            $excelMsg[$i]['keyNum2'] = chr(65 + $i) . '1';
            $excelMsg[$i]['keyName'] = $keyName[$i];
            $excelMsg[$i]['width'] = $keyWidth[$i];
        }
        include_once COMMON_PATH . '/Tool/Excel/Classes/PHPExcel/IOFactory.php';
        $objPHPExcel = new \PHPExcel();
        $objWriter = new \PHPExcel_Writer_Excel5($objPHPExcel);
        $objPHPExcel->setActiveSheetIndex(0);
        foreach ($excelMsg as $i => $iExcelMsg) {
            $objPHPExcel->getActiveSheet()->getColumnDimension($iExcelMsg["keyNum"])->setWidth($iExcelMsg["width"]);
        }
        $excelObj = $objPHPExcel->setActiveSheetIndex(0);
        foreach ($excelMsg as $i => $iExcelMsg) {
            $excelObj = $excelObj->setCellValue($iExcelMsg['keyNum2'], $iExcelMsg["keyName"]);
        }
        if ($excelData) {
            foreach ($excelData as $i => $iValue) {
                $j = $i + 2;
                $contentObj = $objPHPExcel->setActiveSheetIndex(0);
                foreach ($iValue as $k => $kValue) {
                    $tmp = array();
                    if (strstr($kValue, '{#urlTag#}')) {
                        $tmp = explode('{#urlTag#}', $kValue);
                        $kValue = $tmp[0];
                    }
                    $contentObj = $contentObj->setCellValue($excelMsg[$k]['keyNum'] . $j, $kValue);
                    if (!empty($tmp[1])) {
                        $contentObj->getCell($excelMsg[$k]['keyNum'] . $j)->getHyperlink()->setUrl($tmp[1]);
                    }
                }
            }
        } else {
            $j = 2;
            $noMsg = $objPHPExcel->setActiveSheetIndex(0);
            foreach ($excelMsg as $i => $iExcelMsg) {
                $noMsg = $noMsg->setCellValue($iExcelMsg['keyNum'] . $j, '暂无数据');
            }
        }
        $objPHPExcel->getActiveSheet()->setTitle($excelName['title']);
        $objPHPExcel->setActiveSheetIndex(0);
        //header("Content-Type:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=utf-8");
        $filename = iconv('UTF-8', 'GB2312//IGNORE', $excelName['excelName'] . ".xls");

        $objWriter = \PHPExcel_IOFactory :: createwriter($objPHPExcel, "Excel5");
        if ($path) {
            $objWriter->save($path);
            return;
        }
        header('content-Type:application/vnd.ms-excel;charset=utf-8');
        header("Content-Disposition: attachment;filename=\"" . $filename . "\"");
        header("Cache-Control: max-age=0");
        $objWriter->save("php://output");
        exit();
    }

    /**
     * 订单对账
     */
    public function dingdancheck($request) {

        $return = $this->dingdancheckwhere($request);
        $map = $return[0];
        $data = $return[1];

        $perpage = C('FX_PERPAGE'); //每页行数
        $order = SM('Dingdan');
        $count = $order->selectCount(
                $data, 'ddid'
        ); // 查询满足要求的总记录数
        // 进行分页数据查询 注意limit方法的参数要使用Page类的属性
        $page = page($count, $request['p'], $perpage) . ',' . $perpage;

        $list = $order->pageData(
                '*', $data, 'ddid DESC', $page
        );

        $jiekou = SM('Jiekou')->selectData('*', '1=1', 'jkid asc');
        $jiekoubuffer = stringChange('arrayKey', $jiekou, 'jkstyle');

        $dingdanLogic = SL('Dingdan');
        foreach ($list as $i => $iList) {
            $list[$i]['ordernum'] = $this->dingdanchangenum($list[$i]['ordernum']);
            $list[$i]['addtime'] = stringChange('formatDateTime', $list[$i]['addtime']);
            $list[$i]['paytime'] = stringChange('formatDateTime', $list[$i]['paytime']);
            if ($list[$i]['preordernum'] == '')
                $list[$i]['preordernum'] = '-';
            $list[$i]['tzzt'] = $dingdanLogic->tzzt[$list[$i]['tz']];
            $list[$i]['status'] = $dingdanLogic->zt[$list[$i]['status']];
            $list[$i]['ddstylename'] = $dingdanLogic->ddstyle[$list[$i]['ddstyle']];
            $list[$i]['jkstyle'] = $jiekoubuffer[$list[$i]['jkstyle']]['jkname'];
            $list[$i] = stringChange('formatMoneyByArray', $list[$i], array(
                'totalmoney',
                'havemoney'));
        }
        $pageList = $this->pageList($count, $perpage, $map);

        //统计数据
        $tj = array();
        $tj['havemoney'] = $order->sumData('havemoney', $data);
        $tj['totalmoney'] = $order->sumData('totalmoney', $data);
        $tj['num'] = $count;
        foreach ($tj as $i => $iTj) {
            if (empty($iTj))
                $tj[$i] = 0;
        }
        $tj = stringChange('formatMoneyByArray', $tj, array(
            'havemoney',
            'totalmoney'));

        //最近30天的对账
        $yesday = strtotime(date('Y-m-d')) - 24 * 3600;
        $timebuffer = array();
        for ($i = 0; $i < 90; $i++) {
            $tmptime = $yesday - $i * 24 * 3600;
            $timebuffer[] = array(
                'time' => $tmptime,
                'name' => date('Y-m-d', $tmptime)
            );
        }

        $params = array(
            'list' => $list,
            'tj' => $tj,
            'page' => $pageList,
            'timebuffer' => $timebuffer,
            'pageName' => '对账记录'
        );
        return [1,
            $params];
    }

    /**
     * 资金变动
     */
    public function moneylog($request) {
        $map = array();

        $data = ' userid=' . $this->user['userid'] . ' ';
        //高级查询
        if ($request['style']) {
            $map['style'] = $request['style'];
            $data.=' AND style = "' . $request['style'] . '" ';
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

        $perpage = C('FX_PERPAGE'); //每页行数
        $order = SM('PayLog');
        $count = $order->selectCount(
                $data, 'id'
        ); // 查询满足要求的总记录数
        // 进行分页数据查询 注意limit方法的参数要使用Page类的属性
        $page = page($count, $request['p'], $perpage) . ',' . $perpage;

        $list = $order->pageData(
                '*', $data, 'id DESC', $page
        );

        $payLogic = SL('Pay');
        foreach ($list as $i => $iList) {
            //$list[$i]['addtime'] = date('Y-m-d H:i:s', $list[$i]['addtime']);
            $list[$i]['stylename'] = $payLogic->paylogzt[$list[$i]['style']];
            $list[$i] = stringChange('formatMoneyByArray', $list[$i], array(
                'changemoney',
                'leavemoney'));
        }
        $pageList = $this->pageList($count, $perpage, $map);

        $params = array(
            'list' => $list,
            'page' => $pageList,
            'style' => $this->paylogzt,
            'pageName' => '资金变动'
        );
        return [1,
            $params];
    }

    //对商户显示订单号进行处理
    private function dingdanchangenum($ddh) {
        $count = strlen($this->user['userid']);
        return substr($ddh, $count);
    }

    //订单条件处理
    private function dingdanwhere($request) {
        $map = array();
        $data = ' userid=' . $this->user['userid'] . ' ';

        if (is_numeric($request['status'])) {
            $map['status'] = $request['status'];
            if ($request['status'] == 0) {
                $data .=' AND status in (0,2) ';
            } else {
                $data .=' AND status = "1" ';
            }
        }

        //高级查询
        if ($request['ordernum']) {
            $map['ordernum'] = $request['ordernum'];
            $data.=' AND ordernum = "' . $this->user['userid'] . $request['ordernum'] . '" ';
        }
        if ($request['jkstyle']) {
            $map['jkstyle'] = $request['jkstyle'];
            $data.=' AND jkstyle = "' . $request['jkstyle'] . '" ';
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
        return [$map,
            $data];
    }

    //对账订单条件处理
    private function dingdancheckwhere($request) {
        $map = array();
        $map['time'] = $request['time'];

        $data = ' userid=' . $this->user['userid'] . ' AND status = "1" ';
        $time = $request['time'];
        $yesday = strtotime(date('Y-m-d')) - 24 * 3600;
        if (empty($time)) {
            $time = $yesday;
        }
        $data .= ' AND paytime between ' . ($time) . ' and ' . ($time + 24 * 3600 - 1) . ' ';
        return [$map,
            $data];
    }

}
