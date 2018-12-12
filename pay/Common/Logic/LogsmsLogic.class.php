<?php

// +----------------------------------------------------------------------
// | easy pay [ pay to easy ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2017 All rights reserved.
// +----------------------------------------------------------------------
// | Author: fengxing <QQ:51125330>
// +----------------------------------------------------------------------

namespace Common\Logic;

class LogsmsLogic extends BaseLogic {

    protected $moduleName = '短信';

    /**
     * 发送
     */
    public function sendsms($request) {
        if (!$request['phone']) {
            return [0,
                '请传入手机号'];
        }
        global $publicData;
        if ($publicData['peizhi']['ifsms'] != 1) {
            return [0,
                '短信服务未开启'];
        }

        //当前用户发送N次
        $sendtimes=$publicData['peizhi']['smstimes'];
        if(empty($sendtimes)) $sendtimes=5;
        if ($request['userid']) {
            $buffer = SM('LogSms')->selectCount('userid="' . $request['userid'] . '" and addtime>' . strtotime(date('Y-m-d')), 'id');
            if ($buffer >= $sendtimes) {
                return [0,
                    '每个用户每天只能发送'.$sendtimes.'次请明天再试。'];
            }
            $title = '短信验证码';
        } else {
            $request['userid'] = 0;
            $title = '注册验证码';
        }

        $buffer = SM('LogSms')->selectCount('phone="' . $request['phone'] . '" and addtime>' . strtotime(date('Y-m-d')), 'id');
        if ($buffer >= $sendtimes) {
            return [0,
                '每个手机号每天只能发送'.$sendtimes.'次请明天再试。'];
        }

        //写入短信日志
        $buffer = SM('LogSms')->insertData(array(
            'phone' => $request['phone'],
            'content' => '您的短信验证码是：' . $request['code'],
            'code' => $request['code'],
            'userid' => $request['userid'],
            'style' => $title,
            'addtime' => time()
        ));

        if (!$buffer) {
            return [0,
                '发送失败，写入数据失败。'];
        }

        /****************发送数据逻辑开始****************** */
        $data = array(
            'account' => $publicData['peizhi']['smsaccount'],
            'key' => $publicData['peizhi']['smskey'],
            'phone' => $request['phone'],
            'temp' => $publicData['peizhi']['smstemplate'],
            'code' => $request['code'],
            'sign' => $publicData['peizhi']['smssign']
        );
        $fun=$publicData['peizhi']['smsstyle'];
        $result = $this->$fun($data);
        return $result;
    }

    /**
     * 验证
     */
    public function checksms($request) {
        //短信日志
        if (empty($request['code'])) {
            return [0,
                '请输入短信验证码！'];
        }

        $buffer = SM('LogSms')->selectData('*', 'phone="' . $request['phone'] . '" and ifcheck=0 and addtime>' . (time() - 300));
        if(!$buffer){
            return [0,
                '验证码超时或错误，请重新获取！'];
        }
        foreach ($buffer as $i => $iBuffer) {
            if ($iBuffer['code'] == $request['code']) {
                //改变验证的状态
                SM('LogSms')->updateData(array(
                    'ifcheck' => '1'), 'id=' . $iBuffer['id']);
                return [1,
                    '验证短信成功'];
            }
        }
        return [0,
            '验证短信失败'];
    }

    //云信
    private function yunxin($request) {
        $data = array(
            'ac' => 'send',
            'format' => 'json',
            'uid' => $request['account'],
            'pwd' => md5($request['key'].$request['account']),
            'encode' => 'UTF-8',
            'mobile' => $request['phone'],
            'content' => '{"code":"' . $request['code'] . '"}',
            'template' => $request['temp']
        );

        $result = file_get_contents('http://api.sms.cn/sms/?' . http_build_query($data));
        if (strstr($result, '100')) {
            return [1,
                '发送成功'];
        }
        $result = iconv('gbk', 'utf-8', $result);
        $resultn = json_decode($result, true);
        $msg = $resultn['message'];
        if ($msg)
            return [0,
                $msg];
        return [0,
            '短信发送失败。'];
    }

    //阿里短信
    private function ali($request) {
        $params = array(
            "PhoneNumbers" => $request['phone'],
            "SignName" => $request['sign'],
            "TemplateCode" => $request['temp'],
            "TemplateParam" => json_encode(["code" => $request['code']]),
        );

        import('Common.Tool.dysms.SignatureHelper');
        $helper = new \SignatureHelper();
        $content = $helper->request(
                $request['account'], $request['key'], "dysmsapi.aliyuncs.com", array_merge($params, array(
            "RegionId" => "cn-hangzhou",
            "Action" => "SendSms",
            "Version" => "2017-05-25",
                ))
                // fixme 选填: 启用https
                // ,true
        );

        $content=json_decode(json_encode($content), true);
        if ($content['Code'] == 'OK') {
            return [1,
                '发送成功'];
        }
        $msg = $content['Message'];
        if ($msg)
            return [0,
                $msg];
        return [0,
            '短信发送失败。'];
    }

}
