<?php

// +----------------------------------------------------------------------
// | easy pay [ pay to easy ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2017 All rights reserved.
// +----------------------------------------------------------------------
// | Author: fengxing <QQ:51125330>
// +----------------------------------------------------------------------

namespace Common\Logic;

class RepayLogic extends BaseLogic {

    protected $fxid; //商户号
    protected $fxkey; //商户秘钥

    /**
     * 代付申请
     */

    public function paymoney($request) {

        $fxid = $request['fxid'];
        $fxaction = $request['fxaction'];
        $fxnotifyurl = $request['fxnotifyurl'];
        $fxnotifystyle = $request['fxnotifystyle'];
        $fxbody = stripslashes($request['fxbody']);
        $fxsign = $request['fxsign'];

        $this->fxid = $fxid;

        if (empty($fxid)) {
            return [0,
                $this->backError('参数格式有误。请尝试用html表单提交。')];
        }

        //判断商户号 key是否存在
        $userBuffer = SM('User')->findData('*', 'userid=' . $fxid);
        if (!$userBuffer || $userBuffer['status'] == 1) {
            return [0,
                $this->backError('商户号错误。')];
        }
        $fxkey = $userBuffer['miyao'];
        $this->fxkey = $fxkey;

        //判断回调地址是否是http
        if (!checkString('checkIfHttp', $fxnotifyurl)) {
            return [0,
                $this->backError('异步回调网址有误。')];
        }

        //签名
        if ($fxsign != md5($fxid . $fxaction . $fxbody . $fxkey)) {
            return [0,
                $this->backError('签名错误。')];
        }

        //信息域是否正常
        $fxbody = json_decode($fxbody, true);
        if (!is_array($fxbody[0]) || empty($fxbody[0])) {
            return [0,
                $this->backError('订单信息域格式错误，请使用二维数组转json字符串。')];
        }

        //判断提现时间
        global $publicData;
        $peizhi = $publicData['peizhi'];
        //判断提现时间
        $result = SL('Param')->checkPayTime($peizhi['txpaytime']);
        if (!$result)
            return [0,
                $this->backError('请在提现允许时间段内操作。')];

        //代付费率
        $dffl = SL('User')->getdffl($userBuffer['iffl'], $userBuffer['dffl']);

        //获取用户余额,去除用户冻结金额
        //可用金额冻结金额
        $result = SL('Dingdan')->getFrozenMoney($fxid);
        $todaymoney = 0; //冻结金额
        if ($result[0] == 1)
            $todaymoney = $result[1];
        $userMoney = $userBuffer['money'] - (int) $todaymoney; //可用金额

        $return = array();
        $pay = SM('Pay');
        //处理订单
        foreach ($fxbody as $i => $iFxbody) {
            $return[$i]['fxddh'] = $iFxbody['fxddh'];
            $sxf = SL('User')->calcdffl($iFxbody['fxfee'], $dffl);
            $needmoney = $iFxbody['fxfee'] + $sxf;
            //判断余额
            if ($needmoney > $userMoney) {
                $return[$i]['fxstatus'] = 0;
                $return[$i]['fxfee'] = $iFxbody['fxfee'];
                $return[$i]['fxcode'] = '商户余额不足。';
                continue;
            }
            //判断最低提现额度
            if ($peizhi['minpay'] > $iFxbody['fxfee']) {
                $return[$i]['fxstatus'] = 0;
                $return[$i]['fxfee'] = $iFxbody['fxfee'];
                $return[$i]['fxcode'] = '提现金额小于最小要求金额！最小提现金额' . $peizhi['minpay'] . '元';
                continue;
            }

            //判断订单是否存在
            $buffer = SM('Pay')->findData('*', 'ddh="' . $fxid . $iFxbody['fxddh'] . '"');
            if ($buffer) {
                $return[$i]['fxstatus'] = 0;
                $return[$i]['fxfee'] = $iFxbody['fxfee'];
                $return[$i]['fxcode'] = '订单号存在。';
                continue;
            }

            $pay->dbStartTrans(); //开始事务
            $flag = true; //事务标志
            //去除余额
            $result1 = SM('User')->conAddData('money=money-' . $needmoney, 'userid=' . $fxid, 'money');
            $result2 = SM('User')->conAddData('tx=tx+' . $iFxbody['fxfee'], 'userid=' . $fxid, 'tx');
            if ($result1 === false || $result2 === false)
                $flag = false;

            //添加资金变动
            $userBuffer = SM('User')->findData('*', 'userid=' . $fxid);
            $data = array(
                'userid' => $fxid,
                'leavemoney' => $userBuffer['money'],
                'changemoney' => 0 - $needmoney,
                'desc' => '批量代付提现：' . $iFxbody['fxfee'] . '元,手续费：' . $sxf . '元',
                'style' => 2,
                'ddh' => $fxid . $iFxbody['fxddh'],
            );
            $result1 = SL('Pay')->moneylogadd($data);
            if ($result1 === false)
                $flag = false;

            $fj=array(
                'fxnotifystyle'=>$fxnotifystyle
            );

            //写入支付申请表
            $result1 = SM('Pay')->insertData([
                'userid' => $fxid,
                'ddh' => $fxid . $iFxbody['fxddh'],
                'addtime' => time(),
                'money' => $iFxbody['fxfee'],
                'status' => 0,
                'realname' => $iFxbody['fxname'],
                'ka' => $iFxbody['fxbody'],
                'address' => $iFxbody['fxaddress'],
                'zhihang' => $iFxbody['fxzhihang'],
                'sheng' => $iFxbody['fxsheng'],
                'shi' => $iFxbody['fxshi'],
                'lhh' => $iFxbody['fxlhh'],
                'dffl' => $sxf,
                'notifyurl' => $fxnotifyurl,
                'fj' => serialize($fj)
            ]);
            if ($result1 === false)
                $flag = false;

            //处理事务
            if ($flag === false) {
                $pay->dbRollback();
                $return[$i]['fxstatus'] = 0;
                $return[$i]['fxfee'] = $iFxbody['fxfee'];
                $return[$i]['fxcode'] = '代付申请失败，请重试。';
                continue;
            } else {
                $pay->dbCommit();

                $status = 1; //申请成功
                $fxcode = '代付申请成功。';

                //自动代付提交
                if (($peizhi['ifdaifuauto'] == 1 && $userBuffer['ifdaifuauto'] == -1) || $userBuffer['ifdaifuauto'] == 1) {
                    $thisdaifuid = $peizhi['daifuid'];
                    $thisdaifubank = $peizhi['daifubank'];
                    if ($userBuffer['daifuid'] > 0) {
                        $thisdaifuid = $userBuffer['daifuid'];
                        $thisdaifubank = $userBuffer['daifubank'];
                    }
                    if ($thisdaifuid > 0) {
                        $data = [
                            'id' => $result1,
                            'pzid' => $thisdaifuid,
                            'paybank' => $thisdaifubank,
                            'ifnotify' => 1 //不发送异步
                        ];
                        //获取支付状态，便于返回支付状态
                        $result = SL('Pay')->dfSave($data);
                        if (isset($result[1]['status'])) {
                            switch ($result[1]['status']) {
                                case '1':
                                    $status = 3;
                                    $fxcode = $result[1]['msg'];
                                    break;
                                case '0':
                                    $status = 0;
                                    $fxcode = $result[1]['msg'] . '，等待管理员处理。';
                                    break;
                                case '2':
                                    break;
                            }
                        }
                    }
                }

                $return[$i]['fxstatus'] = $status;
                $return[$i]['fxfee'] = $iFxbody['fxfee'];
                $return[$i]['fxcode'] = $fxcode;
                $userMoney = $userMoney - $iFxbody['fxfee'];
            }
        }

        $result = [
            'fxid' => $this->fxid,
            'fxstatus' => 1,
            'fxbody' => json_encode($return),
            'fxmsg' => '提交成功',
        ];

        $result['fxsign'] = md5($result['fxstatus'] . $result['fxid'] . $result['fxbody'] . $this->fxkey);
        return [1,
            $result];
    }

