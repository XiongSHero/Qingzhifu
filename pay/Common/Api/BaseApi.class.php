<?php

// +----------------------------------------------------------------------
// | easy pay [ pay to easy ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2017 All rights reserved.
// +----------------------------------------------------------------------
// | Author: fengxing <QQ:51125330>
// +----------------------------------------------------------------------

namespace Common\Api;

class BaseApi {

    /**
     * 订单状态改变，在支付成功后
     * @param array $data ['ddh'=>订单号,'fee'=>金额,'qudao'=>渠道,'method'=>'post or get','back'=>【1代表返回路径】]
     */
    public function changeDingdan($data) {
        global $publicData;
        $peizhi = $publicData['peizhi'];

        //判断订单是否已经支付
        $dingdan = SM('Dingdan');
        $ddBuffer = $dingdan->findData('*', 'ordernum="' . $data['ddh'] . '"');
        if (!$ddBuffer) {
            $ddBuffer = $dingdan->findData('*', 'ordermd5="' . $data['ddh'] . '"');
            if (!$ddBuffer) {
                return [0,
                    'no order in db'];
            }
        }
        $money1 = number_format($ddBuffer['totalmoney'], 2, "", ".");
        $money2 = number_format($data['fee'], 2, "", ".");
        if ($money1 != $money2) {
            return [0,
                'pay money diff'];
        }

        if ($ddBuffer['status'] == 0) {
            $userBuffer = SM('User')->findData('*', 'userid="' . $ddBuffer['userid'] . '"');
            $zjBuffer = SM('Jiekouzj')->findData('*', 'zjid=' . $ddBuffer['zjid']);

            $dlMoneyArray = 0; //代理金额明细数组
            $dlMoneyAll = 0; //代理金额汇总数额
            if ($userBuffer['agent'] > 0) {
                //计算代理费用
                $agentData = array(
                    'userid' => $userBuffer['agent'],
                    'fl' => $ddBuffer['fl'],
                    'jkfl' => $zjBuffer['fl'],
                    'jkid' => $zjBuffer['jkid'],
                    'money' => $ddBuffer['totalmoney'],
                    'level' => 1,
                );
                $dlMoneyArray = $this->getDLMoney($agentData);
                //计算代理总费用
                foreach ($dlMoneyArray as $iDlMoneyArr) {
                    $dlMoneyAll+=$iDlMoneyArr['money'];
                }
            }

            $dingdan->dbStartTrans(); //开始事务
            $flag = true; //事务标志
            $status = 1;
            $money = 0; //商户金额
            $dlmoney = 0; //代理金额
            $dlmoneyArr = 0; //代理金额明细
            //获取商户扣量信息
            if (($peizhi['ifkl'] != 1 && $userBuffer['ifkl'] == -1) || $userBuffer['ifkl'] == 0) {
                $money = $ddBuffer['havemoney'];
                $dlmoney = $dlMoneyAll;
                $dlmoneyArr = $dlMoneyArray;
            } else {
                $zijianBuffer = SM('Zijian')->findData('*', 'userid=' . $ddBuffer['userid']);
                if (!$zijianBuffer || $zijianBuffer['initval'] == 0) {
                    //不扣量状态
                    $money = $ddBuffer['havemoney'];
                    $dlmoney = $dlMoneyAll;
                    $dlmoneyArr = $dlMoneyArray;
                } else {
                    $zijian = $zijianBuffer['zijian'] - 1;
                    if ($zijian == 0) {
                        if ($ddBuffer['totalmoney'] > $peizhi['klinitmoney'] && $ddBuffer['totalmoney'] > 0) {
                            SM('Zijian')->updateData(array(
                                'zijian' => $zijianBuffer['initval']), 'userid=' . $ddBuffer['userid']);
                            $status = 2;
                        } else {
                            $money = $ddBuffer['havemoney'];
                            $dlmoney = $dlMoneyAll;
                            $dlmoneyArr = $dlMoneyArray;
                        }
                    } else {
                        SM('Zijian')->updateData(array(
                            'zijian' => $zijian), 'userid=' . $ddBuffer['userid']);
                        $money = $ddBuffer['havemoney'];
                        $dlmoney = $dlMoneyAll;
                        $dlmoneyArr = $dlMoneyArray;
                    }
                }
            }

            //改变订单状态
            $ddBuffer['paytime'] = time();
            $ddBuffer['status'] = $status;
            $ddBuffer['preordernum'] = $data['qudao'];

            $result = SM('Dingdan')->updateData(array(
                'status' => $status,
                'paytime' => time(),
                'dailimoney' => $dlmoney,
                'preordernum' => $data['qudao']), 'ddid=' . $ddBuffer['ddid']);
            if ($result === false)
                $flag = false;

            if ($money > 0) {
                $result = SM('User')->conAddData('money=money+' . $money, 'userid=' . $ddBuffer['userid'], 'money');
                if ($result === false) {
                    $flag = false;
                } else {
                    //添加资金变动
                    $newdata = array(
                        'userid' => $ddBuffer['userid'],
                        'leavemoney' => $userBuffer['money'] + $money,
                        'changemoney' => $money,
                        'desc' => '资金流水记录：订单金额' . $ddBuffer['totalmoney'] . '元，到账金额' . $money . '元',
                        'style' => 1,
                        'ddh' => $ddBuffer['ordernum'] . '_' . rand(1000, 9999),
                    );
                    $result = SL('Pay')->moneylogadd($newdata);
                    if ($result === false)
                        $flag = false;
                }
            }
            if (!empty($dlmoneyArr)) {
                //为每一个代理增加金额
                foreach ($dlmoneyArr as $iDlmoneyArr) {
                    //记录代理数据
                    $result = SM('DingdanAgent')->insertData(
                            [
                                'ddid' => $ddBuffer['ddid'],
                                'ddh' => $ddBuffer['ordernum'],
                                'level' => $iDlmoneyArr['level'],
                                'syslevel' => $peizhi['ifagentlevel'],
                                'agent' => $iDlmoneyArr['userid'],
                                'agentmoney' => $iDlmoneyArr['money'],
                                'fl' => $iDlmoneyArr['userfl'],
                                'addtime' => time(),
                            ]
                    );
                    if ($result === false) {
                        $flag = false;
                        break;
                    }

                    if ($iDlmoneyArr['money'] <= 0)
                        continue;

                    //增加金额
                    $result = SM('User')->conAddData('money=money+' . $iDlmoneyArr['money'], 'userid=' . $iDlmoneyArr['userid'], 'money');
                    if ($result === false) {
                        $flag = false;
                        break;
                    } else {
                        //添加资金变动
                        $newdata = array(
                            'userid' => $iDlmoneyArr['userid'],
                            'leavemoney' => $iDlmoneyArr['leavemoney'] + $iDlmoneyArr['money'],
                            'changemoney' => $iDlmoneyArr['money'],
                            'desc' => '代理金流水记录：订单金额' . $ddBuffer['totalmoney'] . '元，到账金额' . $iDlmoneyArr['money'] . '元',
                            'style' => 3,
                            'ddh' => $ddBuffer['ordernum'] . '_' . rand(1000, 9999),
                        );
                        $result = SL('Pay')->moneylogadd($newdata);
                        if ($result === false) {
                            $flag = false;
                            break;
                        }
                    }
                }
            }

            //判断是否限额
            if ($zjBuffer['jetotal'] > 0 && $zjBuffer['jetotal'] != '0.00') {
                //改变订单每日额度
                $result = SM('Jiekouzj')->conAddData('jetoday=jetoday+' . $ddBuffer['totalmoney'], 'zjid=' . $ddBuffer['zjid'], 'jetoday');
                if ($result === false)
                    $flag = false;
            }

            //修改补单状态
            $result = SM('DingdanNotify')->updateData([
                'status' => 1,
                'errorstr' => ''], ['ddh' => $data['ddh']]);
            if ($result === false)
                $flag = false;

            //处理事务
            if ($flag === false) {
                $dingdan->dbRollback();
                return [0,
                    'db change error'];
            } else {
                $dingdan->dbCommit();
            }
        } else {
            $status = $ddBuffer['status'];
        }

        if ($status == 2) {
            return [1,
                'success'];
        }

        //通知商户
        return $this->rebackUser($ddBuffer, $status, $data['method'], $data['back']);
    }

