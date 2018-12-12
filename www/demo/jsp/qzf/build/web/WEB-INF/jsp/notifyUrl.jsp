<%@ include file="config.jsp"%><%@page import="com.jspsmart.upload.*"%><%@page import="net.sf.json.JSONObject"%><%@ page language="java" contentType="text/html; charset=utf-8" pageEncoding="utf-8"%><%@ page import="java.util.*" %><%@ page import="util.*"%><%    //String getid = request.getParameter("fxid");
    SmartUpload mySmartUpload = new SmartUpload();
    mySmartUpload.initialize(pageContext);    // 初始化上传操作
    mySmartUpload.upload();            // 上传准备
    String getid = mySmartUpload.getRequest().getParameter("fxid");
    String getddh = mySmartUpload.getRequest().getParameter("fxddh");
    String getorder = mySmartUpload.getRequest().getParameter("fxorder");
    String getattch = mySmartUpload.getRequest().getParameter("fxattch");
    String getdesc = mySmartUpload.getRequest().getParameter("fxdesc");
    String getfee = mySmartUpload.getRequest().getParameter("fxfee");
    String getstatus = mySmartUpload.getRequest().getParameter("fxstatus");
    String gettime = mySmartUpload.getRequest().getParameter("fxtime");
    String getsign = mySmartUpload.getRequest().getParameter("fxsign");

    String successstatus = "1";
    if (getstatus.equals(successstatus)!=true) {
        out.print("状态错误");
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
    out.print("success");
%>