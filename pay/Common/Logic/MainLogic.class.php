<?php

// +----------------------------------------------------------------------
// | easy pay [ pay to easy ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2017 All rights reserved.
// +----------------------------------------------------------------------
// | Author: fengxing <QQ:51125330>
// +----------------------------------------------------------------------

namespace Common\Logic;

class MainLogic extends BaseLogic {

    protected $moduleName = '统计';

    /**
     * 列表
     */
    public function index($request) {
        //用户统计
        $userBuffer = $this->getUserNum();
        $allUser = 0; //所有用户
        $dlUser = 0; //代理用户
        foreach ($userBuffer as $i => $iUserBuffer) {
            $allUser+=$iUserBuffer['num'];
            if ($iUserBuffer['ifagent'] == 1)
                $dlUser+=$iUserBuffer['num'];
        }

        //订单统计
        $dingdanBuffer = $this->getDingdanNum();
        $allDingdan = 0; //所有订单
        $successDingdan = 0; //成功订单
        $allmoney = 0; //所有进账
        $paymoney = 0; //所有支出
        foreach ($dingdanBuffer as $i => $iDingdanBuffer) {
            $allDingdan+=$iDingdanBuffer['num'];
            if ($iDingdanBuffer['status'] > 0) {
                $successDingdan+=$iDingdanBuffer['num'];
                $allmoney+=$iDingdanBuffer['totalmoney'];
                if ($iDingdanBuffer['status'] == 1)
                    $paymoney+=$iDingdanBuffer['havemoney']+$iDingdanBuffer['dailimoney']+$iDingdanBuffer['syflmoney'];
            }
        }

        //上月统计
        $dingdanMonthBuffer = $this->getDingdanMonth();
        $allpremonthmoney = 0; //所有上月进账
        $paypremonthmoney = 0; //所有上月支出
        foreach ($dingdanMonthBuffer as $i => $iDingdanMonthBuffer) {
            if ($iDingdanMonthBuffer['status'] > 0) {
                $allpremonthmoney+=$iDingdanMonthBuffer['totalmoney'];
                if ($iDingdanMonthBuffer['status'] == 1)
                    $paypremonthmoney+=$iDingdanMonthBuffer['havemoney']+$iDingdanMonthBuffer['dailimoney']+$iDingdanMonthBuffer['syflmoney'];
            }
        }

        //前30天统计
        $dingdan30MonthBuffer = $this->getDingdan30Month();
        $alldingdan30 = 0; //30天内的订单数
        $havedingdan30 = 0; //30天内的成功订单
        $alldingdanmoney30 = 0; //成功订单进账
        $havedingdanmoney30 = 0; //30天内的支出
        foreach ($dingdan30MonthBuffer as $i => $iDingdan30MonthBuffer) {
            $dingdan30MonthBuffer[$i]['day']=date('md',strtotime($iDingdan30MonthBuffer['paytimes']));//转换格式
            $alldingdan30+=$iDingdan30MonthBuffer['num'];
            if ($iDingdan30MonthBuffer['status'] > 0) {
                $alldingdanmoney30+=$iDingdan30MonthBuffer['totalmoney'];
                $havedingdan30+=$iDingdan30MonthBuffer['num'];
                if ($iDingdan30MonthBuffer['status'] == 1)
                    $havedingdanmoney30+=$iDingdan30MonthBuffer['totalmoney'] - $iDingdan30MonthBuffer['havemoney']- $iDingdan30MonthBuffer['dailimoney']- $iDingdan30MonthBuffer['syflmoney'];
                if ($iDingdan30MonthBuffer['status'] == 2)
                    $havedingdanmoney30+=$iDingdan30MonthBuffer['totalmoney'];
            }
        }

        //当天订单统计
        $todaydingdan = $this->getDingdanNum(1);
        $alltodaydingdan = 0; //今天所有订单数
        $havetodaydingdan = 0; //今天成功订单数
        $kltodaydingdan = 0; //今天扣量订单数
        $alltodaymoney = 0; //今天进账资金
        $havetodaymoney = 0; //今天收益资金
        $paytodaymoney = 0; //今天支出资金
        foreach ($todaydingdan as $i => $iTodaydingdan) {
            $alltodaydingdan+=$iTodaydingdan['num'];
            if ($iTodaydingdan['status'] > 0) {
                $havetodaydingdan+=$iTodaydingdan['num'];
                $alltodaymoney+=$iTodaydingdan['totalmoney'];
                if ($iTodaydingdan['status'] == 1) {
                    $paytodaymoney+=$iTodaydingdan['havemoney']+$iTodaydingdan['dailimoney']+$iTodaydingdan['syflmoney'];
                } elseif ($iTodaydingdan['status'] == 2) {
                    $kltodaydingdan+=$iTodaydingdan['num'];
                }
            }
        }
        $havetodaymoney = $alltodaymoney - $paytodaymoney;

        //当天打款统计
        $todaypay = $this->getPayToday();
        $allpay = 0; //今天所有打款金额
        $havepay = 0; //今天已经支付金额
        $nopay = 0; //今天未支付金额
        foreach ($todaypay as $i => $iTodaypay) {
            $allpay+=$iTodaypay['totalmoney'];
            if ($iTodaypay['status'] == 1)
                $havepay+=$iTodaypay['totalmoney'];
            elseif ($iTodaypay['status'] == 0)
                $nopay+=$iTodaypay['totalmoney'];
        }


        $buffer = array();
        $buffer['alluser'] = $allUser; //商户数
        $buffer['dluser'] = $dlUser; //代理数
        $buffer['alldingdan'] = $allDingdan; //总订单数
        if($allDingdan==0) $buffer['successdingdan'] = '0%'; //成功率
        else $buffer['successdingdan'] = round($successDingdan / $allDingdan * 100, 2) . '%'; //成功率

        $buffer['allmoney'] = $allmoney; //总金额
        if($allmoney==0) $buffer['paymoneylv'] = '0%'; //支出比例
        else $buffer['paymoneylv'] = round($paymoney / $allmoney * 100, 2) . '%'; //支出比例
        $buffer['havemoney'] = $allmoney - $paymoney; //支出金额
        if($paymoney==0) $buffer['havemoneylv'] = '0%'; //收入比例
        else $buffer['havemoneylv'] = round(($allmoney - $paymoney) / $allmoney * 100, 2) . '%'; //收入比例

        $buffer['allpremonthmoney'] = $allpremonthmoney; //总金额
        if($allpremonthmoney==0) $buffer['paypremonthmoneylv'] = '0%'; //收入比例
        else $buffer['paypremonthmoneylv'] = round($paypremonthmoney / $allpremonthmoney * 100, 2) . '%'; //支出比例
        $buffer['havepremonthmoney'] = $allpremonthmoney - $paypremonthmoney; //支出金额
        if($allpremonthmoney==0) $buffer['havepremonthmoneylv'] = '0%'; //收入比例
        else $buffer['havepremonthmoneylv'] = round(($allpremonthmoney - $paypremonthmoney) / $allpremonthmoney * 100, 2) . '%'; //收入比例

        $buffer['alldingdanmoney30'] = $alldingdanmoney30; //近30天流水
        $buffer['havedingdan30'] = $havedingdan30; //近30天成功订单数
        $buffer['havedingdanmoney30'] = round($havedingdanmoney30, 2); //近30天收入
        if($alldingdan30==0) $buffer['havedingdan30lv'] = '0%'; //收入比例
        else $buffer['havedingdan30lv'] = round($havedingdan30 / $alldingdan30 * 100, 2) . '%'; //近30天订单率
        if($alldingdanmoney30==0) $buffer['havedingdanmoney30lv'] = '0%'; //收入比例
        else $buffer['havedingdanmoney30lv'] = round($havedingdanmoney30 / $alldingdanmoney30 * 100, 2) . '%'; //近30天收入率

        $buffer['alltodaydingdan'] = $alltodaydingdan; //今天订单数
        $buffer['havetodaydingdan'] = $havetodaydingdan; //今天成功订单数
        $buffer['kltodaydingdan'] = $kltodaydingdan; //今天扣量订单数
        $buffer['alltodaymoney'] = $alltodaymoney; //今天金额
        $buffer['havetodaymoney'] = $havetodaymoney; //今天收入
        $buffer['paytodaymoney'] = $paytodaymoney; //今天支出

        $buffer['allpay'] = $allpay; //今天所有支付款
        $buffer['havepay'] = $havepay; //今天已支付款
        $buffer['nopay'] = $nopay; //今天未支付款
        //处理报表
        $returnData = [];
        $dingdan30MonthBuffer = stringChange('arrayKey', $dingdan30MonthBuffer, 'day');
        for ($i = 30; $i > 0; $i--) {
            $date = date('md', time() - $i * 24 * 3600);

            $totalmoney = $dingdan30MonthBuffer[$date]['totalmoney'];
            if (empty($totalmoney))
                $totalmoney = 0;
            $returnData[] = array(
                'totalmoney' => $totalmoney,
                'date' => $date);
        }
        if(empty($returnData)) $returnData=0;
        else  $returnData=json_encode($returnData);

        $payStyleStatus = $this->getPayStyleStatus();

        $params = array(
            'buffer' => $buffer,
            'dingdan30MonthBuffer' =>$returnData,
            'payStyleStatus' => $payStyleStatus,
            'pageName' => '后台主页'
        );
        return [1,
            $params];
    }

