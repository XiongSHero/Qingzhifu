<?php

// +----------------------------------------------------------------------
// | easy pay [ pay to easy ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2017 All rights reserved.
// +----------------------------------------------------------------------
// | Author: fengxing <QQ:51125330>
// +----------------------------------------------------------------------

namespace Common\Logic;

class UserLogic extends BaseLogic {

    protected $moduleName = '用户';

    /**
     * 列表
     */
    public function index($request) {
        $map = array();
        $data = ' 1=1 ';
        //高级查询
        if ($request['username']) {
            $request['username'] = $request['username'];
            $data.=' AND username like "%' . $request['username'] . '%" ';
        }
        if ($request['userid']) {
            $map['userid'] = $request['userid'];
            $data.=' AND userid ="' . $request['userid'] . '" ';
        }
        if ($request['email']) {
            $map['email'] = $request['email'];
            $data.=' AND email = "' . $request['email'] . '" ';
        }
        if ($request['phone']) {
            $map['phone'] = $request['phone'];
            $data.=' AND phone ="' . $request['phone'] . '" ';
        }
        if (is_numeric($request['ifhttp'])) {
            $map['ifhttp'] = $request['ifhttp'];
            if (empty($request['ifhttp']))
                $data.=' AND httpid =0 ';
            else
                $data.=' AND httpid >0 ';
        }
        if ($request['agent']) {
            $map['agent'] = $request['agent'];
            $data.=' AND agent ="' . $request['agent'] . '" ';
        }
        if (is_numeric($request['ifagent'])) {
            $map['ifagent'] = $request['ifagent'];
            $data.=' AND ifagent = "' . $request['ifagent'] . '" ';
        }
        if (is_numeric($request['ifusercheck'])) {
            $map['ifusercheck'] = $request['ifusercheck'];
            $data.=' AND ifusercheck = "' . $request['ifusercheck'] . '" ';
        }
        if (is_numeric($request['status'])) {
            $map['status'] = $request['status'];
            $data.=' AND status = "' . $request['status'] . '" ';
        }
        if (is_numeric($request['ifkl'])) {
            $map['ifkl'] = $request['ifkl'];
            $data.=' AND ifkl = "' . $request['ifkl'] . '" ';
        }
        $perpage = C('FX_PERPAGE'); //每页行数
        $user = SM('User');
        $count = $user->selectCount(
                $data, 'id'
        ); // 查询满足要求的总记录数
        // 进行分页数据查询 注意limit方法的参数要使用Page类的属性
        $page = page($count, $request['p'], $perpage) . ',' . $perpage;

        //获取接口域名
        $httpBuffer = SM('Http')->selectData('*', '1=1', 'id asc');
        $httpBuffer = stringChange('arrayKey', $httpBuffer, 'id');

        global $publicData;
        $balanceStyle = $publicData['peizhi']['balancestyle'] . '+' . $publicData['peizhi']['balancetime'];

        $list = $user->pageData(
                '*', $data, 'id DESC', $page
        );

        $klBuffer = array(
            '-1' => '按照系统配置',
            '0' => '不扣量',
            '1' => '扣量'
        );
        foreach ($list as $i => $iList) {
            $list[$i]['addtime'] = stringChange('formatDateTime', $iList['addtime']);
            $list[$i]['ifagentname'] = $list[$i]['ifagent'] == 1 ? '是' : '否';
            $list[$i]['http'] = $httpBuffer[$list[$i]['httpid']]['http'];
            $list[$i]['agent'] = $list[$i]['agent'] == 0 ? '无' : $list[$i]['agent'];
            $list[$i]['ifklname'] = $klBuffer[$list[$i]['ifkl']];
            if (empty($list[$i]['balancestyle'])) {
                $list[$i]['balancestyle'] = $balanceStyle;
            } else {
                $list[$i]['balancestyle'] = $list[$i]['balancestyle'] . '+' . $list[$i]['balancetime'];
            }

            $list[$i] = stringChange('formatMoneyByArray', $list[$i], array(
                'money',
                'tx',
                'regmoney'));
        }
        $pageList = $this->pageList($count, $perpage, $map);

        $params = array(
            'list' => $list,
            'page' => $pageList,
            'pageName' => $this->moduleName . '管理'
        );
        return [1,
            $params];
    }