    /**
     * *通知商户
     * @params array $ddBuffer 订单数据集
     * @params int $status订单状态
     * @params string $method 提交方式 post get
     * @params int $back 返回方式 1返回url
     * @return array
     */
    public function rebackUser($ddBuffer, $status, $method = 'post', $back = 0) {
        if ($ddBuffer['tz'] == 2 && $back != 1)
            return [l,
                'success'];

        //通知商户
        $userBuffer = SM('User')->findData('*', 'userid=' . $ddBuffer['userid']);
        $fj = unserialize($ddBuffer['fj']);
        $ddhYuan = substr($ddBuffer['ordernum'], strlen($ddBuffer['userid']));
        $pp = $status . $ddBuffer['userid'] . $ddhYuan . $ddBuffer['totalmoney'] . $userBuffer['miyao'];
        $k = md5($pp);
        $post_data = array(
            'fxid' => $ddBuffer['userid'],
            'fxddh' => $ddhYuan,
            'fxorder' => $ddBuffer['preordernum'],
            'fxdesc' => $fj['fxdesc'],
            'fxfee' => $ddBuffer['totalmoney'],
            'fxattch' => $fj['fxattch'],
            'fxstatus' => $status,
            'fxtime' => $ddBuffer['paytime'],
            'fxsign' => $k
        );

        if ($method == 'post') {
            $json = '';
            if ($fj['fxnotifystyle'] == 2)
                $json = 'json';
            $url = $fj['fxnotifyurl'];
            $result = CURL($url, $post_data, $json);
        } else {
            $url = $fj['fxbackurl'];
            $arr = array();
            foreach ($post_data as $i => $k) {
                $arr[] = $i . '=' . urlencode($k);
            }
            $url = $url . '?' . implode('&', $arr);
            if ($back == 1)
                return [1,
                    $url];

            $result = CURL($url);
        }

        if (strtolower($result) == 'success' && $ddBuffer['tz'] < 2) {
            //通知成功
            SM('Dingdan')->updateData(array(
                'tz' => 2), 'ddid=' . $ddBuffer['ddid']);
            return [1,
                'success'];
        } elseif ($ddBuffer['tz'] < 1) {
            SM('Dingdan')->updateData(array(
                'tz' => 1), 'ddid=' . $ddBuffer['ddid']);
        }

        $status = 0;
        if (strtolower($result) == 'success') {
            $status = 1;
        } else {
            $num = cookie('ddid' . $ddBuffer['ddid']);
            if (empty($num))
                $num = 0;
            if ($num < 4) {
                cookie('ddid' . $ddBuffer['ddid'], $num + 1);
                return [0,
                    'notify error ' . $result];
            } else {
                $status = 0;
            }
        }

        return [$status,
            $result];
    }

