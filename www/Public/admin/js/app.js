$(document).ready(function () {
    $('.form-ajax').submit(function (e) {
        e.preventDefault();
        var index = layer.load();
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            success: function (result) {
                layer.close(index);
                if (result.status == '0') {
                    layer.alert(result['data']);
                    if($('[name=yzm]').length>0) $('[name=yzm]').val('');
                    if($('.verifyImg').length>0) $('.verifyImg').click();
                    return;
                }

                if (result.status == '1') {
                    layer.msg(result['data'][0], {'time': 1000 * parseInt(result['data'][2])}, function () {
                        if (typeof (result['data'][1]) != 'undefined' && result['data'][1]) {
                            window.location.href = result['data'][1];
                        }
                    });
                    return;
                }

                layer.alert(result);
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                layer.close(index);
                layer.alert('数据返回错误。' + XMLHttpRequest + errorThrown);
            }
        });
    });

    $('.selectAllCheckbox').click(function () {
        if ($(this).prop('checked')) {
            $('.checkbox').prop('checked', true);
        } else {
            $('.checkbox').prop('checked', false);
        }
    });

    if ($('.zclipCopy').length > 0) {
        $('.zclipCopy').zclip({
            path: '/static/common/ZeroClipboard.swf',
            copy: function () {
                return $(this).prop('data');
            },
            afterCopy: function () {
                alert('复制成功');
            }
        });
    }

    $('.ajax-delete').click(function () {
        var url = $(this).attr('data-url');
        var id = $(this).attr('data-id');
        var style = $(this).attr('data-show');
        layer.confirm('是否要执行此操作？', function (index) {
            layer.close(index);
            var index = layer.load();
            $.get(url, function (data) {
                layer.close(index);
                if (data.status == '0') {
                    layer.alert(data['data']);
                } else {
                    if (style == '1') {
                        layer.msg(data['data'][0]);
                    } else
                        $('#tr' + id).fadeOut();
                }
            }, 'json');
        });
    });
    $('.addbtn').click(function () {
        location.href = $(this).attr('data-url');
    });
    $('.flushbtn').click(function () {
        window.location.reload();
    });
    $('.delbtn').click(function () {
        var url = $(this).attr('data-url');
        var id = $.commonjs.getCheckedID('.ajax-form');
        if (id == '') {
            layer.alert('请选择要删除的数据。');
            return false;
        }
        layer.confirm('是否要执行此操作？', function (index) {
            $.post(url, {'id': id, 'times': Math.random()}, function (data) {
                layer.close(index);
                if (data.status == '0') {
                    layer.alert(data['data']);
                } else {
                    var listn = id.split(',');
                    for (var l in listn) {
                        $('#tr' + listn[l]).fadeOut();
                    }
                }
            }, 'json');
        });
    });
    //清除部分数据
    $('.delallbtn').click(function () {
        var url = $(this).attr('data-url');
        layer.confirm('是否要执行此操作？', function (index) {
            layer.close(index);
            index = layer.load();
            $.post(url, {'times': Math.random()}, function (data) {
                layer.close(index);
                if (data.status == '0') {
                    layer.alert(data['data']);
                } else {
                    location.reload();
                }
            }, 'json');
        });
    });
    //批量补单数据
    $('.changeallbtn').click(function () {
        var url = $(this).attr('data-url');
        layer.confirm('是否要执行批量补单？', function (index) {
            layer.close(index);
            index = layer.msg('正在批量补单中.请稍候...');
            $.post(url, {'times': Math.random()}, function (data) {
                layer.close(index);
                if (data.status == '0') {
                    layer.alert(data['data']);
                } else {
                    location.reload();
                }
            }, 'json');
        });
    });
    //批量补单数据
    $('.changecurbtn').click(function () {
        var url = $(this).attr('data-url');
        var id = $.commonjs.getCheckedID('.ajax-form');
        if (id == '') {
            layer.alert('请选择要补单的数据。');
            return false;
        }
        layer.confirm('是否要执行补单？', function (index) {
            layer.close(index);
            index = layer.msg('正在补单中.请稍候...');
            $.post(url, {'id': id, 'times': Math.random()}, function (data) {
                layer.close(index);
                if (data.status == '0') {
                    layer.alert(data['data']);
                } else {
                    layer.alert(data['data'][0], function () {
                        location.reload();
                    });
                }
            }, 'json');
        });
    });

    $('.checkbtn').click(function () {
        var url = $(this).attr('data-url');
        var id = $(this).attr('data-id');
        if (typeof (id) == 'undefined' || id == '')
            id = $.commonjs.getCheckedID('.ajax-form');
        if (id == '') {
            layer.alert('请选择要检测的数据。');
            return false;
        }
        layer.confirm('是否要执行此操作？', function (index) {
            layer.close(index);
            var index = layer.load();
            $.post(url, {'id': id, 'times': Math.random()}, function (data) {
                layer.close(index);
                if (data.status == '0') {
                    layer.alert(data['data']);
                } else {
                    for (var i in data['data'][0]) {
                        $('#tr' + data['data'][0][i]['id']).find('.checkresult').html(data['data'][0][i]['status']);
                    }
                }
            }, 'json');
        });
    });
    $('.jumpbutton').click(function () {
        var url = $(this).attr('data-url');
        location.href = url;
    });

    $.commonjs = {
        getCheckedID: function (obj) {
            var str = '';
            $(obj).find('.thisid').each(function (i) {
                if ($(this).attr('checked') == 'checked')
                    str += ',' + $(this).val();
            });
            return str.substr(1);
        }
    }
    // 菜单切换
    $('.navbar-minimalize').click(function () {
        $("body").toggleClass("mini-navbar");
        SmoothlyMenu();
    });
    $('#side-menu dt').click(function (e) {
        if ($(this).parents('.hidemenu').length > 0) {
            NavToggle();
            Cookie.Set('menu', '');
            return false;
        }

        if ($('body.mini-navbar').length > 0) {
            NavToggle();
            $('#side-menu dd').css({'display': 'none'});
            $(this).parents('dl').last().find('dd').css({'display': 'block'});
            return false;
        }

        if ($(this).parents('dl').last().find('dd').css('display') == 'none') {
            addCookieBar($(this).parents('dl').last().find('dt').text());
            $(this).parents('dl').last().find('dd').css({'display': 'block'});
        } else {
            delCookieBar($(this).parents('dl').last().find('dt').text());
            $(this).parents('dl').last().find('dd').css({'display': 'none'});
        }
        e.stopPropagation();
    });
    //侧边栏滚动
    $(window).scroll(function () {
        if ($(window).scrollTop() > 0 && !$('body').hasClass('fixed-nav')) {
            $('#right-sidebar').addClass('sidebar-top');
        } else {
            $('#right-sidebar').removeClass('sidebar-top');
        }

        var top = $('#top-nav').height();
        if ($(window).scrollTop() > 50) {
            $('.navbar-static-side').css({'margin-top': '-50px'});
            $('.navbar-static-side').height($(window).height());
        } else {
            $('.navbar-static-side').css({'margin-top': (0 - $(window).scrollTop()) + 'px'});
            $('.navbar-static-side').height($(window).height() - top + $(window).scrollTop());
        }
    });
    sizediv();
    $('.nav-close').click(NavToggle);

    //滑动门切换
    $('.nav-tabs li').live('click', function () {
        $('.nav-tabs li').removeClass('active');
        $('.tab-content > div').removeClass('active');
        $(this).addClass('active');
        $($(this).find('a').attr('href')).addClass('active');
        return false;
    });

    //订单提醒
    $('.i-checks').live('click', function (e) {
        if ($(this).find('input').attr('checked') == 'checked') {
            $(this).find('.icheckbox_square-green').removeClass('checked');
            $(this).find('input').attr('checked', false);
        } else {
            $(this).find('.icheckbox_square-green').addClass('checked');
            $(this).find('input').attr('checked', 'checked');
        }
    });
});

