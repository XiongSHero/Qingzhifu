<?php

// +----------------------------------------------------------------------
// | easy pay [ pay to easy ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2017 All rights reserved.
// +----------------------------------------------------------------------
// | Author: fengxing <QQ:51125330>
// +----------------------------------------------------------------------

/**
 * 轻支付接口
 */

namespace Common\Api;

class QzfApi extends BaseApi {

    public $wg = 'http://qzf.yuanma360.com/Pay';
    public $paybank = array(
        //'ICBC' => '工商银行',
            );

    /**
     * 支付
     */
    public function pay($request) {
        $typen = $request['fxpay'];
        switch ($typen) {
            case 'bank':
                $type = 'bank';
                break;
            default:
                $type = $typen;
                break;
        }

        $data = array(
            "fxid" => $request['peizhi']['qzf_id'], //商户号
            "fxddh" => $request['fxddh'], //商户订单号
            "fxdesc" => $request['fxdesc'], //商品名
            "fxfee" => $request['fxfee'], //支付金额 单位元
            "fxattch" => $request['fxdesc'], //附加信息
            "fxnotifyurl" => $request['fxnotifyurl'], //异步回调 , 支付结果以异步为准
            "fxbackurl" => $request['fxbackurl'], //同步回调 不作为最终支付结果为准，请以异步回调为准
            "fxpay" => $type, //支付类型 此处可选项以网站对接文档为准 微信公众号：wxgzh   微信H5网页：wxwap  微信扫码：wxsm   支付宝H5网页：zfbwap  支付宝扫码：zfbsm 等参考API
            "fxip" => $request['fxip'], //支付端ip地址
            'fxbankcode' => $request['fxbankcode'],
            'fxfs' => $request['fxfs']
        );
        $data["fxsign"] = md5($data["fxid"] . $data["fxddh"] . $data["fxfee"] . $data["fxnotifyurl"] . $request['peizhi']['qzf_key']); //加密
        $backr = CURL($this->wg, $data);
        $r = json_decode($backr, true); //json转数组
        if (empty($r))
            return [0,
                '获取支付链接失败,' . $backr]; //如果转换错误，原样输出返回

        if ($r["status"] == 1) { //验证返回信息
            return [1,
                $r["payurl"]]; //转入支付页面
        } else {
            return [0,
                '获取支付链接失败,' . $r['error']]; //输出错误信息
        }
    }

    /**
     * 异步回调
     */
    public function notify($request) {
        $fxid = $_REQUEST['fxid']; //商户编号
        $fxddh = $_REQUEST['fxddh']; //商户订单号
        $fxorder = $_REQUEST['fxorder']; //平台订单号
        $fxdesc = $_REQUEST['fxdesc']; //商品名称
        $fxfee = $_REQUEST['fxfee']; //交易金额
        $fxattch = $_REQUEST['fxattch']; //附加信息
        $fxstatus = $_REQUEST['fxstatus']; //订单状态
        $fxtime = $_REQUEST['fxtime']; //支付时间
        $fxsign = $_REQUEST['fxsign']; //md5验证签名串

        if ($request['returnss'] != 1) {
            //异步记录 便于补单
            $this->notifySave(['ddh' => $fxddh,
                'content' => serialize($_POST),
                'function' => 'qzf',
                'sendstyle' => 'post']);
        }

        //根据订单号查找对应支付账户
        $jkpz = $this->getKeyByDdh($fxddh);
        if ($jkpz[0]!=1) {
            return $jkpz;
        }
        $jkpz=$jkpz[1];
        $shkey = $jkpz['qzf_key'];

        $mysign = md5($fxstatus . $fxid . $fxddh . $fxfee . $shkey); //验证签名

        $fxddh = $fxddh; //商户订单号
        $fxfee = $fxfee; //支付金额
        $fxorder = $fxorder; //平台订单号
        $fxreturn = 'success'; //返回数据

        if (strtoupper($fxsign) === strtoupper($mysign)) {
            if ($fxstatus == '1') {//支付成功
                //支付成功 更改支付状态 完善支付逻辑
                $newdata = array();
                $newdata['ddh'] = $fxddh;
                $newdata['qudao'] = $fxorder;
                $newdata['fee'] = $fxfee;
                $newdata['method'] = 'post';
                $return = $this->changeDingdan($newdata);
                if ($return[0] === 1)
                    $result = $fxreturn;
                else
                    $result = 'logic fail';
            } else { //支付失败
                $result = 'status fail';
            }
        } else {
            $result = 'sign error';
        }

        //补单
        if ($request['returnss'] == 1) {
            if ($result == $fxreturn) {
                return [1,
                    $result];
            } else {
                return [0,
                    $result];
            }
        }

        exit($result);
    }

