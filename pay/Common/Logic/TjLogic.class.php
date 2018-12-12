<?php

// +----------------------------------------------------------------------
// | easy pay [ pay to easy ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2017 All rights reserved.
// +----------------------------------------------------------------------
// | Author: fengxing <QQ:51125330>
// +----------------------------------------------------------------------

namespace Common\Logic;

class TjLogic extends BaseLogic {

    protected $moduleName = '统计';

    /**
     * 通道统计
     */
    public function index($request) {
        $map = array();
        $data = ' 1=1 ';
        //高级查询
        if ($request['userid']) {
            $request['userid'] = $request['userid'];
            $data.=' AND userid = "' . $request['userid'] . '" ';
        }
        $start = $request['start'];
        if (strstr($start, '-')) {
            $start = strtotime($start);
        }else{
            $start = 0;
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

        $sql = 'select status,pzid,COUNT(ddid) as num,COUNT(DISTINCT(userid)) as userid,SUM(totalmoney) as totalmoney from fx_dingdan where ' . $data . ' group by pzid,status order by pzid asc';
        $list = SM('ApiDb')->query($sql);

        $pzbuffer = SM('Jiekoupeizhi')->selectData('*', '1=1', 'pzid asc');
        $pzkeybuffer = stringChange('arrayKey', $pzbuffer, 'pzid');

        $allArray = array();
        foreach ($list as $i => $iList) {
            //接口名称 通道名称 总交易金额 成功交易金额 发起笔数 成功笔数 支付人数 转化率 扣量金额 扣量笔 转化率（扣） 扣率（按笔） 扣率（金额）
            $tmpallmoney = 0;
            $tmpsuccessmoney = 0;
            $tmpkoumoney = 0;
            $tmpallnum = 0;
            $tmpsuccessnum = 0;
            $tmpkounum = 0;
            $tmpusernum = 0;
            $tmpallmoney+=$iList['totalmoney'];
            $tmpallnum+=$iList['num'];
            switch ($iList['status']) {
                case 0:
                    break;
                case 1:
                    $tmpsuccessmoney += $iList['totalmoney'];
                    $tmpsuccessnum+=$iList['num'];
                    $tmpusernum+=$iList['userid'];
                    break;
                case 2:
                    $tmpsuccessmoney += $iList['totalmoney'];
                    $tmpsuccessnum+=$iList['num'];
                    $tmpkoumoney += $iList['totalmoney'];
                    $tmpkounum+=$iList['num'];
                    $tmpusernum+=$iList['userid'];
                    break;
            }
            $thisid=$iList['pzid'];
            if (empty($allArray[$thisid])) {
                $allArray[$thisid] = array(
                    'allmoney' => 0,
                    'successmoney' => 0,
                    'koumoney' => 0,
                    'allnum' => 0,
                    'successnum' => 0,
                    'kounum' => 0,
                    'usernum' => 0,
                );
            }

            $allArray[$thisid]['allmoney']+=$tmpallmoney;
            $allArray[$thisid]['successmoney']+=$tmpsuccessmoney;
            $allArray[$thisid]['koumoney']+=$tmpkoumoney;
            $allArray[$thisid]['allnum']+=$tmpallnum;
            $allArray[$thisid]['successnum']+=$tmpsuccessnum;
            $allArray[$thisid]['kounum']+=$tmpkounum;
            $allArray[$thisid]['usernum']+=$tmpusernum;
        }

        //计算概率
        $list = array();
        foreach ($allArray as $i => $iAllArray) {
            //时间 总和交易金额 成功交易金额 发起笔数 成功笔数  扣量金额 扣量笔 转化率 转化率（扣100%） 扣率（按笔0%） 扣率（金额1%）
            if($iAllArray['allnum']==0){
                $alllv ='-';
                $koulv ='-';
            }else{
                $alllv = number_format($iAllArray['successnum'] / $iAllArray['allnum'] * 100, 2, '.', '') . '%';
                $koulv = number_format($iAllArray['kounum'] / $iAllArray['allnum'] * 100, 2, '.', '') . '%';
            }
            if($iAllArray['successnum']==0){
                $kounumlv ='-';
                $koumoneylv ='-';
            }else{
                $kounumlv = number_format($iAllArray['kounum'] / $iAllArray['successnum'] * 100, 2, '.', '') . '%';
                $koumoneylv = number_format($iAllArray['koumoney'] / $iAllArray['successmoney'] * 100, 2, '.', '') . '%';
            }

            $list[] = array(
                'pzname' => $pzkeybuffer[$i]['pzname'],
                'pzid' => $i,
                'allmoney' => $iAllArray['allmoney'],
                'successmoney' => $iAllArray['successmoney'],
                'koumoney' => $iAllArray['koumoney'],
                'allnum' => $iAllArray['allnum'],
                'successnum' => $iAllArray['successnum'],
                'kounum' => $iAllArray['kounum'],
                'alllv' => $alllv,
                'koulv' => $koulv,
                'kounumlv' => $kounumlv,
                'koumoneylv' => $koumoneylv,
                'usernum' => $iAllArray['usernum'],
            );
        }

        $params = array(
            'list' => $list,
            'pageName' => '通道统计'
        );
        return [1,
            $params];
    }

}