    /**
     * 同步回调通用
     */
    public function backurl($request) {
        $ddh = $_REQUEST['ddh'];
        //根据订单号查找对应支付账户
        $ddBuffer = SM('Dingdan')->findData('*', 'ordernum="' . $ddh . '"');
        if (!$ddBuffer) {
            return [0,
                '订单号不存在，请重试。'];
        }
        $fj = unserialize($ddBuffer['fj']);

        if ($ddBuffer['status'] == 2) {
            $ddBuffer['status'] = 0;
            $ddBuffer['paytime'] = 0;
            $ddBuffer['preordernum'] = '';
        }
        //通知商户
        $result = $this->rebackUser($ddBuffer, $ddBuffer['status'], 'get', 1);
        if ($result[0] === 1) {
            header('location:' . $result[1]);
            exit();
        } else {
            return $result;
        }
        exit();
    }

    /**
     * 异步记录 便于补单
     * @param array ['ddh'=>'20171001518185614','content'=>'<xml></xml>','function'=>'wechat','sendstyle'=>'xml']
     */
    public function notifySave($request) {
        //判断系统开关
        global $publicData;
        if ($publicData['peizhi']['ifnotifylog'] != 1) {
            return false;
        }

        if (empty($request['ddh'])) {
            return false;
        }

        $buffer = SM('DingdanNotify')->findData('*', 'ddh="' . $request['ddh'] . '"');
        if (!$buffer) {
            $request['addtime'] = time();
            $request['hits'] = 1;
            $request['status'] = 0;
            SM('DingdanNotify')->insertData($request);
        } else {
            SM('DingdanNotify')->conAddData('hits=hits+1', 'id=' . $buffer['id'], 'hits');
        }
    }

