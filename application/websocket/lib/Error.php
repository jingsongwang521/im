<?php
/**
 * 错误管理类
 */

namespace app\websocket\lib;

class Error {
    // 成功
    const SUCCESS   = '';
    // 默认错误
    const FAILED   = 'action_failed';

    const PARAM_PARSE_FAILED = 'param_parse_failed';
    const INVALID_ACTION = 'invalid_action';
    const INVALID_PARAM = 'invalid_param';
    const NO_AVAILABLE_SALER = 'no_available_saler';
    const CHAT_USER_NOT_EXIST = 'chat_user_not_exist';
    const USER_NOT_IN_CHAT = 'user_not_in_chat';
    const INIT_CHAT_FAILED = 'init_chat_failed';
    const CHAT_USER_OFFLINE = 'chat_user_offline';
    const CHAT_NOT_EXIST = 'chat_not_exist';
    const USER_INIT_FAILED = 'user_init_failed';

    /**
     * 错误提示文字
     */
    private static $message = [
        self::SUCCESS => '',
        self::FAILED => '操作失败',

        self::PARAM_PARSE_FAILED => '参数无法解析',
        self::INVALID_ACTION => '无效的action',
        self::INVALID_PARAM => '无效的参数',
        self::NO_AVAILABLE_SALER => '没有在线的客服',
        self::CHAT_USER_NOT_EXIST => '聊天对象不存在',
        self::INIT_CHAT_FAILED => '初始化聊天失败',
        self::CHAT_USER_OFFLINE => '聊天对象未在线',
        self::CHAT_NOT_EXIST => '聊天不存在',
        self::USER_NOT_IN_CHAT => '用户不在聊天中',
        self::USER_INIT_FAILED => '用户初始化失败'


    ];

    /**
     * 获取错误提示信息
     * @param string $errNo
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