    /**
     * 添加
     */
    public function add($request) {
        $payother = SM('Jiekoupeizhi')->selectData('*', 'ifrepay=1', 'pzid desc');

        $params = array(
            'act' => 'add',
            'payother' => $payother,
            'pageName' => '添加' . $this->moduleName
        );
        return [1,
            $params];
    }

    /**
     * 修改
     */
    public function edit($request) {
        if (!$request['id']) {
            return [0,
                '参数错误！'];
        }
        $userModel = SM('User');
        $row = $userModel->findData('*', 'id=' . $request['id']);

        $payother = SM('Jiekoupeizhi')->selectData('*', 'ifrepay=1', 'pzid desc');
        $payotherKey = stringChange('arrayKey', $payother, 'pzid');
        if ($payotherKey[$row['daifuid']]['style']) {
            $paybank = SA(ucfirst($payotherKey[$row['daifuid']]['style']))->paybank;
        }

        $params = array(
            'edit' => $row,
            'act' => 'edit',
            'payother' => $payother,
            'paybank' => $paybank,
            'pageName' => '修改' . $this->moduleName
        );
        return [1,
            $params,
            'User/add'];
    }

    /**
     * 用户信息
     */
    public function info($request) {
        $usercheck = SM('Usercheck');
        $userid = $request['id'];
        $usercheckbuffer = $usercheck->findData('*', 'userid="' . $userid . '"');

        $statusarr = array(
            '待审核',
            '审核通过',
            '审核失败',
        );

        if ($usercheckbuffer) {
            $tmp = unserialize($usercheckbuffer['photos']);
            if ($tmp) {
                $usercheckbuffer = array_merge($usercheckbuffer, $tmp);
            }
            $usercheckbuffer['statusname'] = $statusarr[$usercheckbuffer['status']];
            $usercheckbuffer['checkstylename'] = $usercheckbuffer['checkstyle'] == 1 ? '企业' : '个人';
        } else {
            $usercheckbuffer['statusname'] = '待提交';
            $usercheckbuffer['checkstyle'] = 0;
        }
        $usercheckbuffer['noimg'] = '/Public/admin/img/noimg.png';

        if (IS_POST) {
            $data = array();
            $data['userid'] = $userid;
            $data['msg'] = $request['msg'];
            $data['status'] = $request['status'];
            $data['checktime'] = time();
            $result = $usercheck->updateData($data, 'userid="' . $data['userid'] . '"');
            if ($result === false) {
                return [0,
                    '提交信息失败，请重试.'];
            }
            //改变用户认证状态
            if ($data['status'] == 1) {
                $ifusercheck = 2;
            }
            if ($data['status'] == 2) {
                $ifusercheck = 3;
            }
            SM('User')->updateData(['ifusercheck' => $ifusercheck], 'userid="' . $userid . '"');

            return [1,
                '提交信息成功',
                U('Manage/User/index')];
        }

        $params = array(
            'edit' => $usercheckbuffer,
            'pageName' => '商户认证'
        );
        return [1,
            $params];
    }