    /**
     * 代付改变订单状态
      $data=array(
      'ddh'=>$val['orderid'], 订单号
      'money'=>$val['amount'],金额
      'outddh'=>$val['transaction'],外部订单
      'msg'=>$val['returnmsg'],返回说明
      'status'=>$val['returncode']=='00'?1:0 状态 0失败 1成功 2状态不变
      'ifnotify'=>1,是否异步回调默认0回调 1不回调
      );
     */
    protected function changestatus($data) {
        //判断订单是否已经支付
        $buffer = SM('PayDingdan')->findData('*', 'ddh="' . $data['ddh'] . '"');
        if (!$buffer)
            return [0,
                '未找到代付订单。'];

        $payBuffer = SM('Pay')->findData('*', 'id="' . $buffer['payid'] . '"');
        if ($payBuffer['money'] != $data['money']) {
            return [0,
                '代付金额与提交金额不一致。'];
        }

        if ($payBuffer['status'] > 1) {
            return [0,
                '订单已被管理员处理'];
        }

        $status = 2; //代付进行中

        $return = '处理完成.';
        if ($payBuffer['status'] == 0) {
            if ($data['status'] == 1) {
                $return = '代付已支付';
                $status = 1; //代付成功
                //更新订单状态
                $paywhere = ['status' => 1,
                    'daifustatus' => 3];
                $dingdanwhere = ['status' => 1,
                    'outddh' => $data['outddh'],
                    'paytime' => time(),
                    'outdesc' => $data['msg']];
            } elseif ($data['status'] == 0) {
                $return = '当前代付提交返回状态失败';
                $paywhere = ['daifustatus' => 2];
                $dingdanwhere = ['status' => 2,
                    'outddh' => $data['outddh'],
                    'paytime' => time(),
                    'outdesc' => $data['msg']];
            } elseif ($data['status'] == 2) {
                $return = '当前代付提交返回状态代付中';
                $paywhere = ['daifustatus' => 1];
                $dingdanwhere = ['status' => 0,
                    'outddh' => $data['outddh'],
                    'paytime' => 0,
                    'outdesc' => $data['msg']];
            }

            $pay = SM('Pay');
            $pay->dbStartTrans(); //开始事务
            $flag = true;
            $result = $pay->updateData($paywhere, 'id="' . $buffer['payid'] . '"');
            if ($result === false)
                $flag = false;

            $result = SM('PayDingdan')->updateData($dingdanwhere, 'ddh="' . $data['ddh'] . '"');
            if ($result === false)
                $flag = false;

            //处理事务
            if ($flag === false) {
                $pay->dbRollback();
                return [0,
                    '数据更新失败。'];
            } else {
                $pay->dbCommit();
            }
        }

        //通知用户
        if (!empty($payBuffer['notifyurl']) && $data['ifnotify'] != 1) {
            return $this->rebackPayUser($payBuffer, $status, 'post');
        }

        if ($data['ifnotify'] == 1) {
            $return = array(
                'status' => $status,
                'msg' => $return
            );
        }

        return [1,
            $return];
    }

