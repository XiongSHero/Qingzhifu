<?php

/**
 * 客户端请求本接口 实现代付提交
 * author: fengxing
 * Date: 2018/3/8
 */
include('./config.php');

$arr = array(
    array(
        'fxddh' => time() . mt_rand(100, 999),
        'fxdate' => date('YmdHis'),
        'fxfee' => '100',
        'fxbody' => '62220214784512003225',
        'fxname' => '张浩明',
        'fxaddress' => '工商银行',
        'fxzhihang' => '开发区支行',
        'fxsheng' => '浙江省',
        'fxshi' => '金华市'),
    array(
        'fxddh' => time() . mt_rand(100, 999),
        'fxdate' => date('YmdHis'),
        'fxfee' => '80',
        'fxbody' => '62125868442225500151',
        'fxname' => '张浩明',
        'fxaddress' => '交通银行',
        'fxzhihang' => '伏牛路支行',
        'fxsheng' => '河南省',
        'fxshi' => '郑州市')
);

$data = array(
    "fxid" => $fxid, //商户号
    "fxaction" => "repay", //查询动作
    "fxnotifyurl" => "http://" . $_SERVER['HTTP_HOST'] . "/repaynotify.php", //异步回调地址，外网能访问
    "fxbody" => json_encode($arr), //订单信息域 json字符串数据
);
$data["fxsign"] = md5($data["fxid"] . $data["fxaction"] . $data["fxbody"] . $fxkey); //加密
$r = getHttpContent($fxgetway, "POST", $data);
$backr = $r;
$r = json_decode($r, true); //json转数组

if (empty($r))
    exit(print_r($backr)); //如果转换错误，原样输出返回


//验证签名
if ($r["fxsign"] != md5($r["fxstatus"] . $r["fxid"] . $r["fxbody"] . $fxkey)) {
    echo '签名错误';
    exit();
}

//验证返回信息
if ($r["fxstatus"] == 1) {
    $fxbody = json_decode($r["fxbody"], true); //json转数组
    //正确申请返回信息
    echo '代付申请成功提交<br/>';
    echo '商户号:' . $r["fxid"] . '<br/>';
    echo '状态描述:' . $r["fxmsg"] . '<br/>';
    echo '返回信息域<br/>';

    foreach ($fxbody as $i => $iFxbody) {
        echo '订单号：' . $iFxbody['fxddh'] . '，订单状态描述：' . $iFxbody['fxcode'] . '<br/>';
    }

    exit();
} else {
    //echo $r['error'].print_r($backr); //输出详细信息
    echo $r['fxmsg']; //输出错误信息
    exit();
}
?>