    /**
     * 保存
     */
    public function save($request) {
        $id = $request['id']; //获取数据标识
        $act = $request['act']; //获取模板标识
        //判断数据标识
        if (empty($id) && $act == 'edit') {
            return [0,
                '数据标识不能为空！'];
        }
        if (empty($act)) {
            return [0,
                '模板标识不能为空！'];
        }
        $user = SM('User');
        $data = array();
        $data['qq'] = $request['qq'];
        $data['email'] = $request['email'];
        $data['phone'] = $request['phone'];
        $data['status'] = $request['status'];
        $data['ifagent'] = $request['ifagent'];
        $data['ifcheckphone'] = $request['ifcheckphone'];
        $data['regmoney'] = $request['regmoney'];
        $data['balancetime'] = $request['balancetime'];
        $data['balancestyle'] = $request['balancestyle'];
        $data['ifkl'] = $request['ifkl'];
        $data['ifagentopenuser'] = $request['ifagentopenuser'];
        $data['ifdaifuauto'] = $request['ifdaifuauto'];
        $data['daifuid'] = $request['daifuid'];
        $data['daifubank'] = $request['daifubank'];
        if (empty($data['regmoney']))
            $data['regmoney'] = 0;

        if (!empty($data['phone']) && !checkString('checkIfPhone', $data['phone']))
            return [0,
                '请输入正确的手机号！'];
        if (!empty($data['email']) && !checkString('checkIfEmail', $data['email']))
            return [0,
                '请输入正确的邮箱地址！'];


        //判断新密码
        $password = $request['password'];
        $password2 = $request['password2'];
        if ($password != "" || $password2 != "") {
            if ($password2 != $password) {
                return [0,
                    '两次输入的密码不一致！'];
            }
            //密码规范
            if (!checkString('checkUserPassWord', $password)) {
                return [0,
                    '密码长度大于8，数字，字母组合！'];
            }
            $data['password'] = md5($request['username'] . $request['password']);
        }

        if ($act == 'add') {
            if ($password == "") {
                return [0,
                    '请输入密码！'];
            }
            if ($request['txpassword'] == "") {
                return [0,
                    '请输入支付密码！'];
            }
            $data['username'] = $request['username'];
            $data['userid'] = $user->createUserID();
            $data['lastip'] = get_client_ip(0, true);
            $data['miyao'] = stringChange('randString', 32);
            $data['savecode'] = stringChange('randString', 6);
            $data['addtime'] = time();
            $data['txpassword'] = md5($data['username'] . $request['txpassword']);
            $data['agent'] = $request['agent'];

            //检查用户名称长度
            if (!checkString('isEngLength', 4, 20, $data['username'])) {
                return [0,
                    '用户名称必须为4-20位字母或数字！'];
            }
            //检查用户名称重复
            $buffer = $user->selectData(
                    'userid', 'username="' . $data['username'] . '"');
            if ($buffer) {
                return [0,
                    '用户名重复请更换'];
            }
            //判断代理id是否是代理
            if ($data['agent'] > 0) {
                $agentBuffer = $user->findData(
                        'userid,ifagent', 'userid="' . $data['agent'] . '"');
                if ($agentBuffer['ifagent'] != 1) {
                    return [0,
                        '您填的代理id对应商户不是代理，请更换。'];
                }
            }


            if (($id = $user->insertData($data)) === false) {
                return [0,
                    '添加失败'];
            } else {
                //写入用户扣量
                SL('Kou')->addUser($data['userid']);

                //写入日志
                $this->adminLog($this->moduleName, '添加用户【' . $data['username'] . '】');
                return [1,
                    '添加成功！转入设置费率。',
                    U('User/fl', array(
                        'id' => $id))];
            }
        } elseif ($act == 'edit') {
            $data['id'] = $id;
            $buffer = $user->findData(
                    'userid,username,password', 'id="' . $data['id'] . '"');
            if (!$buffer) {
                return [0,
                    '用户不存在'];
            }
            if (empty($data['agent']))
                $data['agent'] = $request['agent'];
            if ($password != "" || $password2 != "")
                $data['password'] = md5($buffer['username'] . $request['password']);
            if ($request['txpassword'])
                $data['txpassword'] = md5($buffer['username'] . $request['txpassword']);

            if ($user->updateData(
                            $data, 'id=' . $data['id']) === false) {
                return [0,
                    '修改失败'];
            } else {
                //写入日志
                $this->adminLog($this->moduleName, '修改用户UserID为【' . $buffer['userid'] . '】的数据');
                return [1,
                    '修改成功！',
                    __URL__];
            }
        }
    }