    /**
     * *代付异步通知商户
     * @params array $payBuffer 订单数据集
     * @params int $status订单状态
     * @params string $method 提交方式 post get
     * @return array
     */
    public function rebackPayUser($payBuffer, $status, $method = 'post', $back = 0) {
        //通知商户
        $userBuffer = SM('User')->findData('*', 'userid=' . $payBuffer['userid']);
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

        if ($method == 'post') {
            $json = '';
            $fj=unserialize($payBuffer['fj']);
            if ($fj['fxnotifystyle'] == 2)
                $json = 'json';
            $url = $payBuffer['notifyurl'];
            $result = CURL($url, $post_data, $json);
        } else {
            $url = $payBuffer['notifyurl'];
            $arr = array();
            foreach ($post_data as $i => $k) {
                $arr[] = $i . '=' . urlencode($k);
            }
            $url = $url . '?' . implode('&', $arr);
            if ($back == 1)
                return [1,
                    $url];
            $result = CURL($url);
        }

        if (strtolower($result) == 'success' && $payBuffer['tz'] < 2) {
            //通知成功
            SM('Pay')->updateData(array(
                'tz' => 2), 'id=' . $payBuffer['id']);
            return [1,
                'success'];
        } elseif ($payBuffer['tz'] < 1) {
            SM('Pay')->updateData(array(
                'tz' => 1), 'id=' . $payBuffer['id']);
        }

        $status = 0;
        if (strtolower($result) == 'success') {
            $status = 1;
        } else {
            $num = cookie('payid' . $payBuffer['id']);
            if (empty($num))
                $num = 0;
            if ($num < 4) {
                cookie('payid' . $payBuffer['id'], $num + 1);
                return [0,
                    'notify error ' . $result];
            } else {
                $status = 0;
            }
        }

        return [$status,
            $result];
    }

