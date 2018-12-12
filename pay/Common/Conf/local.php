<?php
// +----------------------------------------------------------------------
// | easy pay [ pay to easy ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2017 All rights reserved.
// +----------------------------------------------------------------------
// | Author: fengxing <QQ:51125330>
// +----------------------------------------------------------------------
return array( //'配置项'=>'配置值'
              //系统名称
              'DB_TYPE'             => 'mysql',
              // 数据库类型
              'DB_HOST'             => '127.0.0.1',
              // 服务器地址
              'DB_NAME'             => 'fx_full',
              // 数据库名
              'DB_USER'             => 'root',
              // 用户名
              'DB_PWD'              => 'root',
              // 密码
              'DB_PORT'             => 3306,
              // 端口
              'DB_DEBUG'              =>  false,  // 数据库调试模式 3.2.3新增
              'DB_PREFIX'           => 'fx_',
              // 数据库表前缀
              'DB_SQL_LOG'          => true,
              // 记录SQL信息
              'DB_FIELDS_CACHE'    => true,      // 启用字段缓存
              // 数据库字段缓存
              'SHOW_SQL_ERROR'      => 2,
              // 终止错误sql
              'SHOW_PAGE_TRACE'     => true,
              // 显示页面Trace信息
              'LOG_RECORD'          => true,
              // 进行日志记录
              'LOG_LEVEL'           => 'EMERG,ALERT,CRIT,ERR,WARN,NOTICE,INFO,DEBUG,SQL',
              // 允许记录的日志级别
              /*'SHOW_RUN_TIME'      => true,         // 运行时间显示
              'SHOW_ADV_TIME'        => true,         // 显示详细的运行时间
              'SHOW_DB_TIMES'        => true,         // 显示数据库查询和写入次数
              'SHOW_CACHE_TIMES'     => true,         // 显示缓存操作次数
              'SHOW_USE_MEM'         => true,         // 显示内存开销
              'SHOW_LOAD_FILE'       => true,         // 显示加载文件数
              'SHOW_FUN_TIMES'       => true,         // 显示函数调用次数
              */
              'APP_FILE_CASE'       => true,
              // 是否检查文件的大小写 对Windows平台有效

              'SHOW_PAGE_ERROR_MORE'=> 1,
              // 是否开启错误页的详细显示 0不开起  1开启
);
?>