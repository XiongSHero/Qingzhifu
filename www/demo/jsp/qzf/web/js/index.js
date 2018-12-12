$(document).ready(function () {
    var ddh = new Date().getTime(); //订单号

    //支付
    $('.mypay').click(function () {
        var desc = $('.mydesc').val();
        var fee = $('.myfee').val();
        var pay = $('.mypay').find('option:selected').val();

        var index = layer.load();
        layer.msg('正在提交请稍后...');

        var data = {'fxdesc': desc, 'fxfee': fee, 'fxpay': pay, 't': Math.random()};

        $.post('post.htm', data, function (result) {
            //验证返回信息
            if (typeof(result["status"])!="undefined" && result["status"] == 1) {
                location.href=result["payurl"]; //转入支付页面
            } else {
                layer.close(index);
                layer.alert(result["error"]); //输出错误信息
            }
        },'json');
    });

});