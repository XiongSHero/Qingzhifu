<?php

// +----------------------------------------------------------------------
// | easy pay [ pay to easy ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2017 All rights reserved.
// +----------------------------------------------------------------------
// | Author: fengxing <QQ:51125330>
// +----------------------------------------------------------------------

/**
 * 支付宝接口
 */

namespace Common\Api;

class AlipayApi extends BaseApi {

    public function pay($request) {
        $wg = 'https://openapi.alipaydev.com/gateway.do'; //测试环境
        //$wg = "https://openapi.alipay.com/gateway.do"; //正式环境

        if (!$request['fxdesc'])
            $request['fxdesc'] = 'alipay sm';

        //秘钥
        $key = $request['peizhi']['alipay_private'];
        if (!strstr($key, '-----')) {
            $priKey = $key;
            $res = "-----BEGIN RSA PRIVATE KEY-----\n" .
                    wordwrap($priKey, 64, "\n", true) .
                    "\n-----END RSA PRIVATE KEY-----";
        } else {
            $res = $key;
        }

        if (!$res) {
            return [0,
                '您使用的私钥格式错误，请检查RSA私钥配置'];
        }

        switch ($request['fxpay']) {
            case 'zfbpc':
                //调用接口d
                $biz = array(
                    'out_trade_no' => $request['fxddh'],
                    'total_amount' => $request['fxfee'],
                    'subject' => $request['fxdesc'],
                    'timeout_express' => "5m",
                    "product_code" => 'FAST_INSTANT_TRADE_PAY',
                );
                $data = array(
                    'app_id' => $request['peizhi']['alipay_appid'],
                    'method' => 'alipay.trade.page.pay',
                    'format' => 'JSON',
                    'charset' => 'utf-8',
                    'sign_type' => $request['peizhi']['alipay_sign'],
                    'timestamp' => date('Y-m-d H:i:s'),
                    'version' => '1.0',
                    'return_url' => $request['fxbackurl'],
                    'notify_url' => $request['fxnotifyurl'],
                    'biz_content' => json_encode($biz, JSON_UNESCAPED_UNICODE),
                );

                ksort($data);
                $str = '';
                foreach ($data as $i => $iData) {
                    $str.=$i . '=' . $iData . '&';
                }
                $str = substr($str, 0, -1);

                if ("RSA2" == $request['peizhi']['alipay_sign']) {
                    openssl_sign($str, $sign, $res, OPENSSL_ALGO_SHA256);
                } else {
                    openssl_sign($str, $sign, $res);
                }

                $sign = base64_encode($sign);
                $data['sign'] = $sign;

                $requestUrl = $wg . "?";
                foreach ($data as $sysParamKey => $sysParamValue) {
                    $requestUrl .= "$sysParamKey=" . urlencode($sysParamValue) . "&";
                }
                $requestUrl = substr($requestUrl, 0, -1);

                return [1,
                    $this->changeUrl($requestUrl,$request['apihttp'])];
                break;

            case 'zfbwap':
                //调用接口d
                $biz = array(
                    'out_trade_no' => $request['fxddh'],
                    'total_amount' => $request['fxfee'],
                    'subject' => $request['fxdesc'],
                    'timeout_express' => "5m",
                    "seller_id" => '',
                    "quit_url" => $request['fxbackurl'],
                    "product_code" => 'QUICK_WAP_WAY',
                );
                $data = array(
                    'app_id' => $request['peizhi']['alipay_appid'],
                    'method' => 'alipay.trade.wap.pay',
                    'format' => 'JSON',
                    'charset' => 'utf-8',
                    'sign_type' => $request['peizhi']['alipay_sign'],
                    'timestamp' => date('Y-m-d H:i:s'),
                    'version' => '1.0',
                    'return_url' => $request['fxbackurl'],
                    'notify_url' => $request['fxnotifyurl'],
                    'biz_content' => json_encode($biz, JSON_UNESCAPED_UNICODE),
                );

                ksort($data);
                $str = '';
                foreach ($data as $i => $iData) {
                    $str.=$i . '=' . $iData . '&';
                }
                $str = substr($str, 0, -1);

                if ("RSA2" == $request['peizhi']['alipay_sign']) {
                    openssl_sign($str, $sign, $res, OPENSSL_ALGO_SHA256);
                } else {
                    openssl_sign($str, $sign, $res);
                }

                $sign = base64_encode($sign);
                $data['sign'] = $sign;

                $requestUrl = $wg . "?";
                foreach ($data as $sysParamKey => $sysParamValue) {
                    $requestUrl .= "$sysParamKey=" . urlencode($sysParamValue) . "&";
                }
                $requestUrl = substr($requestUrl, 0, -1);

                return [1,
                    $this->changeUrl($requestUrl,$request['apihttp'])];
                break;
            case 'zfbsm':
                $biz = array(
                    'out_trade_no' => $request['fxddh'],
                    'total_amount' => $request['fxfee'],
                    'subject' => $request['fxdesc'],
                    'timeout_express' => "5m"
                );
                $data = array(
                    'app_id' => $request['peizhi']['alipay_appid'],
                    'method' => 'alipay.trade.precreate',
                    'format' => 'JSON',
                    'charset' => 'utf-8',
                    'sign_type' => $request['peizhi']['alipay_sign'],
                    'timestamp' => date('Y-m-d H:i:s'),
                    'version' => '1.0',
                    'notify_url' => $request['fxnotifyurl'],
                    'biz_content' => json_encode($biz, JSON_UNESCAPED_UNICODE),
                );

                ksort($data);
                $str = '';
                foreach ($data as $i => $iData) {
                    $str.=$i . '=' . $iData . '&';
                }
                $str = substr($str, 0, -1);

                if ("RSA2" == $request['peizhi']['alipay_sign']) {
                    openssl_sign($str, $sign, $res, OPENSSL_ALGO_SHA256);
                } else {
                    openssl_sign($str, $sign, $res);
                }

                $sign = base64_encode($sign);
                $data['sign'] = $sign;

                $requestUrl = $wg . "?";
                foreach ($data as $sysParamKey => $sysParamValue) {
                    $requestUrl .= "$sysParamKey=" . urlencode($sysParamValue) . "&";
                }
                $requestUrl = substr($requestUrl, 0, -1);

                $result = curl($requestUrl); //exit(print_r($result));
                $r = json_decode($result, true);
                $r = $r['alipay_trade_precreate_response'];
                //根据状态值进行业务处理
                switch ($r['code']) {
                    case '10000'://"SUCCESS":
                        //echo "支付宝创建订单二维码成功:"."<br>---------------------------------------<br>";
                        return [1,
                            getQrcode($r['qr_code'], 150, 150)];
                        //print_r($response);
                        break;
                    case '10003'://"FAILED":
                        return [0,
                            "支付宝创建订单二维码失败!!!" . $r['sub_msg']];
                        //if(!empty($qrPayResult->getResponse())){
                        //print_r($qrPayResult->getResponse());
                        //}
                        break;
                    case '20000'://"UNKNOWN":
                        return [0,
                            "系统异常，状态未知!!!" . $r['sub_msg']];
                        //if(!empty($qrPayResult->getResponse())){
                        //print_r($qrPayResult->getResponse());
                        //}
                        break;
                    default:
                        return [0,
                            "不支持的返回状态，创建订单二维码返回异常!!!" . $r['sub_msg']];
                        break;
                }
                break;
        }
    }

