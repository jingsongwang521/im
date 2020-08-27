<?php
/**
 * 错误管理类
 */

namespace app\common\lib;

class Error {
    // 成功
    const SUCCESS   = 0;
    // 默认错误
    const FAILED   = 99;

    /**
     * 错误提示文字
     */
    private static $message = [
        self::SUCCESS => '',
        self::FAILED => '操作失败',

    ];

    /**
     * 获取错误提示信息
     * @param int $errNo
     * @param array $param
     * @return string
     */
    public static function getMsg($errNo, $param=[]) {
        if(isset(self::$message[$errNo])) {
            if(!empty($param)) {
                array_unshift($param, self::$message[$errNo]);
                return call_user_func_array('sprintf', $param);
            } else {
                return self::$message[$errNo];
            }
        } else {
            return self::$message[self::FAILED];
        }
    }


}