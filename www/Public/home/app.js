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
                if (result.status == '0' || result.status == '1') {
                    if (typeof (result['data']) == 'string') {
                        layer.alert(result['data']);
                    } else {
                        layer.alert(result['data'][0], {'time': 1000 * parseInt(result['data'][2])}, function () {
                            if (typeof (result['data'][1]) != 'undefined' && result['data'][1]) {
                                window.location.href = result['data'][1];
                            }
                        });
                    }
                    return;
                }
                layer.alert(result);
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                layer.close(index);
                layer.alert('数据返回错误。' + errorThrown);
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
        var thiscookie = getcookiename();
        if (thiscookie != 1) {
            setcookiename(1);
        } else {
            setcookiename(2);
        }
        $("body").toggleClass("mini-navbar");
        SmoothlyMenu();
        $('.navbar-static-side').height($(window).height());
        $('.navbar-static-side').css({'overflow-y': 'auto'});
    });
    $('.navmain').click(function (e) {
        if ($('body.mini-navbar').length > 0) {
            NavToggle();
        } else if ($(window).width() < 769) {
            NavToggle();
        }
        e.stopPropagation();
    });
    $('#side-menu>li li a').click(function (e) {
        if ($(this).parents('.navmain').length > 0) {
            if ($(window).width() < 769) {
                NavToggle();
            }
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
    });

    $('.nav-close').click(NavToggle);
    $('.navbar-static-side').height($(window).height());
    $('.navbar-static-side').css({'overflow-y': 'auto'});

    //滑动门切换
    $('.nav-tabs li').live('click', function () {
        $('.nav-tabs li').removeClass('active');
        $('.tab-content > div').removeClass('active');
        $(this).addClass('active');
        $($(this).find('a').attr('href')).addClass('active');
        return false;
    });

});

$(window).bind("load resize", function () {
    $('.navbar-static-side').height($(window).height());
    $('.navbar-static-side').css({'overflow-y': 'auto'});
    if ($(this).width() < 769) {
        var thiscookie = getcookiename();
        if (thiscookie != 1) {
            setcookiename(1);
        } else {
            setcookiename(2);
        }
        $("body").addClass("mini-navbar");
        SmoothlyMenu();
    }
});

function getcookiename() {
    return $('.thiscookie').html();
}
function setcookiename(thiscookie) {
    return $('.thiscookie').html(thiscookie);
}

function SmoothlyMenu() {
    if (!$('body').hasClass('mini-navbar')) {
        $('#side-menu').hide();
        $('.nav-label').css({'font-size': '14px'});
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
        $('.navbar-default').css({'display': 'block'});
        $('.nav-label').css({'font-size': '0px'});
        $('#side-menu').removeAttr('style');
    }
}