    /**
     * 获取商户和代理数量
     */
    public function getUserNum() {
        $buffer = SM('User')->groupData('count(id) as num,ifagent', 'status=0', 'ifagent');
        return $buffer;
    }

    /**
     * 获取订单状态统计
     */
    public function getDingdanNum($istoday = 0) {
        $where = '1=1';
        if ($istoday)
            $where = ' addtime >' . strtotime(date('Y-m-d'));
        $buffer = SM('Dingdan')->groupData('count(ddid) as num,sum(totalmoney) as totalmoney,sum(havemoney) as havemoney,sum(dailimoney) as dailimoney,sum(syflmoney) as syflmoney,status', $where, 'status');
        return $buffer;
    }

    /**
     * 获取上月信息
     */
    public function getDingdanMonth() {
        $start = strtotime(date('Y-m-1', strtotime('-1 month')));
        $end = strtotime(date('Y-m-1'));
        $buffer = SM('Dingdan')->groupData('count(ddid) as num,sum(totalmoney) as totalmoney,sum(havemoney) as havemoney,sum(dailimoney) as dailimoney,sum(syflmoney) as syflmoney,status', 'paytime between ' . $start . ' and ' . $end, 'status');
        return $buffer;
    }

    /**
     * 获取最近30天流水
     */
    public function getDingdan30Month() {
        $buffer = SM('Dingdan')->getGroup30Month();
        return $buffer;
    }