    /**
     * 订单状态改变，在支付成功后撤销订单
     * @param array $ddh 订单号
     */
    public function changeDingdanReback($ddh) {
        $dingdan = SM('Dingdan');
        $buffer = $dingdan->findData('*', 'ordernum="' . $ddh . '"');

        if ($buffer['status'] == 1) {
            $flag = true;
            //判断商户金额是否足够  改变商户金额
            $userBuffer = SM('User')->findData('*', 'userid="' . $buffer['userid'] . '"');
            if ($userBuffer['money'] < $buffer['havemoney']) {
                return [0,
                    '商户余额不足，无法撤单。'];
            }

            //判断代理
            $agent = SM('DingdanAgent');
            $ddAgentBuffer = $agent->selectData('*', 'ddh="' . $ddh . '"');
            if ($ddAgentBuffer) {
                foreach ($ddAgentBuffer as $ii => $iDdAgentBuffer) {
                    if ($iDdAgentBuffer['agentmoney'] <= 0 || $iDdAgentBuffer['agentmoney'] == '0.00')
                        continue;
                    $agentBuffer = SM('User')->findData('*', 'userid="' . $iDdAgentBuffer['agent'] . '"');
                    if ($agentBuffer && $agentBuffer['money'] < $iDdAgentBuffer['agentmoney']) {
                        return [0,
                            '代理商户【' . $agentBuffer['userid'] . '】余额不足，无法撤单。'];
                    }
                    if ($agentBuffer)
                        $ddAgentBuffer[$ii]['leavemoney'] = $agentBuffer['money'];
                    else
                        $ddAgentBuffer[$ii]['leavemoney'] = 0;
                }
            }

            $dingdan->dbStartTrans(); //开始事务
            $result = SM('User')->conAddData('money=money-' . $buffer['havemoney'], 'userid=' . $buffer['userid'], 'money');
            if ($result === false)
                $flag = false;
            //添加资金变动
            $newdata = array(
                'userid' => $buffer['userid'],
                'leavemoney' => $userBuffer['money'] - $buffer['havemoney'],
                'changemoney' => -$buffer['havemoney'],
                'desc' => '订单撤销资金撤回：撤销金额' . $buffer['havemoney'] . '元',
                'style' => 1,
                'ddh' => $ddh . '_' . rand(1000, 9999),
            );
            $result = SL('Pay')->moneylogadd($newdata);
            if ($result === false)
                $flag = false;

            //判断代理
            if ($ddAgentBuffer) {
                $idArr = array();
                foreach ($ddAgentBuffer as $iDdAgentBuffer) {
                    $idArr[] = $iDdAgentBuffer['id'];
                    if ($iDdAgentBuffer['agentmoney'] <= 0 || $iDdAgentBuffer['agentmoney'] == '0.00')
                        continue;
                    $result = SM('User')->conAddData('money=money-' . $iDdAgentBuffer['agentmoney'], 'userid=' . $iDdAgentBuffer['agent'], 'money');
                    if ($result === false) {
                        $flag = false;
                    } else {
                        //添加资金变动
                        $newdata = array(
                            'userid' => $iDdAgentBuffer['agent'],
                            'leavemoney' => $iDdAgentBuffer['leavemoney'] - $iDdAgentBuffer['agentmoney'],
                            'changemoney' => -$iDdAgentBuffer['agentmoney'],
                            'desc' => '订单撤销资金撤回：撤销金额' . $iDdAgentBuffer['agentmoney'] . '元',
                            'style' => 3,
                            'ddh' => $ddh . '_' . rand(1000, 9999),
                        );
                        $result = SL('Pay')->moneylogadd($newdata);
                        if ($result === false)
                            $flag = false;
                    }
                }
                //清除代理数据
                if (!empty($idArr)) {
                    $result = SM('DingdanAgent')->deleteData('id in (' . implode(',', $idArr) . ')');
                    if ($result === false)
                        $flag = false;
                }
            }

            //改变订单状态
            $result = SM('Dingdan')->updateData(['status' => 0,
                'tz' => 0,
                'preordernum' => '',
                'dailimoney' => 0,
                'paytime' => 0], ['ordernum' => $ddh]);
            if ($result === false)
                $flag = false;

            //处理事务
            if ($flag === false) {
                $dingdan->dbRollback();
                return [0,
                    '数据回滚失败，请重试。'];
            } else {
                $dingdan->dbCommit();
            }
        }

        //判断订单是否扣量
        if ($buffer['status'] == 2) {
            //改变订单状态
            SM('Dingdan')->updateData(['status' => 0,
                'tz' => 0,
                'preordernum' => '',
                'paytime' => 0], ['ordernum' => $ddh]);
        }
        return [1,
            '撤销成功。'];
    }

    //对需要转换域名的地址进行额外跳转
    public function changeUrl($url, $apihttp) {
        //如果当前域名与要求域名一致  则返回跳转地址
        $http = str_replace('http://', '', $apihttp);
        $https = str_replace('https://', '', $apihttp);
        if ($_SERVER['HTTP_HOST'] == $http || $_SERVER['HTTP_HOST'] == $https) {
            return $url;
        } else {
            return $apihttp . '/Pay/go?u=' . urlencode($url);
        }
    }

    /**
     * 根据订单号获取商户key
     * @param array $data ['ddh'=>订单号,'fee'=>金额,'qudao'=>渠道,'method'=>'post or get','back'=>【1代表返回路径】]
     */
    public function getKeyByDdh($fxddh) {
        //根据订单号查找对应支付账户
        $ddBuffer = SM('Dingdan')->findData('*', 'ordernum="' . $fxddh . '"');
        return $this->getKeyByDD($ddBuffer);
    }

    /**
     * 根据md5订单号获取商户key
     * @param array $data ['ddh'=>订单号,'fee'=>金额,'qudao'=>渠道,'method'=>'post or get','back'=>【1代表返回路径】]
     */
    public function getKeyByDdhMd5($ddhmd5) {
        //根据订单号查找对应支付账户
        $ddBuffer = SM('Dingdan')->findData('*', 'ordermd5="' . $ddhmd5 . '"');
        return $this->getKeyByDD($ddBuffer);
    }

