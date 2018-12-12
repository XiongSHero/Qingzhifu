<?php

/**
 * 客户端请求本接口 代付异步回调
 * author: fengxing
 * Date: 2018/7/14
 */
include('./config.php');
$request = $_REQUEST;
$fxid = $request['fxid']; //商户编号
$fxddh = $request['fxddh']; //商户订单号
$fxorder = $request['fxorder']; //平台订单号
$fxfee = $request['fxfee']; //交易金额
$fxstatus = $request['fxstatus']; //订单状态
$fxsign = $request['fxsign']; //md5验证签名串

$pp = $request['fxid'] . $request['fxddh'] . $request['fxorder'] . $request['fxstatus'] . $request['fxfee'] . $request['fxdffee'] . $request['fxbody'] . $request['fxname'] . $request['fxaddress'] . $fxkey;
$mysign = md5($pp);
//记录回调数据到文件，以便排错
if ($fxloaderror == 1)
    file_put_contents('./daifu.txt', '异步：' . serialize($_REQUEST) . "\r\n", FILE_APPEND);

if ($fxsign == $mysign) {
    switch($fxstatus){
        case '0':
            //代付失败 更改代付状态 完善代付逻辑
            break;
        case '1':
            //代付成功 更改代付状态 完善代付逻辑
            break;
        case '2':
            //代付中
            break;
    }
    //收到通知
    echo 'success';
} else {
    echo 'sign error';
}
?>