    /**
     * 代付查询
     */
    public function payquery($request) {

        $fxid = $request['fxid'];
        $fxaction = $request['fxaction'];
        $fxbody = stripslashes($request['fxbody']);
        $fxsign = $request['fxsign'];

        $this->fxid = $fxid;

        //判断商户号 key是否存在
        $userBuffer = SM('User')->findData('*', 'userid=' . $fxid);
        if (!$userBuffer || $userBuffer['status'] == 1) {
            return [0,
                $this->backError('商户号错误。')];
        }
        $fxkey = $userBuffer['miyao'];
        $this->fxkey = $fxkey;

        //签名
        if ($fxsign != md5($fxid . $fxaction . $fxbody . $fxkey)) {
            return [0,
                $this->backError('签名错误。')];
        }

        //信息域是否正常
        $fxbody = json_decode($fxbody, true);
        if (!is_array($fxbody[0]) || empty($fxbody[0])) {
            return [0,
                $this->backError('订单信息域格式错误，请使用二维数组转json字符串。')];
        }

        $return = array();
        $pay = SM('Pay');
        $status = array(
            '申请提现',
            '已支付',
            '冻结',
            '已取消'
        );
        //处理订单
        foreach ($fxbody as $i => $iFxbody) {
            $return[$i]['fxddh'] = $iFxbody['fxddh'];

            //判断订单是否存在
            $buffer = SM('Pay')->findData('*', 'ddh="' . $fxid . $iFxbody['fxddh'] . '"');
            if (!$buffer) {
                $return[$i]['fxstatus'] = -1;
                $return[$i]['fxfee'] = 0;
                $return[$i]['fxcode'] = '订单号不存在。';
                continue;
            }

            //触发订单查询，为了那些没有回调的代付接口
            //获取订单状态
            $return[$i]['fxstatus'] = $buffer['status'];
            $return[$i]['fxfee'] = $buffer['money'];
            $return[$i]['fxcode'] = $status[$buffer['status']];
        }

        $result = [
            'fxid' => $this->fxid,
            'fxstatus' => 1,
            'fxbody' => json_encode($return),
            'fxmsg' => '查询成功',
        ];

        $result['fxsign'] = md5($result['fxstatus'] . $result['fxid'] . $result['fxbody'] . $this->fxkey);
        return [1,
            $result];
    }

    /**
     * 错误返回
     */
    private function backError($fxmsg) {
        $return = [
            'fxid' => $this->fxid,
            'fxstatus' => 0,
            'fxbody' => '',
            'fxmsg' => $fxmsg,
        ];

        $return['fxsign'] = md5($return['fxstatus'] . $return['fxid'] . $return['fxbody'] . $this->fxkey);

        return $return;
    }

}