    /**
     * 根据订单信息获取商户key
     * @param array $data ['ddh'=>订单号,'fee'=>金额,'qudao'=>渠道,'method'=>'post or get','back'=>【1代表返回路径】]
     */
    private function getKeyByDD($ddBuffer) {
        if (!$ddBuffer) {
            return [0,
                '订单号不存在，请重试。'];
        }
        //获取支付账号
        if ($ddBuffer['pzid']) {
            $pzBuffer = SM('Jiekoupeizhi')->findData('params', 'pzid="' . $ddBuffer['pzid'] . '"');
        } else {
            $pzBuffer = SL('Api')->getJkByZj($ddBuffer['zjid']);
        }
        if (!$pzBuffer) {
            return [0,
                '账户配置信息不存在，请确认。'];
        }
        $jkpz = unserialize($pzBuffer['params']);
        return [1,
            $jkpz,
            $ddBuffer['ordernum']];
    }

    /**
     * 获取代理金额
     * @params int $userid 当前代理用户id
     * @params float $money 总金额
     * @params float $jkid 当前接口id
     * @params float $fl 当前费率
     * @params float $jkfl 当前接口费率
     * @params float $level 当前等级
     * @return array
     */
    public function getDLMoney($data) {
        //用户费率减去当前费用差为当前用户获得金额
        global $publicData;
        $peizhi = $publicData['peizhi'];

        $moneyBuffer = array();
        if ($data['userid'] > 0) {
            $agentBuffer = SM('User')->findData('*', 'userid="' . $data['userid'] . '"');
            if (empty($agentBuffer) || $agentBuffer['ifagent'] != 1 || $agentBuffer['status'] == 1 || $agentBuffer['ifdlmoney'] == 1) {
                return;
            }
            //接口id是否开启
            $jkUserBuffer = SM('JiekouUser')->findData('*', 'userid="' . $data['userid'] . '" and jkid="' . $data['jkid'] . '"');
            $agentfl = $data['jkfl'];
            if ($jkUserBuffer && $jkUserBuffer['ifopen'] == 1) {
                if ($jkUserBuffer['fl'] > 0 && $jkUserBuffer['fl'] != '0.00')
                    $agentfl = $jkUserBuffer['fl'];
            }

            $dailimoney = 0;
            $nowfl = $agentfl; //当前使用的费率
            if ($agentfl < $data['fl']) {
                $dailimoney = $data['money'] * ($data['fl'] - $agentfl) / 100;
                if ($dailimoney <= 0)
                    $dailimoney = 0;
            }else {
                $nowfl = $data['fl'];
            }

            if (empty($data['level']))
                $data['level'] = 1;

            //判读当前用户是否可以获取代理费用 代理费率差开关
            $lastmoney = 0;
            if (($peizhi['ifdlmoney'] != 1 && $agentBuffer['ifdlmoney'] == -1) || $agentBuffer['ifdlmoney'] === '0') {
                $lastmoney = $dailimoney;
            }

            $moneyBuffer[] = array(
                'level' => $data['level'],
                'money' => $lastmoney,
                'userid' => $data['userid'],
                'fl' => $nowfl, //当前费率
                'userfl' => $agentfl, //当前用户费率
                'jkfl' => $data['jkfl'], //当前接口费率
                'leavemoney' => $agentBuffer['money']
            );

            if ($peizhi['ifagentlevel'] > $data['level'] && $agentBuffer['agent'] > 0) {
                //计算代理费用
                $agentData = array(
                    'userid' => $agentBuffer['agent'],
                    'fl' => $nowfl,
                    'jkfl' => $data['jkfl'],
                    'jkid' => $data['jkid'],
                    'money' => $data['money'],
                    'level' => $data['level'] + 1,
                );
                $tmp = $this->getDLMoney($agentData);
                if ($tmp) {
                    foreach ($tmp as $i => $iTmp) {
                        $moneyBuffer[] = $iTmp;
                    }
                }
            }
            return $moneyBuffer;
        }
        return;
    }

}
