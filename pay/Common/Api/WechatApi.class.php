<?php

// +----------------------------------------------------------------------
// | easy pay [ pay to easy ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2017 All rights reserved.
// +----------------------------------------------------------------------
// | Author: fengxing <QQ:51125330>
// +----------------------------------------------------------------------

/**
 * 微信接口
 */

namespace Common\Api;

class WechatApi extends BaseApi {

    public function pay($request) {
        $types = 'MWEB';
        switch ($request['fxpay']) {
            case 'wxwap':
                $types = 'MWEB';
                break;
            case 'wxgzh':
                $types = 'JSAPI';
                break;
            case 'wxsm':
                $types = 'NATIVE';
                break;
        }

        if ($types == "JSAPI" && empty($request['isjsapi'])) {
            return [1,
                $request['apihttp'] . "/Pay/jsapi?style=wechat&id=" . $request['id'] . '&ddh=' . $request['fxddh'] . '&t=' . time()];
        }

        include_once COMMON_PATH . "/Tool/wechat/lib/WxPay.Api.php";
        if ($types == 'JSAPI') {
            include_once COMMON_PATH . "/Tool/wechat/WxPay.JsApiPay.php";
            $tools = new \JsApiPay();
            $openId = $tools->GetOpenid($request['peizhi']['wechat_appid'], $request['peizhi']['wechat_appsecret']);
        } else {

        }
        include_once COMMON_PATH . "/Tool/wechat/WxPay.NativePay.php";

        \WxPayConfig::setAPPID($request['peizhi']['wechat_appid']);
        \WxPayConfig::setMCHID($request['peizhi']['wechat_mchid']);
        \WxPayConfig::setKEY($request['peizhi']['wechat_key']);
        \WxPayConfig::setAPPSECRET($request['peizhi']['wechat_appsecret']);

        $input = new \WxPayUnifiedOrder();
        $input->SetBody((string) $request['fxdesc']);
        $input->SetAttach((string) $request['fxattch']);
        $input->SetOut_trade_no((string) $request['fxddh']);
        $input->SetTotal_fee($request['fxfee'] * 100);
        $input->SetNotify_url($request['fxnotifyurl']);
        $input->SetTrade_type((string) $types);
        if ($types == 'MWEB')
            $input->SetSpbill_create_ip((string) $request['fxip']);
        else {
            $input->SetTime_start((string) date("YmdHis"));
            $input->SetTime_expire((string) (date("YmdHis", time() + 600)));
            $input->SetGoods_tag("");

            if ($types == 'NATIVE') {
                $input->SetSpbill_create_ip((string) get_client_ip(0, true));
                $input->SetProduct_id((string) $request['fxddh']);
            }

            if ($types == 'JSAPI') {
                $input->SetOpenid($openId);
            }
        }

        $notify = new \NativePay();
        $result = $notify->GetPayUrl($input);

        if ($types == 'JSAPI') {
            $jsApiParameters = $tools->GetJsApiParameters($result);
            $editAddress = $tools->GetEditAddressParameters();
            return array(
                1,
                array(
                    'jsApiParameters' => $jsApiParameters,
                    'backurl' => $request['fxbackurl'],
                    'editAddress' => $editAddress));
        }

        if ($result["return_msg"] != 'OK') {
            return array(
                0,
                $result["return_msg"]);
        }
        if ($types == "NATIVE")
            $result = getQrcode($result["code_url"], 150, 150);
        else
            $result = $request['apihttp'] . '/Pay/go?u=' . urlencode($result["mweb_url"] . '&redirect_url=' . urlencode($request['fxbackurl']));

        return array(
            1,
            $result);
    }

