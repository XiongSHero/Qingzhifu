<?php

// +----------------------------------------------------------------------
// | easy pay [ pay to easy ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2017 All rights reserved.
// +----------------------------------------------------------------------
// | Author: fengxing <QQ:51125330>
// +----------------------------------------------------------------------

namespace Common\Logic;

class DingdanLogic extends BaseLogic {

    protected $moduleName = '订单';
    public $tzzt = array(
        0 => '未通知',
        1 => '通知失败',
        2 => '通知成功');
    public $zt = array(
        0 => '未支付',
        1 => '已支付',
        2 => '扣量');
    public $notifyzt = array(
        0 => '待补单',
        1 => '已补单',
        2 => '补单异常',
        3 => '不再补单'
    );
    public $fj = array(
        'fxattch' => '',
        'fxbackurl' => '',
        'fxnotifyurl' => '',
        'fxgoodname' => '');
    public $ddstyle = array(
        0 => '普通订单',
        1 => '充值订单',
        2 => '保证金',
        3 => '测试订单',
        4 => '商户二维码');

    /**
     * 列表
     */
    public function index($request) {
        $map = array();
        $data = ' 1=1 ';
        //高级查询
        if ($request['userid']) {
            $request['userid'] = $request['userid'];
            $data.=' AND userid = "' . $request['userid'] . '" ';
        }
        if ($request['ordernum']) {
            $map['ordernum'] = $request['ordernum'];
            $data.=' AND ordernum = "' . $request['ordernum'] . '" ';
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
            $data .= ' AND paytime between ' . ($start) . ' and ' . ($end) . ' ';
        }
        if (!is_numeric($request['status'])) {
            $request['status'] = 1;
            $_REQUEST['status'] = 1;
        }
        $map['status'] = $request['status'];
        $data.=' AND status = "' . $request['status'] . '" ';

        $titlename = $this->zt[$request['status']];

        $jiekou = SM('Jiekou')->selectData('*', '1=1', 'jkid asc');

        $jiekoubuffer = stringChange('arrayKey', $jiekou, 'jkstyle');

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

        $pzArray = array();
        foreach ($list as $i => $iList) {
            $pzArray[] = $iList['pzid'];
            $list[$i]['addtime'] = stringChange('formatDateTime', $iList['addtime']);
            $list[$i]['paytime'] = stringChange('formatDateTime', $iList['paytime']);
            if ($list[$i]['preordernum'] == '')
                $list[$i]['preordernum'] = '-';
            $list[$i]['tzzt'] = $this->tzzt[$list[$i]['tz']];
            $list[$i]['statusname'] = $this->zt[$list[$i]['status']];
            $list[$i]['ddstylename'] = $this->ddstyle[$list[$i]['ddstyle']];
            $list[$i]['jkstyle'] = $jiekoubuffer[$list[$i]['jkstyle']]['jkname'];
            $list[$i] = stringChange('formatMoneyByArray', $list[$i], array(
                'totalmoney',
                'havemoney',
                'dailimoney',
                'syflmoney'));
        }

        $pzArray = array_filter($pzArray);
        if (!empty($pzArray)) {
            $pzBuffer = SM('Jiekoupeizhi')->selectData('*', 'pzid in (' . implode(',', $pzArray) . ')');
            $pzBuffer = stringChange('arrayKey', $pzBuffer, 'pzid');
        }

        $pageList = $this->pageList($count, $perpage, $map);

        //统计数据 今日订单笔数     昨日订单笔数     今日订单总金额     今日支出金额     昨日订单总金额     昨日支出金额     历史总笔数     历史总金额     历史总支出
        $times = strtotime(date('Y-m-d', time()));
        $tj = array();
        $tj['today'] = $order->sumData('totalmoney', 'status>0 and addtime>=' . $times);
        $tj['paytoday'] = $order->sumData('havemoney', 'status=1 and addtime>=' . $times);
        $tj['paytoday'] += $order->sumData('dailimoney', 'status=1 and addtime>=' . $times);
        $tj['all'] = $order->sumData('totalmoney', $data);
        $tj['payall'] = $order->sumData('havemoney', $data);
        $tj['payall'] += $order->sumData('dailimoney', $data);
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
            'list' => $list,
            'tj' => $tj,
            'page' => $pageList,
            'jiekou' => $jiekou,
            'pzBuffer' => $pzBuffer,
            'pageName' => $titlename . $this->moduleName . '管理'
        );
        return [1,
            $params];
    }

    /**
     * 对账列表
     */
    public function dingdancheck($request) {
        $map = array();
        $data = ' status>0 ';
        //高级查询
        if ($request['pzid']) {
            $map['pzid'] = $request['pzid'];
            $data.=' AND pzid = "' . $request['pzid'] . '" ';
        }

        $map['time'] = $request['time'];
        $time = $request['time'];
        $yesday = strtotime(date('Y-m-d')) - 24 * 3600;
        if (empty($time)) {
            $time = $yesday;
        }
        $data .= ' AND paytime between ' . ($time) . ' and ' . ($time + 24 * 3600 - 1) . ' ';

        $jiekou = SM('Jiekou')->selectData('*', '1=1', 'jkid asc');
        $jiekouBuffer = stringChange('arrayKey', $jiekou, 'jkstyle');

        $pzBufferList = SM('Jiekoupeizhi')->selectData('*', '1=1', 'pzid asc');
        $pzBuffer = stringChange('arrayKey', $pzBufferList, 'pzid');

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

        foreach ($list as $i => $iList) {
            $list[$i]['addtime'] = stringChange('formatDateTime', $iList['addtime']);
            $list[$i]['paytime'] = stringChange('formatDateTime', $iList['paytime']);
            if ($list[$i]['preordernum'] == '')
                $list[$i]['preordernum'] = '-';
            $list[$i]['tzzt'] = $this->tzzt[$list[$i]['tz']];
            $list[$i]['statusname'] = $this->zt[$list[$i]['status']];
            $list[$i]['ddstylename'] = $this->ddstyle[$list[$i]['ddstyle']];
            $list[$i]['jkstyle'] = $jiekouBuffer[$list[$i]['jkstyle']]['jkname'];
            $list[$i] = stringChange('formatMoneyByArray', $list[$i], array(
                'totalmoney',
                'havemoney',
                'dailimoney'));
        }

        $pageList = $this->pageList($count, $perpage, $map);

        //统计数据
        $tj = array();
        $tj['totalmoney'] = $order->sumData('totalmoney', $data);
        $tj['havemoney'] = $order->sumData('havemoney', $data);
        $tj['havemoney'] += $order->sumData('dailimoney', $data);
        $tj['num'] = $count;
        foreach ($tj as $i => $iTj) {
            if (empty($iTj))
                $tj[$i] = 0;
        }
        $tj = stringChange('formatMoneyByArray', $tj, array(
            'totalmoney',
            'havemoney'));

        //最近30天的对账
        $timeBuffer = array();
        for ($i = 0; $i < 90; $i++) {
            $tmptime = $yesday - $i * 24 * 3600;
            $timeBuffer[] = array(
                'time' => $tmptime,
                'name' => date('Y-m-d', $tmptime)
            );
        }

        $params = array(
            'list' => $list,
            'tj' => $tj,
            'page' => $pageList,
            'timeBuffer' => $timeBuffer,
            'pzBufferList' => $pzBufferList,
            'pzBuffer' => $pzBuffer,
            'pageName' => '对账记录'
        );
        return [1,
            $params];
    }

    /**
     * 重新发送订单
     */
    public function edit($request) {
        if (!$request['id']) {
            return [0,
                '参数错误！'];
        }

        $agentBuffer = SM('DingdanAgent')->selectData('*', 'ddid=' . $request['id'], 'id asc');

        $order = SM('Dingdan');
        $row = $order->findData('*', 'ddid=' . $request['id']);

        $result = $this->getParamsByID($row);
        if ($result[0] == 0)
            return $result;

        $post_data = $result[1][1];
        $str = array();
        foreach ($post_data as $k => $buffer) {
            $str[] = $k . '=' . urlencode($buffer);
        }

        $row['params'] = implode('&', $str);
        $row['notifyurl'] = $result[1][0];
        $row['notifystyle'] = $result[1][2]['fxnotifystyle'];
        $row['sigleddh'] = $post_data['fxddh'];

        //添加更多订单数据
        $tmp=$result[1][2];
        $post_data['fxnotifyurl']=$tmp['fxnotifyurl'];
        $post_data['fxbackurl']=$tmp['fxbackurl'];
        $post_data['fxip']=$tmp['fxip'];
        $post_data['fxbankcode']=$tmp['fxbankcode'];
        $post_data['fxfs']=$tmp['fxfs'];
        $post_data['fxuserid']=$tmp['fxuserid'];
        $post_data['fxnotifystyle']=$tmp['fxnotifystyle'];

        $title = '重发' . $this->moduleName;
        switch ($request['e']) {
            case 1:
                $title = '手动补单';
                break;
            case 2:
                $title = '手动撤单';
                break;
            default:

                break;
        }

        $params = array(
            'agentBuffer' => $agentBuffer,
            'edit' => $row,
            'data' => $post_data,
            'e' => $request['e'],
            'act' => 'edit',
            'pageName' => $title
        );
        return [1,
            $params,
            'Dingdan/add'];
    }

    /**
     * 根据订单号获取发送数据
     * @param array $row 订单表数据内容
     */
    private function getParamsByID($row) {
        $userBuffer = SM('User')->findData('*', 'userid="' . $row['userid'] . '"');

        if (!$userBuffer)
            return [0,
                '订单用户不存在。'];

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

        return [1,
            [$fj['fxnotifyurl'],
                $post_data,
                $fj]];
    }

    /**
     * 批量重新发送订单
     */
    public function editall($request) {
        //待发订单
        $buffer = SM('Dingdan')->selectData('*', 'status=1 and tz<2', 'ddid desc');

        foreach ($buffer as $i => $iBuffer) {
            $buffer[$i]['paytime'] = stringChange('formatDateTime', $iBuffer['paytime']);
        }

        $params = array(
            'list' => $buffer,
            'act' => 'edit',
            'pageName' => '批量补单'
        );
        return [1,
            $params,
            'Dingdan/editall'];
    }

    /**
     * 重新发送订单
     */
    public function save($request) {
        $ddh = $request['ddh'];
        //判断数据标识
        if (empty($ddh)) {
            return [0,
                '订单号不能为空！'];
        }

        $dingdan = SM('Dingdan');
        $buffer = $dingdan->findData('*', 'ordernum="' . $ddh . '"');
        if (!$buffer) {
            return [0,
                '订单号不存在！'];
        }

        switch ($request['e']) {
            case 1:
                //手动补单
                $data = array(
                    'ddh' => $ddh,
                    'fee' => $buffer['totalmoney'],
                    'qudao' => 'qzf' . date('YmdHis') . getDingdanRand(),
                    'method' => 'post'
                );
                $buffer = SA('Base')->changeDingdan($data);
                if ($buffer[0] == 1) {
                    return [1,
                        '手动补单成功。'];
                }
                return $buffer;
                break;
            case 2:
                //手动撤单
                $buffer = SA('Base')->changeDingdanReback($ddh);
                if ($buffer[0] == 1) {
                    return [1,
                        '手动撤单成功。'];
                }
                return $buffer;
                break;
        }

        $url = $request['url'];
        if (empty($url)) {
            //获取参数
            $result = $this->getParamsByID($buffer);
            if ($result[0] == 0)
                return $result;
            $params = $result[1][1];
            $url = $result[1][0];
            $notifystyle = $result[1][2]['fxnotifystyle'];
        }else {
            $notifystyle = $request['notifystyle'];
            $params = $request['params']; //获取模板标识
            if (empty($params)) {
                return [0,
                    '参数不能为空！'];
            }
            $params = urldecode(htmlspecialchars_decode($params));
            $tmparr = explode('&', $params);
            $arr = array();
            foreach ($tmparr as $i => $iTmparr) {
                $tmp = explode('=', $iTmparr);
                $arr[$tmp[0]] = $tmp[1];
            }
            $params = $arr;
        }

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

    /**
     * 改变通知状态
     */
    public function savetz($request) {
        $id = $request['id'];
        $result = SM('Dingdan')->findData('*', 'ddid=' . $id);
        if (!$result) {
            return [0,
                '订单不存在'];
        }
        if ($result['status'] != 1) {
            return [0,
                '订单状态异常。刷新重试。'];
        }
        if ($result['tz'] == 2) {
            return [1,
                '操作成功。'];
        }
        //$result=SM('Dingdan')->updateData(['tz'=>2],['ddid'=>$id]);
        if ($result === false) {
            return [0,
                '操作失败，请重试。'];
        }
        return [1,
            '操作成功。'];
    }

    /**
     * 删除
     */
    public function delete($request) {
        $orderID = $request['id']; //获取数据标识
        $clear = $request['clear']; //获取数据标识
        //清除三天以上的未支付订单
        if ($clear) {
            if (SM('Dingdan')->deleteData('addtime<' . (time() - 3 * 24 * 3600) . ' and status=0') === false) {
                return [0,
                    '删除失败'];
            } else {
                //写入日志
                $this->adminLog($this->moduleName, '删除订单3天以上未支付的数据');
                return [1,
                    '删除成功',
                    __URL__];
            }
        }

        $idArray = explode(',', $orderID);

        if (!$orderID) {
            return [0,
                '数据标识不能为空',
                __URL__];
        }

        //只能删除未支付订单
        if (SM('Dingdan')->deleteData(
                        'status=0 and ddid in (' . implode(',', $idArray) . ')') === false) {
            return [0,
                '删除失败'];
        } else {
            //写入日志
            $this->adminLog($this->moduleName, '删除订单DingdanID为【' . implode(',', $idArray) . '】的数据');
            return [1,
                '删除成功',
                __URL__];
        }
    }

    /**
     * 未支付订单
     */
    public function wei($request) {
        header('Location:' . U('Dingdan/index', array(
                    'status' => 0)));
        exit();
    }

    /**
     * 扣量订单
     */
    public function kou($request) {
        header('Location:' . U('Dingdan/index', array(
                    'status' => 2)));
        exit();
    }

    /**
     * 接口日志
     */
    public function pay($request) {
        $map = array();
        $data = ' 1=1 ';
        //高级查询
        if ($request['userid']) {
            $request['userid'] = $request['userid'];
            $data.=' AND userid = "' . $request['userid'] . '" ';
        }
        if ($request['fxddh']) {
            $map['fxddh'] = $request['fxddh'];
            $data.=' AND fxddh = "' . $request['fxddh'] . '" ';
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
        if (is_numeric($request['status'])) {
            $map['status'] = $request['status'];
            $data.=' AND status = "' . $request['status'] . '" ';
        }

        $perpage = C('FX_PERPAGE'); //每页行数
        $order = SM('DingdanPay');
        $count = $order->selectCount(
                $data, 'id'
        ); // 查询满足要求的总记录数
        // 进行分页数据查询 注意limit方法的参数要使用Page类的属性
        $page = page($count, $request['p'], $perpage) . ',' . $perpage;

        $list = $order->pageData(
                '*', $data, 'id DESC', $page
        );

        $zjArray = array();
        foreach ($list as $i => $iList) {
            $list[$i]['addtime'] = stringChange('formatDateTime', $iList['addtime']);
            $list[$i]['statusname'] = $list[$i]['status'] == 1 ? '正常' : '失败';
            if ($list[$i]['http'] == '')
                $list[$i]['http'] = '-';
            $list[$i] = stringChange('formatMoneyByArray', $list[$i], array(
                'fxfee'));
            if ($list[$i]['ip'] == '0.0.0.0')
                $list[$i]['ip'] = '本地';
        }

        $pageList = $this->pageList($count, $perpage, $map);

        $params = array(
            'list' => $list,
            'page' => $pageList,
            'pageName' => '接口日志管理'
        );
        return [1,
            $params];
    }

    /**
     * 接口日志详细
     */
    public function payedit($request) {
        $id = explode(',', $request['id']);
        //显示补单信息
        if (!$request['id']) {
            return [0,
                '参数错误！'];
        }
        $row = SM('DingdanPay')->findData('*', 'id=' . $request['id']);
        $row['content'] = unserialize($row['content']);
        $row['statusname'] = $row['status'] == 1 ? '正常' : '失败';
        $row['addtime'] = date('Y-m-d H:i:s', $row['addtime']);

        $params = array(
            'edit' => $row,
            'act' => 'edit',
            'pageName' => '接口日志详细'
        );
        return [1,
            $params,
            'Dingdan/payedit'];
    }

    /**
     * 接口日志删除
     */
    public function paydelete($request) {
        $orderID = $request['id']; //获取数据标识
        $clear = $request['clear']; //获取数据标识
        //清除三天以上的成功通知订单
        if ($clear) {
            if (SM('DingdanPay')->deleteData('addtime<' . (time() - 3 * 24 * 3600)) === false) {
                return [0,
                    '删除失败'];
            } else {
                //写入日志
                $this->adminLog($this->moduleName, '删除接口日志3天以上的数据');
                return [1,
                    '删除成功',
                    U('Dingdan/pay')];
            }
        }

        $idArray = explode(',', $orderID);

        if (!$orderID) {
            return [0,
                '数据标识不能为空',
                U('Dingdan/pay')];
        }

        //只能删除支付订单
        if (SM('DingdanPay')->deleteData(
                        'id in (' . implode(',', $idArray) . ')') === false) {
            return [0,
                '删除失败'];
        } else {
            //写入日志
            $this->adminLog($this->moduleName, '删除成功接口日志ID为【' . implode(',', $idArray) . '】的数据');
            return [1,
                '删除成功',
                U('Dingdan/pay')];
        }
    }

    /**
     * 异步回调记录
     */
    public function notify($request) {
        $map = array();
        $data = ' 1=1 ';
        //高级查询
        if ($request['function']) {
            $request['function'] = $request['function'];
            $data.=' AND function = "' . $request['function'] . '" ';
        }
        if ($request['ddh']) {
            $map['ddh'] = $request['ddh'];
            $data.=' AND ddh = "' . $request['ddh'] . '" ';
        }
        if (is_numeric($request['status'])) {
            if ($request['status'] != -1) {
                $map['status'] = $request['status'];
                $data.=' AND status = "' . $request['status'] . '" ';
            }
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
        $order = SM('DingdanNotify');
        $count = $order->selectCount(
                $data, 'id'
        ); // 查询满足要求的总记录数
        // 进行分页数据查询 注意limit方法的参数要使用Page类的属性
        $page = page($count, $request['p'], $perpage) . ',' . $perpage;

        $list = $order->pageData(
                '*', $data, 'id DESC', $page
        );

        foreach ($list as $i => $iList) {
            $list[$i]['addtime'] = stringChange('formatDateTime', $iList['addtime']);
            $list[$i]['statusname'] = $this->notifyzt[$list[$i]['status']];
            if ($list[$i]['errorstr'] == '')
                $list[$i]['errorstr'] = '-';
        }
        $pageList = $this->pageList($count, $perpage, $map);

        $params = array(
            'list' => $list,
            'page' => $pageList,
            'pageName' => '异步通知日志（补单）'
        );
        return [1,
            $params];
    }

    /**
     * 异步回调记录详细及补单
     */
    public function notifyedit($request) {
        $notify = SM('DingdanNotify');

        $id = explode(',', $request['id']);
        //补单流程
        if (IS_AJAX || count($id) > 1) {
            if (empty($request['id'])) {
                $buffer = $notify->selectData('*', 'status=0');
            } else {
                $buffer = $notify->selectData('*', 'status in (0,2) and id in (' . implode(',', $id) . ')');
            }
            foreach ($buffer as $i => $iBuffer) {
                $params = array(
                    'id' => $iBuffer['id'], //编号
                    'ddh' => $iBuffer['ddh'], //订单号
                    'function' => $iBuffer['function'], //接口名称
                    'content' => $iBuffer['content'], //内容
                    'sendstyle' => $iBuffer['sendstyle'], //发送方式
                    'reback' => $iBuffer['reback'] //补单次数
                );
                $newBuffer = $this->notifyAddLeave($params);
            }
            if (count($id) == 1) {
                return $newBuffer;
            }
            return [1,
                '补单完成.'];
        }

        //显示补单信息
        if (!$request['id']) {
            return [0,
                '参数错误！'];
        }
        $row = $notify->findData('*', 'id=' . $request['id']);
        $row['statusname'] = $this->notifyzt[$row['status']];

        $params = array(
            'edit' => $row,
            'act' => 'edit',
            'e' => $request['e'],
            'pageName' => '补单操作'
        );
        return [1,
            $params,
            'Dingdan/notifyedit'];
    }

    /**
     * 异步回调补单
     * $params=array(
      'id'=>$iBuffer['id'], //订单号
      'ddh'=>$iBuffer['ddh'], //订单号
      'function'=>$iBuffer['function'], //接口名称
      'content'=>$iBuffer['content'], //内容
      'sendstyle'=>$iBuffer['sendstyle'] //发送方式
      'reback'=>$iBuffer['reback'] //补单次数
      );
     */
    public function notifyAddLeave($params) {
        //设置参数
        switch ($params['sendstyle']) {
            case 'xml':
                $GLOBALS['HTTP_RAW_POST_DATA'] = $params['content'];
                break;
            case 'json':
                $_POST = $params['content'];
                break;
            case 'post':
                $_POST = unserialize($params['content']);
                $_REQUEST = $_POST;
                break;
            case 'get':
                $_GET = unserialize($params['content']);
                $_REQUEST = $_GET;
                break;
        }

        $_REQUEST['returnss'] = 1;
        $buffer = SA(ucfirst($params['function']))->notify($_REQUEST);
        if ($buffer[0] != 1) {
            SM('DingdanNotify')->updateData(['status' => 2,
                'errorstr' => $buffer[1],
                'reback' => ($params['reback'] + 1)], ['id' => $params['id']]);
        }
        return $buffer;
    }

    /**
     * 异步回调清除
     */
    public function notifydelete($request) {
        $orderID = $request['id']; //获取数据标识
        $clear = $request['clear']; //获取数据标识
        //清除三天以上的成功通知订单
        if ($clear) {
            if (SM('DingdanNotify')->deleteData('addtime<' . (time() - 3 * 24 * 3600) . ' and status in (1,3)') === false) {
                return [0,
                    '删除失败'];
            } else {
                //写入日志
                $this->adminLog($this->moduleName, '删除成功通知订单3天以上的回调数据');
                return [1,
                    '删除成功',
                    U('Dingdan/notify')];
            }
        }

        $idArray = explode(',', $orderID);

        if (!$orderID) {
            return [0,
                '数据标识不能为空',
                U('Dingdan/notify')];
        }

        //只能删除支付订单
        if (SM('DingdanNotify')->deleteData(
                        'id in (' . implode(',', $idArray) . ')') === false) {
            return [0,
                '删除失败'];
        } else {
            //写入日志
            $this->adminLog($this->moduleName, '删除成功通知订单ID为【' . implode(',', $idArray) . '】的数据');
            return [1,
                '删除成功',
                U('Dingdan/notify')];
        }
    }

    /**
     * 获取用户冻结金额
     * @params int $userid 商户id
     * @params int $ifagent  代理默认0不是 1是
     * @params string $balancestyle 结算类型 默认空 D+N或者T+N
     * @return array
     */
    public function getFrozenMoney($userid, $ifagent = 0, $balancestyle = '') {
        if (empty($balancestyle)) {
            $userBuffer = SM('User')->findData('*', 'userid="' . $userid . '"');
            if (!$userBuffer) {
                return [0,
                    '用户不存在。'];
            }
            $ifagent = $userBuffer['ifagent'];
            global $publicData;
            $balancestyle = $publicData['peizhi']['balancestyle'] . '+' . $publicData['peizhi']['balancetime'];
            if (!empty($userBuffer['balancestyle'])) {
                $balancestyle = $userBuffer['balancestyle'] . '+' . $userBuffer['balancetime'];
            }
        }

        //获取最近几天的收入金额
        $t = 0;
        if (strstr($balancestyle, 'T') !== false) {
            $t = str_replace('T+', '', $balancestyle);
            $ga = date("w");
            if ($ga == 0)
                $t = $t + 2;
            if ($ga == 6)
                $t = $t + 1;
        }elseif (strstr($balancestyle, 'D') !== false) {
            $t = str_replace('D+', '', $balancestyle);
        } else {
            return [0,
                '结算类型有误。'];
        }

        if ($t == 0) {
            return [1,
                0];
        }

        $time = strtotime(date('Y-m-d')) - 24 * 3600 * ($t - 1);
        //排除充值订单
        $userBuffer = SM('Dingdan')->sumData('havemoney', 'ddstyle=0 and status=1 and userid=' . $userid . ' and paytime>' . $time);
        if (empty($userBuffer))
            $userBuffer = 0;

        $agentBuffer = 0;
        if ($ifagent) {
            $agentBuffer = SM('DingdanAgent')->sumData('agentmoney', 'agent="' . $userid . '" and addtime between ' . $time . ' and ' . time());
            if (empty($agentBuffer))
                $agentBuffer = 0;
            $agentBuffer += SM('Fandian')->sumData('havemoney', 'userid=' . $userid . ' and addtime>=' . $time . ' and status=2');
            if (empty($agentBuffer))
                $agentBuffer = 0;
        }
        return [1,
            $userBuffer + $agentBuffer];
    }

}
