//cookie
(function () {
    window.Cookie = (function () {
        return {
            Set: function (a, b, c) {
                var d = new Date();
                d.setTime(d.getTime() + c * 24 * 60 * 60 * 1000);
                document.cookie = a + "=" + encodeURIComponent(b) + ";expires=" + d.toGMTString() + ";path=/"
            },
            Get: function (a) {
                var c = document.cookie.split(a + '=');
                if (c.length > 1) {
                    var d = c[c.length - 1].split(';');
                    if (d.length > 1) {
                        return decodeURIComponent(d[0])
                    } else {
                        return decodeURIComponent(c[c.length - 1])
                    }
                } else {
                    return null
                }

                //var b = document.cookie.match(new RegExp("(^| )" + a + "=([^;]*)(;|$)"));
                //if(a=='paperstyle') alert(b.length);
                //if (b != null) {
                //    return decodeURIComponent(b[2])
                //} else {
                //    return null
                //}
            },
            Del: function (a) {
                var b = new Date();
                b.setTime(b.getTime() - 100000);
                var c = this.Get(a);
                if (c != null) {
                    document.cookie = a + "=;expires=" + b.toGMTString() + ";path=/"
                }
            },
            Has: function (name) {
                var ck = document.cookie.indexOf(name + "=");
                if (ck == -1) {
                    return false;
                }
                return true;
            }
        }
    })();
    
    $('.verifyImg').live('click', function () {
        //重载验证码
        var timenow = new Date().getTime();
        var src = $(this).attr('src') + '?time=' + timenow;
        $(this).attr('src', src);
        if($('.verifyCode').length>0) $(".verifyCode").val("");
        if($('.verifyCodeSms').length>0) $(".verifyCodeSms").val("");
    });
})();

function NavToggle() {
    $('.navbar-minimalize').trigger('click');
}
//判断浏览器是否支持html5本地存储
function localStorageSupport() {
    return (('localStorage' in window) && window['localStorage'] !== null)
}

var isupload = false;
function selfile(inputid) {
    if (isupload != false) {
        layer.msg("其他文件正在上传...请稍候....");
        return false;
    } else {
        $("#" + inputid).click();
    }
}
function uploadImg(hiddenid, divid, obj) {
    var filename = $(obj).val();
    if (filename != '' && filename != null) {
        isupload = true;
        var pic = $(obj)[0].files[0];
        var fd = new FormData();
        fd.append('imgFile', pic);
        $.ajax({
            url: "/Index/Index/upload/dir/info",
            type: "post",
            dataType: 'json',
            data: fd,
            cache: false,
            contentType: false,
            processData: false,
            success: function (data) {
                if (data && data.status == '1') {
                    layer.msg("上传成功");
                    var imgurl = data['data'][0];
                    $("#" + divid).attr('src', imgurl);
                    $("#" + hiddenid).val(imgurl);
                } else {
                    layer.msg("上传出错了..." + data.data);
                }
            },
            error: function () {
                layer.msg("上传出错了...");
            }
        });
        isupload = false;
    }
    isupload = false;
}

//短信验证
function sendsms(url, phone, style) {
    //弹出验证码输入
    var xy = '<div style="width:100%;line-height:40px;"><div class="form-group" style="margin-bottom: 15px;">' +
            '<label for="verifyCodeSms" style="float: left;width:25%;position:relative;display: inline-block;min-height: 1px; padding-right: 15px;padding-left: 15px;-webkit-box-sizing: border-box;-moz-box-sizing: border-box;box-sizing: border-box;" class="col-sm-3 col-xs-10 control-label">*验证码</label>' +
            '<div class="col-sm-4 col-xs-8" style="float: left;width:33%;position:relative;min-height: 1px;padding-right: 15px;padding-left: 15px;-webkit-box-sizing: border-box;-moz-box-sizing: border-box;box-sizing: border-box;">' +
            '    <input type="text" id="verifyCodeSms" class="verifyCodeSms form-control" style="display: block;width: 100%;height: 34px;padding: 6px 12px;font-size: 14px;line-height: 1.42857143;color: #555;background-color: #fff;background-image: none;border: 1px solid #ccc;border-radius: 4px;-webkit-box-sizing: border-box;-moz-box-sizing: border-box;box-sizing: border-box;" placeholder="验证码" name="verifyCode" />' +
            '</div>' +
            '<div class="col-sm-4 col-xs-4" style="float: left;width:33%;position:relative;min-height: 1px;padding-right: 15px;padding-left: 15px;-webkit-box-sizing: border-box;-moz-box-sizing: border-box;box-sizing: border-box;">' +
            '    <img height="33" width="95" class="verifyImg" src="/Index/Index/verify" border="0" title="点击刷新验证码" align="absmiddle" />' +
            '</div>' +
            '</div></div>';
    var title = '发送手机验证码';
    var index = layer.open({
        type: 1,
        title: title,
        content: xy,
        btn: ['发送', '取消'],
        yes: function (index) {
            var code = $('#verifyCodeSms').val();
            if (code.length != 4) {
                $('.verifyImg').click();
                layer.msg('请输入正确的验证码');
                return;
            }
            var indexn = layer.load();
            $.post(url, {'phone': phone, 'code': code, 'style': style, 't': Math.random()}, function (data) {
                layer.close(indexn);
                $('.verifyImg').click();
                if (data['status'] == '1') {
                    layer.closeAll();
                    settimes();
                    layer.msg(data['data'][0]);
                    return;
                }
                layer.msg(data['data']);
            });
        }
    });
}

function settimes(time) {
    var html='';
    if (typeof (time) == 'undefined') {
        time = 60;
        html='请在60秒后重发。';
    } else {
        html='请在' + time + '秒后重发。';
    }
    if (time <= 0) {
        html='发送验证码';
        return false;
    }
    if(typeof($('.sendsms').html())=='undefined') $('.sendsms').val('发送验证码');
    else $('.sendsms').html('发送验证码');
    
    setTimeout(function () {
        settimes(time - 1);
    }, 1000);
}