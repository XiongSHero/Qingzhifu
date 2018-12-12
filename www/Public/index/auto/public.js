/**
 * Created by Administrator on 2016/12/15.
 */


$(function () {
    var bannerSlider = new Slider($('#banner_tabs'), {
        time: 5000,
        delay: 400,
        event: 'hover',
        auto: true,
        mode: 'fade',
        controller: $('#bannerCtrl'),
        activeControllerCls: 'active'
    });
    $('#banner_tabs .flex-prev').click(function () {
        bannerSlider.prev()
    });
    $('#banner_tabs .flex-next').click(function () {
        bannerSlider.next()
    });


    $(window).scroll(function () {
        if ($(window).scrollTop() > 100) {
            $('.gbf-aside-right .h-top').fadeIn(1000);
        }
        else {
            $('.gbf-aside-right .h-top').fadeOut(1000);
        }
    });


    $('.gbf-aside-right .h-top').click(function () {
        $('body,html').animate({scrollTop: 0}, 1000);
        return false;
    });

});

/*切换*/
;(function () {
    window.onload = function () {
        var slider = {
            slider_view: $('.box-slider'),
            slider_z: $('.box-slider-z'),
            slider_length: 0,
            idx_slider_ul: $('.idx-slider-ul'),
            slider_index: 1,
            slider_data: [
                {
                    id: 1,
                    title: "多级商户",
                    slider_data: {
                        h1: "多级商户解决方案",
                        p: "全方位满足多级商户的应用场景。全面支持平台、代理、分销等多种商业形态，让你轻松管理全平台的交易和账务，高效实现多级商户的账务清分。",
                        a: ""
                    }
                },
                {
                    id: 2,
                    title: "智能港口",
                    slider_data: {
                        h1: "智能港口电商平台解决方案",
                        p: "拥有10多年港口、航运产业信息化实施与咨询服务的专家团队。在充分理解国家“互联网+”战略和港口综合物流服务体系的基础上，依托先进的技术手段、丰富的互联网运营经验和港航信息化实施经验、以及专业的港航综合物流服务知识体系的深入理解与认知，多年来一直致力于“互联网+港口”、“互联网+金融”等创新模式的研究，公司紧紧围绕以港口为圆点，通过整合上下游资源，为港航企业提供创新的服务体系支撑和完整的产品链解决方案。",
                        a: ""
                    }
                },
                {
                    id: 3,
                    title: "物流行业",
                    slider_data: {
                        h1: "物流行业解决方案",
                        p: "国内首家专注于港航物流电商产品和行业解决方案提供商的科技公司，紧跟国家“一带一路”战略和“互联网+”行动指导方针，致力于“互联网+大物流”创新模式的研究和实施，为物流企业提供创新的服务体系支撑和多元化的产品支付解决方案。",
                        a: ""
                    }
                },
                {
                    id: 4,
                    title: "零售电商",
                    slider_data: {
                        h1: "零售电商解决方案",
                        p: "支持多场景下聚合支付，为电商轻松建立、管理会员系统。为电商提供PC、App、微信公众号、支付宝服务窗全场景下聚合支付服务，覆盖银联、支付宝、微信等主流支付渠道、分期付款渠道、外卡支付渠道等；帮助电商轻松建立用户系统、用户余额账户，支持用户在电商应用内余额充值、充值赠送、消费、转账等。对特定用户或用户群体发放不同类型的优惠券，实现精准营销，并跟踪分析优惠券使用效果。",
                        a: ""
                    }
                },
                {
                    id: 5,
                    title: "旅游行业",
                    slider_data: {
                        h1: "旅游行业解决方案",
                        p: "同时管理用户及产品资源，满足复杂的业务需求。给旅游行业提供管理、运营、财务一系列完整的平台系统解决方案，给客户便捷，快速的消费体验。",
                        a: ""
                    }
                },
                {
                    id: 6,
                    title: "教育培训",
                    slider_data: {
                        h1: "教育培训解决方案",
                        p: "灵活满足不同业务模式下学校、幼儿园、培训机构、在线教育行业的支付需求。提供账单管理、在线缴费、奖学金发放等教育培训系统支付管理平台。避免假币风险、线下支付繁琐，对账时效等问题， 足不出户、轻松缴费。提升教育培训机构信息化管理水平",
                        a: ""
                    }
                },
                {
                    id: 7,
                    title: "停车行业",
                    slider_data: {
                        h1: "智能停车解决方案",
                        p: "通过微信、支付宝、快捷、信用免密等多种支付能力，协助伙伴构建智能停车系统。降低收单成本，提升场地使用效率。享受快捷停车服务，快速查询周边优惠；绑定代扣，享受直接抬杆离场体验。",
                        a: ""
                    }
                },
                {
                    id: 8,
                    title: "游戏充值",
                    slider_data: {
                        h1: "游戏充值解决方案",
                        p: "为游戏提供多种便捷、快速的在线支付功能。提供多种优惠卷、充值返现等活动服务，大量、稳定的移动支付系统解决方案。",
                        a: ""
                    }
                }
            ]
        };

        slider.fn = {
            init: function () {

                function add_slider_ul() {
                    var a_html = '';
                    for (var i = 0; i < slider.slider_data.length; i++) {
                        a_html += '<li><a href="">' + slider.slider_data[i].title + '</a></li>'
                    }
                    slider.idx_slider_ul.html(a_html)
                }

                add_slider_ul();

                function box_slider_ul() {
                    var a_html = '';
                    for (var i = 0; i < slider.slider_data.length; i++) {
                        a_html += '<li class="box-slider-d"> <div class="li-view">'
                            + '<h1>' + slider.slider_data[i].slider_data.h1 + '</h1>'
                            + '<p>' + slider.slider_data[i].slider_data.p + '</p>'
                            + '<a href="'+slider.slider_data[i].slider_data.a+'">了解更多</a>'
                            + '</div></li>'
                    }
                    slider.slider_z.html(a_html)
                    slider.slider_z.find('li:eq(0)').addClass('current');
                }

                box_slider_ul();
            },
            tab: function (i) {
                slider.slider_z.find('li:eq(' + i + ')').addClass('current').siblings().removeClass('current')
            },
            time: function () {

                var t;

                slider.slider_length = $('.box-slider-z').find('li').length;

                function tab() {
                    t = setInterval(function () {
                        slider.fn.tab(slider.slider_index)
                        slider.slider_index >= slider.slider_length - 1 ? slider.slider_index = 0 : slider.slider_index = slider.slider_index + 1;
                    }, 4500)
                }

                tab();

                slider.idx_slider_ul.find('li').on('mouseover', function () {
                    var idx = $(this).index();
                    slider.slider_index = idx;
                    slider.fn.tab(slider.slider_index)
                    clearInterval(t);
                });

                slider.idx_slider_ul.find('li').on('mouseout', function () {
                    clearInterval(t);
                    tab()
                });
            }
        };
        slider.fn.init();
        slider.fn.time();
    };
}());