    /**
     * 代付申请
     * $request=array(
     * 'ddh'=>$ddh, 订单号
     * 'paybuffer'=>$paybuffer, pay表信息
     * 'params'=>$params, 支付账户信息
     * 'paybank'=>$paybank 使用的银行编号
     * 'ifnotify'=>$ifnotify 是否异步发送
     * )
     */
    public function repay($request) {
        $errormsg = ''; //错误信息
        //异步地址
        $wg = $this->wg;
        $arr = array(
            array(
                'fxddh' => $request['ddh'],
                'fxdate' => date('YmdHis'),
                'fxfee' => $request['paybuffer']['money'], //提现金额(元)
                'fxbody' => $request['paybuffer']['ka'], //银行卡号
                'fxname' => $request['paybuffer']['realname'], //持卡人姓名
                'fxaddress' => $request['paybuffer']['address'], //银行名称
                'fxzhihang' => $request['paybuffer']['zhihang'], //支行
                'fxsheng' => $request['paybuffer']['sheng'], //省
                'fxshi' => $request['paybuffer']['shi'], //市
            )
        );
        $shid = $request['params']['qzf_id'];
        $shkey = $request['params']['qzf_key'];

        $data = array(
            "fxid" => $shid, //商户号
            "fxaction" => "repay", //查询动作
            "fxnotifyurl" => $request['fxnotifyurl'], //异步返回地址
            "fxbody" => json_encode($arr), //订单信息域 json字符串数据
        );
        $data["fxsign"] = md5($data["fxid"] . $data["fxaction"] . $data["fxbody"] . $shkey); //加密
        $backr = CURL($wg, $data);
        $r = json_decode($backr, true); //json转数组

        if (empty($r)) {
            $errormsg = '代付提交失败。' . $backr; //如果转换错误，原样输出返回
        } elseif (strtoupper($r["fxsign"]) != strtoupper(md5($r["fxstatus"] . $r["fxid"] . $r["fxbody"] . $shkey))) {
            $errormsg = '签名错误。'; //如果转换错误，原样输出返回
        }

        //验证返回信息
        if (!$errormsg && $r["fxstatus"] == 1) {
            $fxbody = json_decode($r["fxbody"], true); //json转数组
            $iFxbody = $fxbody[0];

            $statusArr = array(
                0 => '申请异常',
                1 => '正常申请',
                2 => '打款中',
                3 => '已打款',
            );

            if ($iFxbody['fxstatus'] == 3 || $iFxbody['fxstatus'] == 0) {
                //更新状态
                $data = array(
                    'ddh' => $iFxbody['fxddh'],
                    'money' => $iFxbody['fxfee'],
                    'outddh' => $iFxbody['fxddh'],
                    'msg' => $r["fxmsg"] . '-' . $iFxbody['fxcode'] . '-' . $statusArr[$iFxbody['fxstatus']],
                    'status' => $iFxbody['fxstatus'] == 3 ? 1 : 0
                );
            } else {
                $data = array(
                    'ddh' => $iFxbody['fxddh'],
                    'money' => $iFxbody['fxfee'],
                    'outddh' => $iFxbody['fxddh'],
                    'msg' => '申请成功,返回状态：' . $statusArr[$iFxbody['fxstatus']] . ',说明：' . $iFxbody['fxcode'],
                    'status' => 2
                );
            }
        } else {
            if(!$errormsg){
                $errormsg='返回数据失败，代付申请失败' . $r['fxmsg'];
            }
            $data = array(
                'ddh' => $request['ddh'],
                'money' => $request['paybuffer']['money'],
                'outddh' => '',
                'msg' => $errormsg,
                'status' => 0,
            );
        }
        $data['ifnotify']=$request['ifnotify'];

        $result = $this->changestatus($data);
        return $result;
    }

