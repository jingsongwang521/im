<?php


namespace app\websocket\handle;

use app\websocket\lib\Error;
use app\common\lib\traits\SingletonTrait;

class Response
{
    use SingletonTrait;

    public function error($server, $fd, $src_action, $error_type, $error_msg='', $data=[])
    {
        $data = [
            'action' => 'recv_error',
            'scr_action' => $src_action,
            'error_type' => $error_type,
            'error_msg' => ($error_msg == "" || is_array($error_msg)) ?
                Error::getMsg($error_type, $error_msg) : $error_msg,
            'data' => empty($data) ? new \stdClass() : $data,
        ];
        $messge = json_encode($data, JSON_UNESCAPED_UNICODE);
        echo date('Y-m-d H:i:s')." [send] fd:{$fd} recv_error ".$messge . "\n";
        $server->push((int)$fd, json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    public function send($server, $fd, $action, $data)
    {
        $messge = json_encode(['action'=>$action, 'data'=>$data], JSON_UNESCAPED_UNICODE);
        echo date('Y-m-d H:i:s')." [send] fd:{$fd} {$action} ".$messge . "\n";
        $server->push((int)$fd, $messge);
    }



}