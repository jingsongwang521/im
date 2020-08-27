<?php


namespace app\websocket\Logic;


class Container
{

    private static $data = [];

    public static function set($key, $object)
    {
        self::$data[$key] = $object;
    }

    public static function get($key)
    {
        if(isset(self::$data[$key])) {
            return self::$data[$key];
        } else {
            return null;
        }
    }


}