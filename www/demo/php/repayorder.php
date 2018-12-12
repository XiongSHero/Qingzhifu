<?php
/**
 * 客户端请求本接口 实现代付查询
 * author: fengxing
 * Date: 2018/3/8
 */
include('./config.php');

$arr=array(
    array('fxddh'=>'15205043534364'),
    array('fxddh'=>time() . mt_rand(100, 999)),
    array('fxddh'=>time() . mt_rand(100, 999))
);

$data = array(
    "fxid" => $fxid, //商户号
    "fxaction" => "repayquery", //查询动作
    "fxbody" => json_encode($arr), //订单信息域 json字符串数据
);
$data["fxsign"] = md5($data["fxid"] . $data["fxaction"] . $data["fxbody"] . $fxkey); //加密
$r = getHttpContent($fxgetway, "POST", $data);
$backr = $r;
$r = json_decode($r, true); //json转数组

if(empty($r)) exit(print_r($backr)); //如果转换错误，原样输出返回

//验证签名
if($r["fxsign"]!=md5($r["fxstatus"].$r["fxid"].$r["fxbody"].$fxkey)){
    echo '签名错误';
    exit();
}

//验证返回信息
if ($r["fxstatus"] == 1) {
    $fxbody=json_decode($r["fxbody"], true); //json转数组
    //正确申请返回信息
    echo '代付查询成功提交<br/>';
    echo '商户号:'.$r["fxid"].'<br/>';
    echo '状态描述:'.$r["fxmsg"].'<br/>';
    echo '返回信息域<br/>';

    foreach($fxbody as $i=>$iFxbody){
        echo '订单号：'.$iFxbody['fxddh'].'，订单状态描述：'.$iFxbody['fxcode'].'<br/>';
    }

    exit();
} else {
    //echo $r['error'].print_r($backr); //输出详细信息
    echo $r['fxmsg']; //输出错误信息
    exit();
}
?>