    /**
     * 获取打款统计
     */
    public function getPayToday() {
        $where = ' addtime >' . strtotime(date('Y-m-d'));
        $buffer = SM('Pay')->groupData('count(id) as num,sum(money) as totalmoney,status', $where, 'status');
        return $buffer;
    }

    /**
     * 获取通道状态
     */
    public function getPayStyleStatus() {
        $buffer = SM('Jiekou')->selectData('*', '1=1');
        global $publicData;
        $httpDefaultBuffer = SM('Http')->findData('*', 'locked!=1 and ifdefault=1');
        $defaultHttp = $publicData['peizhi']['httpstyle'] . '://' . $_SERVER['HTTP_HOST'];
        if (!empty($httpDefaultBuffer))
            $defaultHttp = $httpDefaultBuffer['http'];
        foreach ($buffer as $i => $iBuffer) {
            $buffer[$i]['ifroundname'] = $buffer[$i]['ifround'] == 1 ? '开启' : '关闭';
            $buffer[$i]['ifopenname'] = $buffer[$i]['ifopen'] == 1 ? '开启' : '关闭';
            $buffer[$i]['ifuseropenname'] = $buffer[$i]['ifuseropen'] == 1 ? '开启' : '关闭';

            $buffer[$i]['httppath'] = '';
            $httpid = 0;
            if ($buffer[$i]['ifround'] && $buffer[$i]['roundhttpid']) {
                $httpid = $buffer[$i]['roundhttpid'];
            } elseif ($buffer[$i]['httpid']) {
                $httpid = $buffer[$i]['httpid'];
            }
            if ($httpid) {
                $httpBuffer = SM('Http')->findData('*', 'locked!=1 and id=' . $httpid);
                $buffer[$i]['httppath'] = $httpBuffer['http'];
            }
            if (empty($buffer[$i]['httppath']))
                $buffer[$i]['httppath'] = $defaultHttp;
        }
        return $buffer;
    }

    /**
     * 获取通道统计
     */
    public function getPayStyleNum($time = 0) {
        $where = 'status>0';
        if ($time == 1) {
            $where = ' addtime >' . strtotime(date('Y-m-d'));
        }
        $buffer = SM('Dingdan')->groupData('count(ddid) as num,sum(totalmoney) as totalmoney,sum(havemoney) as havemoney,style', $where, 'status');
        return $buffer;
    }

}
