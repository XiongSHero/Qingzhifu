<%@ page language="java" contentType="text/html; charset=utf-8"
    pageEncoding="utf-8"%>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<!DOCTYPE html>
<!--**
 * 客户端请求本接口 实现支付
 * author: fengxing
 * Date: 2018/3/8
 *-->
<html lang="zh-CN">
    <head>
        <meta http-equiv="content-type" content="text/html; charset=UTF-8">
        <meta charset="UTF-8">
        <title>支付接口 - 轻支付</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
        <meta http-equiv="X-UA-Compatible" content="IE=edge,Chrome=1">
        <meta name="renderer" content="webkit">
        <script type="text/javascript" src="./js/jquery-1.8.0.min.js"></script>
        <script type="text/javascript" src="./js/layer/layer.js"></script>
        <script type="text/javascript" src="./js/index.js"></script>
    </head>
    <body>
        <form id="Form1" name="Form1" method="post" action="post.htm">
            商品名称：<input type="text" class="mydesc" name="fxdesc" value='test'/>
            金额：<input type="text" class="myfee" name="fxfee" value='10'/>
            支付类型：<select type="text" class="mypay" name="fxpay">
                <option value="bank">网银支付</option>
            </select>
            <input type="button" class="mypay" value="支付"/>
        </form>
    </body>
</html>