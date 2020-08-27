<?php


namespace app\common\lib\traits;


trait SingletonTrait
{
    private static $_instance;

    public static function instance()
    {
        if(is_null(self::$_instance)) {
            self::$_instance = new static();
        }
        return self::$_instance;
    }
}