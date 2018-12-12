<%@ include file="config.jsp"%><%@page import="net.sf.json.JSONObject"%><%@ page language="java" contentType="text/html; charset=utf-8" pageEncoding="utf-8"%><%@ page import="java.util.*" %><%@ page import="util.*"%><%
    String fxnotifyurl = "http://localhost:8084/qzf/notifyUrl.htm";
    String fxbackurl = "http://localhost:8084/qzf/backUrl.htm";
    String fxattch = "test";
    String fxdesc = request.getParameter("fxdesc");
    String fxfee = request.getParameter("fxfee");
    String fxpay = request.getParameter("fxpay");
    String fxddh = UtilDate.getdtlongNum(); //订单号
    String fxip = request.getRemoteAddr();

    //订单签名
    String fxsign = MD5Tool.encoding(fxid + fxddh + fxfee + fxnotifyurl + fxkey);
    fxsign = fxsign.toLowerCase();

    Map<String, String> reqMap = new HashMap<String, String>();
    reqMap.put("fxid", fxid);
    reqMap.put("fxddh", fxddh);
    reqMap.put("fxfee", fxfee);
    reqMap.put("fxpay", fxpay);
    reqMap.put("fxnotifyurl", fxnotifyurl);
    reqMap.put("fxbackurl", fxbackurl);
    reqMap.put("fxattch", fxattch);
    reqMap.put("fxdesc", fxdesc);
    reqMap.put("fxip", fxip);
    reqMap.put("fxsign", fxsign);

    // 支付请求返回结果
    String result = null;
    result = new HttpClientUtil().doPost(wg, reqMap);
    out.print(result);
%>