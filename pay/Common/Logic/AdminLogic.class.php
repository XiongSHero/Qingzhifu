<?php

// +----------------------------------------------------------------------
// | easy pay [ pay to easy ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2017 All rights reserved.
// +----------------------------------------------------------------------
// | Author: fengxing <QQ:51125330>
// +----------------------------------------------------------------------

namespace Common\Logic;

class AdminLogic extends BaseLogic {

    protected $moduleName = '管理员';

    /**
     * 列表
     */
    public function index($request) {
        $map = array();
        $data = ' 1=1 ';
        //高级查询
        if ($request['adminname']) {
            $request['adminname'] = $request['adminname'];
            $data.=' AND adminname like "%' . $request['adminname'] . '%" ';
        }
        if ($request['realname']) {
            $map['realname'] = $request['realname'];
            $data.=' AND realname like "%' . $request['realname'] . '%" ';
        }
        if ($request['adminid']) {
            $map['adminid'] = $request['adminid'];
            $data.=' AND adminid ="' . $request['adminid'] . '" ';
        }
        if ($request['phone']) {
            $map['phone'] = $request['phone'];
            $data.=' AND phone ="' . $request['phone'] . '" ';
        }
        if ($request['email']) {
            $map['email'] = $request['email'];
            $data.=' AND email ="' . $request['email'] . '" ';
        }
        if (is_numeric($request['status'])) {
            $map['status'] = $request['status'];
            $data.=' AND status = "' . $request['status'] . '" ';
        }
        $perpage = C('FX_PERPAGE'); //每页行数
        $admin = SM('Admin');
        $count = $admin->selectCount(
                $data, 'adminid'
        ); // 查询满足要求的总记录数
        // 进行分页数据查询 注意limit方法的参数要使用Page类的属性
        $page = page($count, $request['p'], $perpage) . ',' . $perpage;

        $list = $admin->pageData(
                '*', $data, 'adminid DESC', $page
        );
        foreach ($list as $i => $iList) {
            $list[$i]['addtime'] = stringChange('formatDateTime', $iList['addtime']);
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
        $params = array(
            'act' => 'add',
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
        $userModel = SM('Admin');
        $row = $userModel->findData('*', 'adminid=' . $request['id']);
        $params = array(
            'edit' => $row,
            'act' => 'edit',
            'pageName' => '修改' . $this->moduleName
        );
        return [1,
            $params,
            'Admin/add'];
    }

    /**
     * 保存
     */
    public function save($request) {
        $adminID = $request['adminid']; //获取数据标识
        $act = $request['act']; //获取模板标识
        //判断数据标识
        if (empty($adminID) && $act == 'edit') {
            return [0,
                '数据标识不能为空！'];
        }
        if (empty($act)) {
            return [0,
                '模板标识不能为空！'];
        }
        $admin = SM('Admin');
        $data = array();
        $data['realname'] = $request['realname'];
        $data['email'] = $request['email'];
        $data['phone'] = $request['phone'];
        $data['status'] = $request['status'];

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
            $data['savecode'] = $admin->saveCode();
        }
        //判断原密码
        if ($adminID == 1) {
            $data['status'] = 0;
            $passwordy = $request['passwordy'];
            if ($passwordy != "") {
                //为超级管理员 比较原密码是否正确
                $adminArray = $admin->findData(
                        'password', 'adminid =' . $adminID);
                if (md5('admin' . $passwordy) != $adminArray['password']) {
                    return [0,
                        '原密码不正确'];
                    exit;
                }
            }
        }
        if ($act == 'add') {
            //检查管理员名称长度
            if (!checkString('isEngLength', 4, 20, $request['adminname'])) {
                return [0,
                    '管理员名称必须为4-20位字母或数字！'];
            }
            $data['adminname'] = $request['adminname'];
            $data['addtime'] = time();
            $data['password'] = md5($request['adminname'] . $request['password']);
            //检查管理员名称重复
            $buffer = $admin->selectData(
                    'adminid', 'adminname="' . $data['adminname'] . '"');
            if ($buffer) {
                return [0,
                    '用户名重复请更换'];
            }
            if ($admin->insertData($data) === false) {
                return [0,
                    '添加失败'];
            } else {
                //写入日志
                $this->adminLog($this->moduleName, '添加管理员【' . $data['adminname'] . '】');
                return [1,
                    '添加成功！',
                    __URL__];
            }
        } elseif ($act == 'edit') {
            $data['adminid'] = $adminID;
            $buffer = $admin->selectData(
                    'adminid,adminname', 'adminid="' . $data['adminid'] . '"');
            if (!$buffer) {
                return [0,
                    '管理员不存在'];
            }
            if ($request['password'] != "" || $request['password2'] != "")
                $data['password'] = md5($buffer[0]['adminname'] . $request['password']);
            if ($admin->updateData(
                            $data, 'adminid=' . $data['adminid']) === false) {
                return [0,
                    '修改失败'];
            } else {
                //写入日志
                $this->adminLog($this->moduleName, '修改管理员AdminID为【' . $adminID . '】的数据');
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
        $adminID = $request['id']; //获取数据标识
        $idArray = explode(',', $adminID);
        if (in_array(1, $idArray)) {
            return [0,
                '删除失败，请不要删除编号为1的超级管理员'];
        }
        if (!$adminID) {
            return [0,
                '数据标识不能为空',
                __URL__];
        }
        if (SM('Admin')->deleteData(
                        'adminid in (' . implode(',', $idArray) . ')') === false) {
            return [0,
                '删除失败'];
        } else {
            //写入日志
            $this->adminLog($this->moduleName, '删除管理员AdminID为【' . implode(',', $idArray) . '】的数据');
            return [1,
                '删除成功',
                __URL__];
        }
    }

    /**
     * 登录状态验证
     */
    public function checklogin() {
        $adminID = $this->getCookieUserID();
        $oldCode = $this->getCookieCode();
        if (!$adminID || !$oldCode) {
            return [0,
                '未登录'];
        }
        //获取用户信息
        $buffer = SM('Admin')->selectData(
                '*', "adminid='" . $adminID . "'"
        );

        if (!$buffer) {
            return [0,
                '账号有误。'];
        }
        if ($buffer[0]['Status'] != 0) {
            return [0,
                '账号已被禁止，请联系管理员。'];
        }

        $time = C('FX_COOKIE_TIMEOUT');
        $code = md5($buffer[0]['adminid'] . $buffer[0]['adminname'] . $buffer[0]['savecode'] . ceil(time() / $time));
        $code1 = md5($buffer[0]['adminid'] . $buffer[0]['adminname'] . $buffer[0]['savecode'] . (ceil(time() / $time) - 1));
        if ($oldCode != $code && $oldCode != $code1) {
            $this->setCookieUserID(null, $time, 'MANAGE');
            $this->setCookieUserName(null, $time, 'MANAGE');
            $this->setCookieCode(null, $time, 'MANAGE');
            return [0,
                '账户已过期，请重新登录。'];
        }


        if ($oldCode == $code1) {
            $this->setCookieUserID($buffer[0]['adminid'], $time, 'MANAGE');
            $this->setCookieUserName($buffer[0]['adminname'], $time, 'MANAGE');
            $this->setCookieCode($code, $time, 'MANAGE');
        }
        unset($buffer[0]['password']);
        return [1,
            $buffer[0]];
    }

    /**
     * 登录事件验证
     */
    public function login($request) {
        $userName = $request['username'];
        $password = $request['password'];
        $code = $request['yzm'];
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
        $where['AdminName'] = $userName;
        $admin = SM('Admin');
        $data = $admin->selectData(
                $fields, $where
        );
        if (empty($data)) {
            return array(
                0,
                '账户密码有误。');
        }
        $password = trim($password);
        if ($data[0]['password'] !== md5($data[0]['adminname'] . $password)) {
            return array(
                0,
                '密码错误');
        }
        //判断用户状态
        if ($data[0]['status'] == 1) {
            return array(
                0,
                '账户被锁定，请联系管理员。');
        }
        if (empty($data[0]['lastip']))
            $data[0]['lastip'] = get_client_ip(0, true); //登录IP

        $time = C('FX_COOKIE_TIMEOUT');
        $data[0]['usercode'] = md5($data[0]['adminid'] . $data[0]['adminname'] . $data[0]['savecode'] . ceil(time() / $time));
        unset($data[0]['password']);
        $this->adminLog('管理员登录', '管理员【' . $data[0]['adminname'] . '】登录系统', $data[0]['adminname'], $data[0]['adminid']);

        $this->setCookieUserID($data[0]['adminid'], $time, 'MANAGE');
        $this->setCookieUserName($data[0]['adminname'], $time, 'MANAGE');
        $this->setCookieCode($data[0]['usercode'], $time, 'MANAGE');

        return array(
            1);
    }

    /**
     * 修改密码
     */
    public function pass($request) {
        if (IS_POST) {
            $adminID = $this->getCookieUserID();
            //判断新密码
            $password = $request['password'];
            $password2 = $request['password2'];

            if ($password2 != $password) {
                return [0,
                    '两次输入的密码不一致！'];
            }
            //密码规范
            if (!checkString('checkUserPassWord', $password)) {
                return [0,
                    '密码长度大于8，数字，字母组合！'];
            }
            $admin = SM('Admin');
            $adminArray = $admin->findData(
                    'adminname,password', 'adminid =' . $adminID);
            if (!$adminArray) {
                return [0,
                    '用户不存在。',
                    U('/Manage')];
            }
            $data = array();
            $data['savecode'] = $admin->saveCode();
            if (md5($adminArray['adminname'] . $request['passwordy']) != $adminArray['password']) {
                return [0,
                    '原密码不正确'];
            }

            $data['password'] = md5($adminArray['adminname'] . $password);
            if ($admin->updateData(
                            $data, 'adminid=' . $adminID) === false) {
                return [0,
                    '修改失败'];
            } else {
                //写入日志
                $this->adminLog($this->moduleName, '修改管理员密码AdminID为【' . $adminID . '】的数据');
                return [1,
                    '修改成功！',
                    __URL__];
            }
        }
        $username = $this->getCookieUserName();
        $params = array(
            'edit' => array(
                'adminname' => $username),
            'pageName' => $this->moduleName . '修改密码'
        );
        return [1,
            $params];
    }

}
