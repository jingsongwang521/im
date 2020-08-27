<?php


namespace app\system\logic\test;


class TestWsServer
{
    public function __construct()
    {
    }

    public function push($fd, $message)
    {
        echo "[".date('Y-m-d H:i:s')."]"."fd:{$fd} message:{$message}";
        echo "<br/>\n";
    }

}