    /**
     * 删除
     */
    public function delete($request) {
        $id = $request['id']; //获取数据标识
        $idArray = explode(',', $id);
        if (!$id) {
            return [0,
                '数据标识不能为空',
                __URL__];
        }
        if (SM('User')->deleteData(
                        'id in (' . implode(',', $idArray) . ')') === false) {
            return [0,
                '删除失败'];
        } else {
            //删除费率
            SM('JiekouUser')->deleteData('userid in (' . implode(',', $idArray) . ')');
            SM('Zijian')->deleteData('userid in (' . implode(',', $idArray) . ')');
            //写入日志
            $this->adminLog($this->moduleName, '删除用户UserID为【' . implode(',', $idArray) . '】的数据');
            return [1,
                '删除成功',
                __URL__];
        }
    }

    /**
     * 登录状态验证
     */
    public function checklogin() {
        $userID = $this->getCookieUserID();
        $oldCode = $this->getCookieCode();
        if (!$userID || !$oldCode) {
            return [0,
                '未登录'];
        }
        //获取用户信息
        $buffer = SM('User')->selectData(
                '*', "userid='" . $userID . "'"
        );

        if (!$buffer) {
            return [0,
                '账号有误。'];
        }
        if ($buffer[0]['Status'] != 0) {
            return [0,
                '账号已被禁止，请联系客服。'];
        }

        $time = C('FX_COOKIE_TIMEOUT');
        $code = md5($buffer[0]['userid'] . $buffer[0]['username'] . $buffer[0]['savecode'] . ceil(time() / $time));
        $code1 = md5($buffer[0]['userid'] . $buffer[0]['username'] . $buffer[0]['savecode'] . (ceil(time() / $time) - 1));
        if ($oldCode != $code && $oldCode != $code1) {
            $this->setCookieUserID(null, $time);
            $this->setCookieUserName(null, $time);
            $this->setCookieCode(null, $time);
            return [0,
                '账户已过期，请重新登录。'];
        }


        if ($oldCode == $code1) {
            $this->setCookieUserID($buffer[0]['userid'], $time);
            $this->setCookieUserName($buffer[0]['username'], $time);
            $this->setCookieCode($code, $time);
        }
        unset($buffer[0]['password']);
        $buffer[0] = stringChange('formatMoneyByArray', $buffer[0], 'money,tx,dffl,regmoney');
        return [1,
            $buffer[0]];
    }

