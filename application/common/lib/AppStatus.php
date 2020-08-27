<?php
/**
 * 运行环境类
 */
namespace app\common\lib;

use think\facade\Env;

class AppStatus {
    // 开发环境
    const DEVELOPMENT = 'development';
    // 测试环境
    const TESTING = 'testing';
    // 预发布环境
    const STAGING = 'staging';
    // 生产环境
    const PRODUCTION = 'production';

    /**
     * 获取当前环境
     * @return string
     */
    public static function getStatus()
    {
        $status = Env::get('APP_STATUS', '');
        return $status;
    }

    /**
     * 运行环境是否有效
     * @param string $app_status
     * @return bool
     */
    public static function isValidStatus($app_status)
    {
        if(empty($app_status) ||
            !in_array($app_status, [self::DEVELOPMENT, self::TESTING, self::STAGING, self::PRODUCTION])
        ) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 是否开发环境
     * @param $app_status
     * @return bool
     */
    public static function isDevelopment($app_status=null)
    {
        if(is_null($app_status)) {
            $app_status = self::getStatus();
        }
        return $app_status == self::DEVELOPMENT;
    }

    /**
     * 是否测试环境
     * @param $app_status
     * @return bool
     */
    public static function isTesting($app_status=null)
    {
        if(is_null($app_status)) {
            $app_status = self::getStatus();
        }
        return $app_status == self::TESTING;
    }

    /**
     * 是否预发布环境
     * @param $app_status
     * @return bool
     */
    public static function isStaging($app_status=null)
    {
        if(is_null($app_status)) {
            $app_status = self::getStatus();
        }
        return $app_status == self::STAGING;
    }

    /**
     * 是否生产环境
     * @param $app_status
     * @return bool
     */
    public static function isProduction($app_status=null)
    {
        if(is_null($app_status)) {
            $app_status = self::getStatus();
        }
        return $app_status == self::PRODUCTION;
    }
}