    //代付异步回调
    public function repaynotify($request) {
        $ddh = $request['fxddh'];
        $ddBuffer = SM('PayDingdan')->findData('*', 'ddh="' . $ddh . '"');
        if (!$ddBuffer) {
            return [0,
                '订单号不存在，请重试。'];
        }

        //获取支付账号
        $pzBuffer = SM('Jiekoupeizhi')->findData('*', 'pzid=' . $ddBuffer['pzid']);
        $jkpz = unserialize($pzBuffer['params']);
        $shkey = $jkpz['qzf_key'];

        $pp = $request['fxid'] . $request['fxddh'] . $request['fxorder'] . $request['fxstatus']  . $request['fxfee'] . $request['fxdffee'] . $request['fxbody'] . $request['fxname'] . $request['fxaddress'] . $shkey;
        $mysign = md5($pp);

        $sign = $request['fxsign'];
        if (strtoupper($mysign) != strtoupper($sign)) {
            exit('sign fail');
        }

        //修改状态
        $data = array(
            'ddh' => $request['fxddh'],
            'money' => $request['fxfee'],
            'outddh' => $request['fxorder'],
            'msg' => $request['fxcode'],
            'status' => $request['fxstatus']
        );
        $result = $this->changestatus($data);

        if ($result[0] == 1)
            exit('success');
        exit($result[1]);
    }

    /**
     * 代付查询
      $request=array(
      'ddh'=>$id, //订单号
      'peizhi'=>$params, //账户配置信息
      'paybuffer'=>$paybuffer, //支付订单信息
      );
     */
    public function repayselect($request) {
        $wg = $this->wg;
        $arr = array(
            array(
                'fxddh' => $request['ddh'])
        );

        $shid = $request['peizhi']['qzf_id'];
        $shkey = $request['peizhi']['qzf_key'];
        $data = array(
            "fxid" => $shid, //商户号
            "fxaction" => "repayquery", //查询动作
            "fxbody" => json_encode($arr), //订单信息域 json字符串数据
        );
        $data["fxsign"] = md5($data["fxid"] . $data["fxaction"] . $data["fxbody"] . $shkey); //加密
        $backr = CURL($wg, $data);
        $r = json_decode($backr, true); //json转数组

        if (empty($r))
            return[0,
                '查询信息失败。' . $backr]; //如果转换错误，原样输出返回

        //验证签名
        if (strtoupper($r["fxsign"]) != strtoupper(md5($r["fxstatus"] . $r["fxid"] . $r["fxbody"] . $shkey))) {
            return[0,
                '签名错误。'];
        }

        //验证返回信息
        if ($r["fxstatus"] == 1) {
            $fxbody = json_decode($r["fxbody"], true); //json转数组

            $iFxbody = $fxbody[0];
            //修改状态
            if ($iFxbody['fxstatus'] !== '0') {
                $data = array(
                    'ddh' => $iFxbody['fxddh'],
                    'money' => $iFxbody['fxfee'],
                    'outddh' => $iFxbody['fxddh'],
                    'msg' => $iFxbody['fxcode'],
                    'status' => $iFxbody['fxstatus'] == '1' ? 1 : 0
                );
                $result = $this->changestatus($data);
                if($result[0]==1){
                    return [1,'查询成功，返回信息：'.$result[1]];
                }
                return $result;
            }

            return [1,
                '查询代付信息:' . $r['fxmsg'] . '-' . $iFxbody['fxcode']]; //输出错误信息
        } else {
            return [0,
                '查询代付信息失败。' . $r['fxmsg']]; //输出错误信息
        }
    }

}
