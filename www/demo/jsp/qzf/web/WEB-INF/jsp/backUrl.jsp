<%@ include file="config.jsp"%><%@page import="net.sf.json.JSONObject"%><%@ page language="java" contentType="text/html; charset=utf-8" pageEncoding="utf-8"%><%@ page import="java.util.*" %><%@ page import="util.*"%><%    //String getid = request.getParameter("fxid");
    String getid = request.getParameter("fxid");
    String getddh = request.getParameter("fxddh");
    String getorder = request.getParameter("fxorder");
    String getattch = request.getParameter("fxattch");
    String getdesc = request.getParameter("fxdesc");
    String getfee = request.getParameter("fxfee");
    String getstatus = request.getParameter("fxstatus");
    String gettime = request.getParameter("fxtime");
    String getsign = request.getParameter("fxsign");

    String successstatus = "1";
    if (getstatus.equals(successstatus)!=true) {
        out.print("支付失败");
        return;
    }

    //订单签名 【md5(订单状态+商务号+商户订单号+支付金额+商户秘钥)】
    String mysign = MD5Tool.encoding(getstatus + getid + getddh + getfee + fxkey);
    mysign = mysign.toLowerCase();

    if (mysign.equals(getsign)!=true) {
        out.print("签名错误");
        return;
    }

    //支付成功逻辑
    out.print("支付成功");
%>