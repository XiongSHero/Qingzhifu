<?php

// +----------------------------------------------------------------------
// | easy pay [ pay to easy ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2017 All rights reserved.
// +----------------------------------------------------------------------
// | Author: fengxing <QQ:51125330>
// +----------------------------------------------------------------------

namespace Common\Logic;

class ParamLogic extends BaseLogic {

    protected $moduleName = '参数';
    public $template = array(
        'auto'=>'官方默认',
        'kuaiyun'=>'黑色',
        'wiipay'=>'白色简约',
        //'ipaynow'=>'纯色朴素',
        'ispay'=>'简洁大气'
    );

    /**
     * 列表
     */
    public function index($request) {
        $pezhi = SM('Peizhi');
        $edit = $pezhi->findData(
                '*', 'id=1'
        );
        $file = SS('params');
        if (!empty($file))
            $edit = array_merge($edit, $file);

        $edit = stringChange('formatMoneyByArray', $edit, array(
            'klinitmoney',
            'minpay',
            'baozhengjin',
            'paytest',
            'minrecharge',
            'dffl'));

        $tmp = $this->formatPayTime($edit['txpaytime']);
        $edit['txpaytimestart'] = $tmp[0];
        $edit['txpaytimeend'] = $tmp[1];

        $edit['smskey']='*******';

        $payother = SM('Jiekoupeizhi')->selectData('*', 'ifrepay=1', 'pzid desc');
        $payotherKey = stringChange('arrayKey', $payother, 'pzid');
        if($payotherKey[$edit['daifuid']]['style']){
            $paybank=SA(ucfirst($payotherKey[$edit['daifuid']]['style']))->paybank;
        }

        $params = array(
            'edit' => $edit,
            'template' => $this->template,
            'payother' => $payother,
            'paybank' => $paybank,
            'pageName' => $this->moduleName . '管理'
        );
        return [1,
            $params];
    }