    /**
     * 异步回调微信
     */
    public function notify($request) {
        include_once COMMON_PATH . "/Tool/wechat/lib/WxPay.Api.php";
        include_once COMMON_PATH . "/Tool/wechat/lib/WxPay.Notify.php";
        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
        if (empty($xml))
            $xml = file_get_contents('php://input');
        libxml_disable_entity_loader(true);
        $values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        $ddh = $values['out_trade_no'];

        if ($request['returnss'] != 1) {
            //异步记录 便于补单
            $this->notifySave(['ddh' => $ddh,
                'content' => $xml,
                'function' => 'wechat',
                'sendstyle' => 'xml']);
        } else {
            global $returnss;
            $returnss['status'] = 1;
        }

        //根据订单号查找对应支付账户
        $jkpz = $this->getKeyByDdh($ddh);
        if ($jkpz[0]!=1) {
            return $jkpz;
        }
        $jkpz=$jkpz[1];

        \WxPayConfig::setAPPID($jkpz['wechat_appid']);
        \WxPayConfig::setMCHID($jkpz['wechat_mchid']);
        \WxPayConfig::setKEY($jkpz['wechat_key']);
        \WxPayConfig::setAPPSECRET($jkpz['wechat_appsecret']);

        $result = \WxPayResults::Init($xml);
        $notify = new \WxPayNotify();
        $notify->Handle(false);

        if ($request['returnss'] == 1) {
            if (!empty($returnss['error']))
                return [0,
                    $returnss['error']];
            if (strstr($returnss['xml'], 'SUCCESS'))
                return [1,
                    'success'];
            else
                return [0,
                    'fail'];
        }

        exit();
    }

    /**
     * 公众号类H5支付
     */
    public function jsapi($request) {
        $style = $request['style'];

        $id = $request['id'];
        $ddh = $request['ddh'];
        //判断订单号
        $ddBuffer = SM('Dingdan')->findData('*', 'ordernum="' . $ddh . '"');
        if (!$ddBuffer) {
            return [0,
                '订单号不存在，请重试。'];
        }

        //获取支付账号
        $pzBuffer = SL('Api')->getJkByZj($id);
        if (!$pzBuffer) {
            return [0,
                '数据id错误，请重试。'];
        }
        if (!$pzBuffer['params']) {
            return [0,
                '数据id错误，请重试。。'];
        }
        if (!$pzBuffer['jkstyle']) {
            return [0,
                '数据id错误，请重试。。。'];
        }
        switch ($style) {
            case 'wechat':
                return $this->jsapi_wechat($pzBuffer, $ddBuffer);
                break;
        }
    }

    //微信公众号
    protected function jsapi_wechat($pzBuffer, $ddBuffer) {
        //获取支付网关
        if(!empty($pzBuffer['httpid'])){
            $httpBuffer=SM('Http')->findData('*','id="'.$pzBuffer['httpid'].'"');
            $wg = $httpBuffer['http'];
        }else{
            $httpBuffer = SL('Http')->getApiHttp($ddBuffer['userid']);
            $wg = '';
            if (is_array($httpBuffer[1])) {
                $apihttp = stringChange('arrayKey', $httpBuffer[1], 'jkstyle');
                $wg = $apihttp[$ddBuffer['jkstyle']]['thishttp'];
            } else {
                $wg = $httpBuffer[1];
            }
        }

        $fj = unserialize($ddBuffer['fj']);
        $jkdata = array(
            'id' => $pzBuffer['zjid'],
            'peizhi' => unserialize($pzBuffer['params']),
            'apihttp' => $wg,
            'style' => $pzBuffer['style'],
            'fxddh' => $ddBuffer['ordernum'],
            'fxdesc' => $fj['fxdesc'],
            'fxfee' => $ddBuffer['totalmoney'],
            'fxattch' => $fj['fxattch'],
            'fxnotifyurl' => $wg . "/Pay/notify/" . $pzBuffer['style'], //本站回调
            'fxbackurl' => $wg . "/Pay/backurl/" . $pzBuffer['style'] . '/1/ddh/' . $ddBuffer['ordernum'], //本站回调 $fxbackurl, //商户同步回调 商户需要做订单查询以支持实时订单状态
            'fxpay' => $ddBuffer['jkstyle'],
            'fxip' => $fj['fxip'],
            'isjsapi' => 1
        );

        //调用接口返回
        return $this->pay($jkdata);
    }

}
