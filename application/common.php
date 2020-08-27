<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件

// 应用公共文件

use think\facade\Env;
use think\facade\Config;
use app\common\lib\AppStatus;

// 设置环境变量：是否调试模式
if(is_null(Env::get('APP_DEBUG'))) {
    Env::set('APP_DEBUG', 0);
}
// 设置环境变量：运行环境
if(!AppStatus::isValidStatus(Env::get('APP_STATUS'))) {
//    Env::set('APP_STATUS', AppStatus::DEVELOPMENT);
    echo "Error, environment variable is invalid.";
    exit;
}
// 设置报错
if (Env::get('APP_STATUS') ==  AppStatus::PRODUCTION) {
    ini_set('display_errors', 'off');
    ini_set('log_errors', 'on');
    error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING ^ E_DEPRECATED);
} else {
    ini_set('display_errors', 'on');
    ini_set('log_errors', 'on');
    error_reporting(E_ALL ^ E_WARNING ^ E_DEPRECATED);
}

//返回当前的毫秒时间戳
function msectime() {
    list($msec, $sec) = explode(' ', microtime());
    $msectime = (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
    return $msectime;
}