<?php
/**
 * 客户端请求本接口 实现余额查询
 * author: fengxing
 * Date: 2018/3/8
 */
include('./config.php');
$data = array(
    "fxid" => $fxid, //商户号
    "fxaction" => 'money', //商户号
    "fxdate" => date('YmdHis'), //查询时间
);
$data["fxsign"] = md5($data["fxid"] . $data["fxdate"]. $data["fxaction"] . $fxkey); //加密
$r = getHttpContent($fxgetway, "POST", $data);
$backr = $r;
$r = json_decode($r, true); //json转数组

if(empty($r)) exit(print_r($backr)); //如果转换错误，原样输出返回

//验证签名
if($r["fxsign"]!=md5($r["fxstatus"].$r["fxid"].$r["fxmoney"])){
    echo '签名错误';
    exit();
}

//验证返回信息
if ($r["fxstatus"] == 1) {
    echo '商户'.$r["fxid"].'的余额为：'.$r["fxmoney"].'元';//输出余额信息
    exit();
} else {
    //echo $r['error'].print_r($backr); //输出详细信息
    echo '返回信息：'.$r['fxmsg']; //输出错误信息
    exit();
}
?>