$(window).bind("load resize", function () {
    if ($(this).width() < 769) {
        $("body").addClass("mini-navbar");
        SmoothlyMenu();
    } else {
        $('.topfix').css({'display': 'inline-block'});

        var list = Cookie.Get('menu');
        showBar(list);
    }
});

//显示菜单
function showBar(list) {
    $('#side-menu').find('dd').css({'display': 'none'});
    var list = Cookie.Get('menu');
    if (typeof (list) == 'undefined' || list == '' || list == null)
        return;
    $('#side-menu dt').each(function () {
        if (list.indexOf(',' + $(this).text()) != -1) {
            $(this).click();
        }
    });
}
//添加cookie菜单
function addCookieBar(bar) {
    var list = Cookie.Get('menu');
    if (typeof (list) == 'undefined' || list == '' || list == null)
        list = '';
    if (list.indexOf(bar) == -1) {
        list += ',' + bar;
    }
    Cookie.Set('menu', list);
}
//去掉cookie菜单
function delCookieBar(bar) {
    var list = Cookie.Get('menu');
    if (typeof (list) == 'undefined' || list == '' || list == null)
        list = '';
    list = list.replace(',' + bar, '');
    Cookie.Set('menu', list);
}

function SmoothlyMenu() {
    if (!$('body').hasClass('mini-navbar')) {
        $('#side-menu').hide();
        $('.nav-label').css({'font-size': '14px'});
        $('#side-menu').find('dd').css({'display': 'none'});
        setTimeout(
                function () {
                    $('#side-menu').fadeIn(500);
                }, 100);
    } else if ($('body').hasClass('fixed-sidebar')) {
        $('#side-menu').hide();
        $('.nav-label').css({'font-size': '0px'});
        setTimeout(
                function () {
                    $('#side-menu').fadeIn(500);
                }, 300);
    } else {
        $('#side-menu').find('dd').css({'display': 'none'});
        $('.navbar-default').css({'display': 'block'});
        $('.nav-label').css({'font-size': '0px'});
        $('#side-menu').removeAttr('style');
    }
    sizediv();
}
function sizediv() {
    $('.navbar-static-side').css({'overflow-y': 'auto'});

    var top = $('#top-nav').height();
    if ($(window).scrollTop() > 50) {
        $('.navbar-static-side').css({'margin-top': '-50px'});
        $('.navbar-static-side').height($(window).height());
    } else {
        $('.navbar-static-side').css({'margin-top': (0 - $(window).scrollTop()) + 'px'});
        $('.navbar-static-side').height($(window).height() - top + $(window).scrollTop());
    }

    if ($(this).width() < 769) {
        $('.topfix').css({'display': 'none'});
    } else {
        $('.topfix').css({'display': 'inline-block'});
    }
}