<?php

// +----------------------------------------------------------------------
// | easy pay [ pay to easy ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2017 All rights reserved.
// +----------------------------------------------------------------------
// | Author: fengxing <QQ:51125330>
// +----------------------------------------------------------------------
return array(
    //'配置项'=>'配置值'
    'LOAD_EXT_CONFIG' => 'local',
    //载入配置
    'FX_VERSION' => 'V3.3.0',
    //版本地址
    'FX_UPDATE_FILE_DATE' => '20180823',
    //js.css文件更新日期
    'FX_URL_404' => '/404.html',
    // 定义错误跳转页面URL地址
    'FX_PICK_JS' => '',
    //js压缩扩展名原版本为空 压缩版.min
    'FX_PERPAGE' => '15',
    'FX_INDEC_AUTH_KEY' => 'fx_user',
    'FX_MANAGE_AUTH_KEY' => 'fx_admin_user',
    'FX_COOKIE_TIMEOUT' => 72000,
    'FX_QRCODE_KEY' => 'QINGZHIFU',
    //cookie过期时间
    'TAGLIB_PRE_LOAD' => 'html',
    //import tags
    'DB_PARAMS' => array(
        \PDO::ATTR_CASE => \PDO::CASE_NATURAL),
    //数据字段大小写问题处理
    //'配置项'=>'配置值'
    'DEFAULT_C_LAYER' => 'Controller',
    // 默认的控制器层名称
    'MULTI_MODULE' => true,
    // 是否允许多模块 如果为false 则必须设置 DEFAULT_MODULE
    'MODULE_DENY_LIST' => array(
        'Common',
        'Runtime'
    //临时文件
    ),
    // 禁止访问的模块列表
    'MODULE_ALLOW_LIST' => array(
        'Index',
        'Manage'
    ),
    // 配置你原来的分组列表
    'DEFAULT_MODULE' => 'Index',
    // 配置你原来的默认分组
    'URL_CASE_INSENSITIVE' => true,
    // 默认false 表示URL区分大小写 true则表示不区分大小写
    'URL_PATHINFO_DEPR' => '/',
    // 简化url层次
    'URL_MODEL' => 2,
    //REWRITE 模式
    'URL_HTML_SUFFIX' => 'html',
    // html
    'URL_ROUTER_ON' => true,
    // 开启路由
    'URL_ROUTE_RULES' => array(
        'Index/Index/index' => '/',
    ),
    // 路由规则
    'URL_ROUTE_RULES' => array(
        'Index/Index/index' => '/',
        'reg' => 'Index/Index/reg',
        'login' => 'Index/Index/login',
        'pass' => 'Index/Index/pass'
    ),
    'TMPL_EXCEPTION_FILE' => APP_PATH . 'Common/View/error.html',
    // 定义公共错误模板
    'TMPL_VAR_IDENTIFY' => 'array',
    //模板变量识别
    'TMPL_STRIP_SPACE' => 'false',
    //是否去除模板文件里面的html空格与换行
    'TMPL_CACHE_ON' => 'false',
    //是否开启模板编译缓存
    'TMPL_L_DELIM' => '{#',
    //模板标签开头
    'TMPL_R_DELIM' => '#}',
    //模板标签结尾
    //'TMPL_CACHE_TIME'     => 3600,        //模板缓存有效期 0为永久
    //'TMPL_DENY_PHP'       =>  'true',     //是否禁用PHP原生代码
    'TAGLIB_BEGIN' => '{#',
    //标签库标签开始标记
    'TAGLIB_END' => '#}',
    //标签库标签结束标记
);
