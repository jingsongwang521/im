<?php
/**
 * Redis 操作类
 */

namespace app\common\lib;

use app\common\lib\traits\SingletonTrait;
use think\Facade\Config;

/* @see \Redis
 * @mixin \Redis
 */
class Redis {

    use SingletonTrait;

    const PREFIX = 'zl_im_';

    const KEY_CHATLIST = 'chatlist_';

    /**
     * @var \Redis
     */
    protected $redis;

    public function __construct()
    {
        $config = Config::get('redis.');
        $redis = new \Redis();
        $redis->connect($config['host'], $config['port'], 2);
//        $redis->auth($config['password']);
        $redis->select(2);//选择数据库2
        $this->redis = $redis;
    }

    /**
     * 会话列表key
     * @param $im_id
     * @return string
     */
    public static function keyChatList($im_id)
    {
        return Redis::PREFIX . Redis::KEY_CHATLIST . $im_id;
    }


    public function __call($method, $args)
    {
        return call_user_func_array([$this->redis, $method], $args);
    }








}