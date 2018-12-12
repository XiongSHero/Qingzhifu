<?php

// +----------------------------------------------------------------------
// | easy pay [ pay to easy ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2017 All rights reserved.
// +----------------------------------------------------------------------
// | Author: fengxing <QQ:51125330>
// +----------------------------------------------------------------------

namespace Common\Logic;

class AgentLogic extends BaseLogic {

    protected $moduleName = '代理等级';
    public $zt = array(
        0 => '未申请',
        1 => '申请',
        2 => '通过',
        3 => '冻结',
    );

    /**
     * 列表
     */
    public function index($request) {
        $map = array();
        $data = ' 1=1 ';

        $agent = SM('Daili');

        $list = $agent->selectData(
                '*', $data, 'dlid ASC'
        );

        foreach ($list as $i => $iList) {
            $list[$i] = stringChange('formatMoneyByArray', $list[$i], array(
                'money',
                'fl'));
        }

        $params = array(
            'list' => $list,
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
        $agent = SM('Daili');
        $row = $agent->findData('*', 'dlid=' . $request['id']);
        $row = stringChange('formatMoneyByArray', $row, array(
            'money',
            'fl'));
        $params = array(
            'edit' => $row,
            'act' => 'edit',
            'pageName' => '修改' . $this->moduleName
        );
        return [1,
            $params,
            'Agent/add'];
    }

    /**
     * 保存
     */
    public function save($request) {
        $agentID = $request['dlid']; //获取数据标识
        $act = $request['act']; //获取模板标识
        //判断数据标识
        if (empty($agentID) && $act == 'edit') {
            return [0,
                '数据标识不能为空！'];
        }
        if (empty($act)) {
            return [0,
                '模板标识不能为空！'];
        }
        $agent = SM('Daili');
        $data = array();
        $data['dlname'] = $request['dlname'];
        $data['money'] = $request['money'];
        $data['fl'] = $request['fl'];

        if ($act == 'add') {
            //检查名称重复
            $buffer = $agent->selectData(
                    'dlid', 'dlname="' . $data['dlname'] . '"');
            if ($buffer) {
                return [0,
                    '名称重复请更换'];
            }
            if ($agent->insertData($data) === false) {
                return [0,
                    '添加失败'];
            } else {
                //写入日志
                $this->adminLog($this->moduleName, '添加代理等级【' . $data['dlname'] . '】');
                return [1,
                    '添加成功！',
                    __URL__];
            }
        } elseif ($act == 'edit') {
            $data['dlid'] = $agentID;
            $buffer = $agent->selectData(
                    'dlid,dlname', 'dlid="' . $data['dlid'] . '"');
            if (!$buffer) {
                return [0,
                    '代理等级不存在'];
            }
            $buffer = $agent->selectData(
                    'dlid,dlname', 'dlname="' . $data['dlname'] . '" && dlid!="' . $data['dlid'] . '"');
            if ($buffer) {
                return [0,
                    '代理等级名称重复'];
            }
            if ($agent->updateData(
                            $data, 'dlid=' . $data['dlid']) === false) {
                return [0,
                    '修改失败'];
            } else {
                //写入日志
                $this->adminLog($this->moduleName, '修改代理等级dlid为【' . $agentID . '】的数据');
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
        $agentID = $request['id']; //获取数据标识
        $idArray = explode(',', $agentID);
        if (!$agentID) {
            return [0,
                '数据标识不能为空',
                __URL__];
        }
        if (SM('Daili')->deleteData(
                        'dlid in (' . implode(',', $idArray) . ')') === false) {
            return [0,
                '删除失败'];
        } else {
            //写入日志
            $this->adminLog($this->moduleName, '删除代理等级dlID为【' . implode(',', $idArray) . '】的数据');
            return [1,
                '删除成功',
                __URL__];
        }
    }

    /**
     * 代理更新返点数据
     */
    public function fandianupdate() {
        //订单日期 获取最早的
        $orderBuffer = SM('Dingdan')->findData('*', 'status=1', 'ddid asc');
        $lastfd = strtotime(date('Y-m-d', $orderBuffer['paytime']));
        $today = strtotime(date('Y-m-d', time()));
        if (!$orderBuffer || $orderBuffer['paytime'] > $today) {
            return;
        }

        //返点最后一天日期
        $fdBuffer = SM('Fandian')->findData('*', '1=1', 'id DESC');
        if ($fdBuffer) {
            $lastfd = strtotime(date('Y-m-d', $fdBuffer['addtime'] + 24 * 3600));
            if ($lastfd >= $today) {
                return;
            }
        }

        //计算更新几天的返点
        $count = ($today - $lastfd) / (24 * 3600);

        //获取代理等级
        $daili = SM('Daili');
        $dlbuffer = $daili->selectData('*', '1=1', 'money asc');
        if (empty($dlbuffer))
            return;

        //循环更新返点
        $dingdan = SM('Dingdan');
        $addFandian = array();
        for ($i = 0; $i < $count; $i++) {
            $buffer = $dingdan->getAgentMoney($lastfd, $lastfd + 24 * 3600);
            if ($buffer) {
                foreach ($buffer as $iBuffer) {
                    //获取费率
                    $fl = 0;
                    foreach ($dlbuffer as $jDlbuffer) {
                        if ($iBuffer['money'] >= $jDlbuffer['money'])
                            $fl = $jDlbuffer['fl'];
                    }

                    $addFandian[] = array(
                        'addtime' => $lastfd,
                        'totalmoney' => $iBuffer['money'],
                        'fl' => $fl,
                        'havemoney' => round($iBuffer['money'] * $fl / 100, 2),
                        'status' => 1,
                        'userid' => $iBuffer['userid']
                    );
                }
            }
            $lastfd+=24 * 3600;
        }
        if ($addFandian)
            SM('Fandian')->addAllData($addFandian);
    }

    /**
     * 代理账单
     */
    public function fandian($request) {
        $this->fandianupdate(); //更新返点

        $map = array();
        $data = ' 1=1 ';
        //高级查询
        if ($request['userid']) {
            $map['userid'] = $request['userid'];
            $data .= ' AND userid = "' . $request['userid'] . '" ';
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
            $list[$i]['statusname'] = $this->zt[$iList['status']];
            $list[$i]['addtime'] = stringChange('formatDateTime', $iList['addtime']);
            $list[$i] = stringChange('formatMoneyByArray', $list[$i], array(
                'totalmoney',
                'havemoney',
                'fl'));
        }

        //当日代理金，当日支出，总代理金，总支出
        $times = strtotime(date('Y-m-d', time() - 3600 * 24));
        $tj = array();
        $tj['today'] = $fandian->sumData('havemoney', 'addtime=' . $times);
        $tj['paytoday'] = $fandian->sumData('havemoney', 'status=2 and addtime=' . $times);
        $tj['all'] = $fandian->sumData('havemoney', '1=1');
        $tj['payall'] = $fandian->sumData('havemoney', 'status=2');
        foreach ($tj as $i => $iTj) {
            if (empty($iTj))
                $tj[$i] = 0;
        }
        $tj=  stringChange('formatMoneyByArray',$tj,array('today','paytoday','all','payall'));

        $params = array(
            'zt' => $this->zt,
            'tj' => $tj,
            'list' => $list,
            'pageName' => '代理返点管理'
        );
        return [1,
            $params];
    }

    /**
     * 状态
     */
    public function status($request) {
        $id = $request['id']; //获取数据标识
        $status = $request['status']; //获取数据标识

        if (empty($id) || !is_numeric($status)) {
            return [0,
                '数据标识不能为空！'];
        }

        if (!is_array($id)) {
            $id = explode(',', $id);
        }

        $pay = SM('Fandian');
        $pay->dbStartTrans(); //开启事务
        $flag = true; //事务标志
        //状态为2的数据不能修改状态
        if ($status == 2) {
            $buffer = $pay->selectData('*', 'id in (' . implode(',', $id) . ') && status!=2', 'userid asc');
            $userAddBuffer = array(); //需要添加的数据
            $userIDArray = array(); //商户id的数据
            foreach ($buffer as $iBuffer) {
                if (empty($userAddBuffer[$iBuffer['userid']]))
                    $userAddBuffer[$iBuffer['userid']] = 0;
                $userAddBuffer[$iBuffer['userid']]+=$iBuffer['havemoney'];
                $userIDArray[] = $iBuffer['userid'];
            }
        }

        if ($pay->updateData(array(
                    'status' => $status), 'id in (' . implode(',', $id) . ') && status!=2') === false) {
            $pay->dbRollback();
            return [0,
                '修改失败！'];
        } else {

            if ($status == 2 && $userIDArray) {
                //给审核通过的用户增加金额
                $user = SM('User');
                foreach ($userAddBuffer as $i => $iUserAddBuffer) {
                    $result = $user->conAddData('money=money+' . $iUserAddBuffer, 'userid=' . $i, 'money');
                    if ($result === false)
                        $flag = false;
                    //添加资金变动
                    $userBuffer = $user->findData('*', 'userid=' . $i);
                    $data = array(
                        'userid' => $i,
                        'leavemoney' => $userBuffer['money'],
                        'changemoney' => $iUserAddBuffer,
                        'desc' => '代理佣金',
                        'style' => 3,
                        'ddh' => 'dlfd'.$i.time(),
                    );
                    $result = SL('Pay')->moneylogadd($data);
                    if ($result === false)
                        $flag = false;
                }
            }
            if ($flag === false) {
                $pay->dbRollback();
                return [0,
                    '修改失败！'];
            } else {
                $pay->dbCommit();
            }

            $this->adminLog($this->moduleName, '修改账单状态id为【' . implode(',', $id) . '】的数据');
            return [1,
                '修改成功！'];
        }
    }

}
