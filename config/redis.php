<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

use app\common\lib\AppStatus;

if(AppStatus::isDevelopment() || AppStatus::isTesting()) {
    return [
        // 地址
        'host' => '192.168.1.186',
        // 全局缓存有效期（0为永久有效）
        'expire'=>  0,
        // 缓存前缀
        'prefix'=>  'zl_im_',
        // 端口
        'port'=>  '6379',
        // 密码
        'password'    => '',
        // 数据库序号
        'select'    => 2,
    ];
} elseif (AppStatus::isProduction()) {
    return [
        // 地址
        'host' => '192.168.1.186',
        // 全局缓存有效期（0为永久有效）
        'expire'=>  0,
        // 缓存前缀
        'prefix'=>  'zl_im_',
        // 端口
        'port'=>  '6379',
        // 密码
        'password'    => '',
        // 数据库序号
        'select'    => 2,
    ];
}