    /**
     * 注册事件
     */
    public function reg($request) {
        $agent = $request['agent'];
        if (empty($agent) || !is_numeric($agent)) {
            $agent = 0;
            $_REQUEST['agent'] = 0;
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
            global $publicData;
            $peizhi = $publicData['peizhi'];

            //检查管理员名称长度
//            if (!checkString('isEngLength', 4, 20, $userName)) {
//                return [0,
//                    '用户名称必须为4-20位字母或数字！'];
//            }
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

            if ($peizhi['ifsms'] == 1) {
                //判断短信密码
                $code = $yzm;
                $result = SL('Logsms')->checksms(array(
                    'code' => $code,
                    'phone' => $phone));
                if ($result[0] != 1) {
                    return $result;
                }
                $ifcheckphone = 1;
            } else {
                //验证码
                if (md5($yzm) != session('verify')) {
                    return [0,
                        '验证码错误！'];
                }
            }

            //判断agent是否存在
            if ($agent) {
                $buffer = $user->selectData(
                        'userid', 'userid="' . $agent . '"');
                if (!$buffer)
                    $agent = 0;
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
            $data['lastip'] = get_client_ip(0, true);
            $data['addtime'] = time();
            $data['ifagent'] = $ifagent;
            $data['agent'] = $agent;
            $data['status'] = 0;
            $data['ifcheckphone'] = $ifcheckphone;

            if ($peizhi['ifregcheck'] == 1)
                $data['status'] = 1;
            $data['userid'] = $user->createUserID();
            if ($user->insertData($data) === false) {
                return [0,
                    '添加失败'];
            } else {
                //写入用户扣量
                SL('Kou')->addUser($data['userid']);

                //写入日志
                $this->userLog($this->moduleName, '用户注册【' . $data['userid'] . '】', $data['userid']);
                return [1,
                    '注册成功！',
                    U("/")];
            }
        }
    }

    /**
     * 找回密码
     */
    public function pass($request) {
        if (IS_POST) {
            $code = $request['code'];
            $checkstyle = $request['checkstyle'];
            $username = $request['phone'];
            $txmm = $request['txmm'];
            $password = $request['pass'];
            $password1 = $request['pass1'];

            if (!checkString('checkUserPassWord', $password)) {
                return [0,
                    '密码长度大于8，数字，字母组合！'];
            }
            if ($password1 != $password) {
                return [0,
                    '两次输入的密码不一致！'];
            }

            $userBuffer = SM('User')->findData('*', 'username="' . $username . '"');
            if (!$userBuffer) {
                return [0,
                    '您输入的信息有误！'];
            }

            //获取配置信息
            global $publicData;
            $peizhi = $publicData['peizhi'];

            switch ($checkstyle) {
                case 'txmms':
                    if ($userBuffer['txpassword'] != md5($userBuffer['username'] . $txmm)) {
                        return [0,
                            '支付密码有误！'];
                    }
                    //验证码
                    if (md5($code) != session('verify')) {
                        return [0,
                            '验证码错误！'];
                    }
                    break;
                case 'phones':
                    if ($peizhi['ifsms'] != 1) {
                        return [0,
                            '手机验证方式未开启！'];
                    }

                    if ($userBuffer['ifcheckphone'] != 1) {
                        return [0,
                            '该账户未开启手机认证，请联系管理员！'];
                    }

                    //判断短信密码
                    $result = SL('Logsms')->checksms(array(
                        'code' => $code,
                        'phone' => $userBuffer['phone']));
                    if ($result[0] != 1) {
                        return $result;
                    }

                    break;
            }

            $data = array(
                'password' => md5($userBuffer['username'] . $password)
            );
            $result = SM('User')->updateData($data, 'id=' . $userBuffer['id']);
            if ($result === false)
                return [0,
                    '更新数据失败！请重试.'];
            return [1,
                '重置密码成功。',
                U("/")];
        }
    }

    /**
     * 登录事件验证
     */
    public function login($request) {
        if (IS_POST) {
            $userName = $request['username'];
            $password = $request['password'];
            $code = $request['code'];
            if (empty($userName)) {
                return array(
                    0,
                    '账户不能为空');
            }
            if (empty($password)) {
                return array(
                    0,
                    '密码不能为空');
            }
            //验证码
            if (md5($code) != session('verify')) {
                return [0,
                    '验证码错误！'];
            }

            $fields = '*';
            $userName = preg_replace('/\s+/', '', $userName);
            $where = array();
            $where['username'] = $userName;
            $user = SM('User');
            $data = $user->findData(
                    $fields, $where
            );
            if (empty($data)) {
                return array(
                    0,
                    '账户密码有误。');
            }
            $password = trim($password);
            if ($data['password'] !== md5($data['username'] . $password)) {
                return array(
                    0,
                    '密码错误');
            }
            //判断用户状态
            if ($data['status'] == 1) {
                return array(
                    0,
                    '账户被锁定，请联系用户。');
            }

            //修改用户最后一次登录ip
            $newdata = array(
                'lastip' => get_client_ip(0, true),
                'logintimes' => $data['logintimes'] + 1
            );
            $user->updateData($newdata, 'id=' . $data['id']);

            //写入cookie
            $time = C('FX_COOKIE_TIMEOUT');
            $data['usercode'] = md5($data['userid'] . $data['username'] . $data['savecode'] . ceil(time() / $time));
            unset($data['password']);
            $this->userLog('用户登录', '用户【' . $data['username'] . '】登录系统', $data['userid']);
            $this->setCookieUserID($data['userid'], $time);
            $this->setCookieUserName($data['username'], $time);
            $this->setCookieCode($data['usercode'], $time);
            return array(
                1,
                '登录成功',
                U('Index/Home/index'));
        }
    }

    /**
     * 费率设置
     */
    public function fl($request) {
        $id = $request['id'];
        if (empty($id) || !is_numeric($id)) {
            return array(
                0,
                '数据标识错误。');
        }
        $user = SM('User');
        $userBuffer = $user->findData('*', 'id=' . $id);
        if (!$userBuffer) {
            return array(
                0,
                '数据不存在。');
        }
        $jiekouUser = SM('JiekouUser');
        $row = $jiekouUser->selectData('*', 'userid=' . $userBuffer['userid']);
        $row = stringChange('arrayKey', $row, 'jkid');

        if (IS_POST) {
            $jkid = $request['jkid'];
            $dffl = $request['dffl'];
            $iffl = $request['iffl'];
            $httpid = $request['httpid'];
            $ifopenuserhttp = $request['ifopenuserhttp'];
            $ifdlmoney = $request['ifdlmoney'];
            SM('User')->updateData(['dffl' => $dffl,
                'iffl' => $iffl,
                'ifdlmoney' => $ifdlmoney,
                'ifopenuserhttp' => $ifopenuserhttp,
                'httpid' => $httpid], ['userid' => $userBuffer['userid']]);
            $zjBuffer = array();
            foreach ($jkid as $iJkid) {
                if (empty($request['flselect_' . $iJkid]))
                    $fl = 0;
                else
                    $fl = $request['fl_' . $iJkid];
                if (empty($fl))
                    $fl = 0;

                $zjBuffer[] = array(
                    'jkid' => $iJkid,
                    'userid' => $userBuffer['userid'],
                    'fl' => $fl,
                    'pzid' => $request['pzid_' . $iJkid],
                    'ifopen' => $request['ifopen_' . $iJkid]
                );
            }

            foreach ($zjBuffer as $i => $iZjBuffer) {
                if ($row[$iZjBuffer['jkid']]) { //修改数据
                    $jiekouUser->updateData($iZjBuffer, 'id=' . $row[$iZjBuffer['jkid']]['id']);
                } else {
                    //写入数据
                    $jiekouUser->insertData($iZjBuffer);
                }
            }
            //写入日志
            $this->adminLog($this->moduleName, '管理员修改用户费率【' . $userBuffer['userid'] . '】');
            return array(
                1,
                '保存成功',
                U('User/index'));
        }

        $pzBuffer = SM('Jiekoupeizhi')->selectData('*', '1=1', 'pzid asc');
        $pzBuffer = stringChange('arrayKey', $pzBuffer, 'pzid');
        $jkzjBuffer = SM('Jiekouzj')->selectData('*', '1=1', 'zjid asc');
        $tmpArray = array();
        foreach ($jkzjBuffer as $i => $iJkzjBuffer) {
            $tmpArray[$iJkzjBuffer['jkid']][] = $pzBuffer[$iJkzjBuffer['pzid']];
        }
        $jkzjBuffer = $tmpArray;


        $jiekouBuffer = SM('Jiekou')->selectData('*', '1=1', 'jkid asc');
        foreach ($jiekouBuffer as $i => $iJiekouBuffer) {
            $row[$iJiekouBuffer['jkid']] = stringChange('formatMoneyByArray', $row[$iJiekouBuffer['jkid']], array(
                'fl'));
            $jiekouBuffer[$i]['fl'] = $row[$iJiekouBuffer['jkid']]['fl'];
            $jiekouBuffer[$i]['pzid'] = $row[$iJiekouBuffer['jkid']]['pzid'];
            if ($row[$iJiekouBuffer['jkid']]['ifopen'] == null) {
                $jiekouBuffer[$i]['ifopen'] = $jiekouBuffer[$i]['ifuseropen'];
            } else {
                $jiekouBuffer[$i]['ifopen'] = $row[$iJiekouBuffer['jkid']]['ifopen'];
            }
            $jiekouBuffer[$i]['pzbuffer'] = $jkzjBuffer[$iJiekouBuffer['jkid']];
        }

        $userBuffer = stringChange('formatMoneyByArray', $userBuffer, array(
            'dffl'));

        //获取接口域名
        $httpBuffer = SM('Http')->selectData('*', '1=1', 'id asc');

        $params = array(
            'edit' => $userBuffer,
            'act' => 'edit',
            'list' => $jiekouBuffer,
            'http' => $httpBuffer,
            'pageName' => $this->moduleName . '费率管理'
        );
        return [1,
            $params];
    }

    /**
     * 接口数据，获取用户余额
     */
    public function getUserMoney($request) {
        $fxid = $request['fxid'];
        $fxdate = $request['fxdate'];
        $fxaction = $request['fxaction'];
        $fxsign = $request['fxsign'];
        //判断商户号 key是否存在
        $userBuffer = SM('User')->findData('money,miyao,status', 'userid=' . $fxid);
        if (!$userBuffer || $userBuffer['status'] == 1) {
            return [0,
                '商户号错误。'];
        }
        $fxkey = $userBuffer['miyao'];

        if ($fxsign != md5($fxid . $fxdate . $fxaction . $fxkey)) {
            return [0,
                '秘钥错误。'];
        }

        $result = SL('Dingdan')->getFrozenMoney($fxid);
        $todaymoney = 0; //冻结金额
        if ($result[0] == 1)
            $todaymoney = $result[1];
        $userMoney = $userBuffer['money'] - (int) $todaymoney; //可用金额

        return [1,
            $userMoney];
    }

    /**
     * 用户单笔支付费率
     * @params int $iffl 费率类型
     * @params float $dffl 费率值
     * @return array [$iffl,$dffl]
     */
    public function getdffl($iffl, $dffl) {
        global $publicData;
        $df = array();
        $df[0] = $iffl;
        $df[1] = $dffl;

        if ($dffl < 0) {
            $df[0] = $publicData['peizhi']['iffl'];
            $df[1] = $publicData['peizhi']['dffl'];
        }
        $df[1] = stringChange('formatMoney', $df[1]);
        return $df;
    }

    /**
     * 费率计算
     * @params float $money 金额
     * @params array [$iffl,$dffl] 费率类型，费率值
     * @return float
     */
    public function calcdffl($money, $dffl) {
        if ($dffl[0] == 1) {
            $return = round($money * ($dffl[1] / 100), 2);
        } else {
            $return = $dffl[1];
        }
        return stringChange('formatMoney', $return);
    }

    /**
     * 发送短信
     */
    public function sendsms($request) {
        $phone = $request['phone'];
        $code = $request['code'];
        $style = $request['style'];
        //验证码
        if (md5($code) != session('verify')) {
            return [0,
                '验证码错误！'];
        }

        if ($style == 1) { //找回密码
            $userBuffer = SM('User')->findData('*', 'username="' . $phone . '" or phone="' . $phone . '"');
            if (!$userBuffer) {
                return [0,
                    '您输入的手机号或用户名不存在'];
            }
            if ($userBuffer['ifcheckphone'] != 1) {
                return [0,
                    '您的手机号未验证，暂不支持手机短信找回密码。'];
            }
            $phone = $userBuffer['phone'];
        }
        if ($style == 2) { //用户登录后验证
            $userBuffer = SM('User')->findData('*', 'username="' . $phone . '"');
            if (!$userBuffer) {
                return [0,
                    '用户名不存在'];
            }
            if ($userBuffer['ifcheckphone'] != 1) {
                return [0,
                    '您的手机号未验证，请先在商户后台我的资料->绑定手机号。'];
            }
            $phone = $userBuffer['phone'];
        }

        if (!empty($phone) && !checkString('checkIfPhone', $phone))
            return [0,
                '请输入正确的手机号！'];

        $code = rand(100000, 999999);
        $data = array(
            'phone' => $phone,
            'code' => $code,
            'userid' => empty($userBuffer['userid']) ? 0 : $userBuffer['userid']
        );
        $result = SL('Logsms')->sendsms($data);
        return $result;
    }

    //管理员结算用户余额
    public function sendmoney($request) {
        $id = $request['id'];
        if (empty($id) || !is_numeric($id)) {
            return array(
                0,
                '数据标识错误。');
        }
        $user = SM('User');
        $userBuffer = $user->findData('*', 'id=' . $id);
        if (!$userBuffer) {
            return array(
                0,
                '数据不存在。');
        }
        //可申请金额，减去冻结金额
        global $publicData;
        $balancestyle = $publicData['peizhi']['balancestyle'] . '+' . $publicData['peizhi']['balancetime'];
        if (!empty($userBuffer['balancestyle'])) {
            $balancestyle = $userBuffer['balancestyle'] . '+' . $userBuffer['balancetime'];
        }

        $result = SL('Dingdan')->getFrozenMoney($userBuffer['userid'], $userBuffer['ifagent'], $balancestyle);
        $todaymoney = 0;
        if ($result[0] == 1)
            $todaymoney = $result[1];
        if (!is_numeric($todaymoney) || $todaymoney <= 0)
            $todaymoney = 0;
        $nowmoney = $userBuffer['money'] - $todaymoney;
        $dffl = SL('User')->getdffl($userBuffer['iffl'], $userBuffer['dffl']);

        if (IS_POST) {
            $act = $request['act']; //获取模板标识
            //判断数据标识
            if (empty($act)) {
                return [0,
                    '模板标识不能为空！'];
            }

            //获取银行卡
            $yhk = $request['yhk'];
            if (empty($yhk)) {
                return [0,
                    '请选择银行卡！'];
            }
            $kaBuffer = SM('Ka')->findData('*', 'ifcheck=1 and userid=' . $userBuffer['userid'] . ' and id=' . $yhk);
            if (!$kaBuffer) {
                return [0,
                    '银行卡无法支付，请更换！'];
            }

            $sxf = SL('User')->calcdffl($request['money'], $dffl);
            $ddh = 'qzf' . time() . getDingdanRand();
            $data = array();
            $data['userid'] = $userBuffer['userid'];
            $data['money'] = $request['money'];
            $data['status'] = $request['status'];
            $data['realname'] = $kaBuffer['username'];
            $data['ka'] = $kaBuffer['ka'];
            $data['address'] = $kaBuffer['address'];
            $data['zhihang'] = $kaBuffer['zhihang'];
            $data['sheng'] = $kaBuffer['sheng'];
            $data['shi'] = $kaBuffer['shi'];
            $data['ddh'] = $ddh;
            $data['dffl'] = $sxf;

            $needmoney = $data['money'] + $sxf;
            //判断支付金额
            if ($needmoney > $userBuffer['money']) {
                return [0,
                    '支付金额不足,当前需要' . $needmoney . '元,手续费：' . $sxf . '元！'];
            }

            if ($data['money'] < $publicData['peizhi']['minpay']) {
                return [0,
                    '支付金额小于最小要求金额！最低支付' . $publicData['peizhi']['minpay'] . '元'];
            }

            $zijian = SM('Pay');
            if ($act == 'add') {
                $data['addtime'] = time();
                $zijian->dbStartTrans(); //开始事务

                $userBuffer = SM('User')->findData('*', 'userid="' . $userBuffer['userid'] . '"');
                $flag = false;
                if ($zijian->insertData($data) === false)
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
                    'userid' => $userBuffer['userid'],
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
                }

                //写入日志
                $this->userLog('申请支付', '管理员发起支付商户id【' . $data['userid'] . '】【' . $request['money'] . '元】手续费：' . $sxf . '元');
                return [1,
                    '申请成功！',
                    U('User/index')];
            } elseif ($act == 'edit') {

            }
        }

        $kaBuffer = SM('Ka')->selectData('*', 'userid=' . $userBuffer['userid'] . ' and ifcheck=1');

        $params = array(
            'act' => 'add',
            'nowmoney' => stringChange('formatMoney', $nowmoney),
            'ka' => $kaBuffer,
            'dffl' => $dffl,
            'todaymoney' => $todaymoney,
            'edit' => $userBuffer,
            'balancestyle' => $balancestyle,
            'pageName' => '余额结算'
        );
        return [1,
            $params];
    }

}