    /**
     * 保存
     */
    public function save($request) {
        $data = array();
        $data['sitename'] = $request['sitename'];
        $data['closeweb'] = $request['closeweb'];
        $data['ifregcheck'] = $request['ifregcheck'];
        $data['ifagent'] = $request['ifagent'];
        $data['ifcheckka'] = $request['ifcheckka'];
        $data['baozhengjin'] = $request['baozhengjin'];
        $data['bzjuserid'] = $request['bzjuserid'];
        //$data['apihttp'] = $request['apihttp'];
        //$data['apijump'] = $request['apijump'];
        $data['changeapitime'] = $request['changeapitime'];
        $data['ifkl'] = $request['ifkl'];
        $data['klvalue'] = $request['klvalue'];
        $data['klzijian'] = $request['klzijian'];
        $data['klinitmoney'] = $request['klinitmoney'];
        $data['minpay'] = $request['minpay'];
        $data['xieyi'] = $request['xieyi'];
        $data['notice'] = $request['notice'];
        //$data['ifopenapi'] = $request['ifopenapi'];
        $data['phone'] = $request['phone'];
        $data['qq'] = $request['qq'];
        $data['beian'] = $request['beian'];
        $data['balancestyle'] = $request['balancestyle'];
        $data['balancetime'] = $request['balancetime'];
        $data['paytest'] = $request['paytest'];
        $data['minrecharge'] = $request['minrecharge'];
        $data['ddhlength'] = $request['ddhlength'];
        $data['ifpaylog'] = $request['ifpaylog'];
        $data['ifnotifylog'] = $request['ifnotifylog'];
        $data['iffl'] = $request['iffl'];
        $data['dffl'] = $request['dffl'];
        $data['httpstyle'] = $request['httpstyle'];
        $data['logopath'] = $request['logopath'];
        $data['ifopenuserhttp'] = $request['ifopenuserhttp'];
        $data['ifdlmoney'] = $request['ifdlmoney'];
        $data['ifdaifuauto'] = $request['ifdaifuauto'];
        $data['daifuid'] = $request['daifuid'];
        $data['daifubank'] = $request['daifubank'];

        $txpaytimestart = $request['txpaytimestart'];
        $txpaytimeend = $request['txpaytimeend'];
        if (!empty($txpaytimestart) && !empty($txpaytimeend)) {
            $txpaytimestart = str_replace('：', ':', $txpaytimestart);
            $txpaytimeend = str_replace('：', ':', $txpaytimeend);
            $data['txpaytime'] = $txpaytimestart . '-' . $txpaytimeend;
        }

        if (empty($data['sitename'])) {
            return [0,
                '请填写网站名称'];
        }
        if (!empty($data['bzjuserid'])) {
            $buffer = SM('User')->findData('username', 'userid="' . $data['bzjuserid'] . '"');
            if (!$buffer)
                return [0,
                    '保证金收款商户id不存在。请更换。'];
        }

        $peizhi=$this->getPZ();

        //记录数据到文件
        $file = array();
        $file['ifshowerror'] = $request['ifshowerror'];
        $file['ifsms'] = $request['ifsms'];
        $file['smsstyle'] = $request['smsstyle'];
        $file['smstimes'] = $request['smstimes'];
        $file['smsaccount'] = $request['smsaccount'];

        if($request['smskey']=='*******' || empty($request['smskey'])){
            $file['smskey'] = $peizhi['smskey'];
        }else{
            $file['smskey'] = $request['smskey'];
        }
        $file['smssign'] = $request['smssign'];
        $file['smstemplate'] = $request['smstemplate'];
        $file['ifopenusercheck'] = $request['ifopenusercheck'];
        $file['ifopenusercheckstyle'] = $request['ifopenusercheckstyle'];
        $file['ifopenusercheckauto'] = $request['ifopenusercheckauto'];
        $file['template'] = $request['template'];
        //判断界面是否存在
        if($file['template']!='auto'){
            if(!file_exists(APP_PATH."Index/View/Index".$file['template'])){
                return [0,'没有该模板文件，请查看演示或前往官方购买,官方地址【http://www.qianlicc.cc】'];
            }
        }

        $file['templatecookie'] = $request['templatecookie'];
        $file['hiddenclosejiekou'] = $request['hiddenclosejiekou'];
        $file['openagentfl'] = $request['openagentfl'];
        $file['ewmcreate'] = $request['ewmcreate'];
        $file['ifagentopenuser'] = $request['ifagentopenuser'];
        $file['ifagentlevel'] = $request['ifagentlevel'];
        $file['ifagentopenusercheck'] = $request['ifagentopenusercheck'];
        SS('params', $file);

        $pezhi = SM('Peizhi');
        if ($pezhi->updateData(
                        $data, 'id=1') === false) {
            return [0,
                '修改失败'];
        } else {
            //写入日志
            $this->adminLog($this->moduleName, '修改配置参数数据');
            return [1,
                '修改成功！',
                __URL__];
        }
    }

    /**
     * 获取配置信息
     */
    public function getPZ() {
        $file = SS('params');
        if (empty($file))
            $file = array();
        $data = SM('Peizhi')->findData('*', 'id=1');
        $result = array_merge($file, $data);
        return $result;
    }

    /**
     * 判断提现时间
     */
    public function formatPayTime($paytimestr = '') {
        if (empty($paytimestr)) {
            global $publicData;
            $paytimestr = $publicData['peizhi']['txpaytime'];
        }

        if (empty($paytimestr))
            $paytimestr = '00:00-24:00';

        $arr = explode('-', $paytimestr);
        if (empty($arr[0]) || !strstr($arr[0], ':'))
            $arr[0] = '00:00';
        if (empty($arr[1]) || !strstr($arr[1], ':'))
            $arr[1] = '24:00';

        $starttime = strtotime(date('Y-m-d ') . $arr[0] . ':00');
        if (empty($starttime))
            $arr[0] = '00:00';
        $endtime = strtotime(date('Y-m-d ') . $arr[1] . ':00');
        if (empty($endtime))
            $arr[1] = '24:00';

        return $arr;
    }

    /**
     * 判断提现时间
     */
    public function checkPayTime($paytimestr = '') {
        if (empty($paytimestr)) {
            global $publicData;
            $paytimestr = $publicData['peizhi']['txpaytime'];
        }
        $arr = $this->formatPayTime($paytimestr);

        $start = strtotime(date('Y-m-d ') . $arr[0] . ':00');
        $end = strtotime(date('Y-m-d ') . $arr[1] . ':00');
        $now = time();
        if (empty($end)) {
            return true;
        }

        if ($now >= $start && $now <= $end) {
            return true;
        }

        return false;
    }

}