    /**
     * 异步回调支付宝
     */
    public function notify($request) {
        $post = $_POST;
        foreach ($post as $i => $ipost) {
            $post[$i] = stripslashes($ipost);
        }
        $ddh = $request['out_trade_no'];

        if ($request['returnss'] != 1) {
            //异步记录 便于补单
            $this->notifySave(['ddh' => $ddh,
                'content' => serialize($_POST),
                'function' => 'alipay',
                'sendstyle' => 'post']);
        }

        //根据订单号查找对应支付账户
        $ddBuffer = SM('Dingdan')->findData('*', 'ordernum="' . $ddh . '"');
        if (!$ddBuffer) {
            return [0,
                '订单号不存在，请重试。'];
        }
        //获取支付账号
        $pzBuffer = SL('Api')->getJkByZj($ddBuffer['zjid']);
        $jkpz = unserialize($pzBuffer['params']);
        $shsigntype = $jkpz['alipay_sign'];
        $shpublic = $jkpz['alipay_public'];

        $sign = $post['sign'];
        unset($post['sign']);
        unset($post['sign_type']);
        ksort($post);
        $stringToBeSigned = "";
        $i = 0;
        foreach ($post as $k => $v) {
            if (!(!isset($v) || $v === null || trim($v) === "") && "@" != substr($v, 0, 1)) {
                if ($i == 0) {
                    $stringToBeSigned .= "$k" . "=" . "$v";
                } else {
                    $stringToBeSigned .= "&" . "$k" . "=" . "$v";
                }
                $i++;
            }
        }

        //公钥
        $key = $shpublic;
        if (!strstr($key, '-----')) {
            $pubKey = $key;
            $res = "-----BEGIN PUBLIC KEY-----\n" .
                    wordwrap($pubKey, 64, "\n", true) .
                    "\n-----END PUBLIC KEY-----";
        } else {
            $res = $key;
        }

        if (!$res) {
            return [0,
                '支付宝RSA公钥错误。请检查公钥文件格式是否正确'];
        }

        if ("RSA2" == $shsigntype) {
            $result = (bool) openssl_verify($stringToBeSigned, base64_decode($sign), $res, OPENSSL_ALGO_SHA256);
        } else {
            $result = (bool) openssl_verify($stringToBeSigned, base64_decode($sign), $res);
        }

        if ($result) {
            //商户订单号
            $out_trade_no = $request['out_trade_no'];
            //支付宝交易号
            $trade_no = $request['trade_no'];
            //交易状态
            $trade_status = $request['trade_status'];
            //订单金额
            $total_amount = $request['total_amount'];
            //描述
            $body = $request['body'];

            $newdata = array();
            $newdata['ddh'] = $out_trade_no;
            $newdata['qudao'] = $trade_no;
            $newdata['fee'] = $total_amount;
            $newdata['method'] = 'post';

            $result = '';
            if ($request['trade_status'] == 'TRADE_FINISHED') {
                $return = $this->changeDingdan($newdata);
                if ($return[0] === 0)
                    $result = 'fail';
                //判断该笔订单是否在商户网站中已经做过处理
                //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                //请务必判断请求时的total_amount与通知时获取的total_fee为一致的
                //如果有做过处理，不执行商户的业务程序
                //注意：
                //退款日期超过可退款期限后（如三个月可退款），支付宝系统发送该交易状态通知
            } else if ($request['trade_status'] == 'TRADE_SUCCESS') {
                $return = $this->changeDingdan($newdata);
                if ($return[0] === 0)
                    $result = 'fail';
                //判断该笔订单是否在商户网站中已经做过处理
                //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                //请务必判断请求时的total_amount与通知时获取的total_fee为一致的
                //如果有做过处理，不执行商户的业务程序
                //注意：
                //付款完成后，支付宝系统发送该交易状态通知
            }
            //——请根据您的业务逻辑来编写程序（以上代码仅作参考）——

            if ($result == '')
                $result = "success";  //请不要修改或删除
        } else {
            //验证失败
            $result = 'fail'; //请不要修改或删除
        }

        //补单
        if ($request['returnss'] == 1) {
            if ($result == 'success')
                return [1,
                    'success'];
            else
                return [0,
                    $result];
        }

        exit($result);
    }

}
