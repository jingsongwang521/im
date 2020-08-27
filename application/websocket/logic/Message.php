<?php


namespace app\websocket\logic;

use app\websocket\handle\Response;
use app\websocket\lib\Error;
use app\websocket\logic\Connect;
use app\websocket\model\UserModel;
use app\websocket\model\ChatModel;
use app\websocket\lib\ResultRet;

use app\common\lib\traits\SingletonTrait;
use think\Db;

class Message
{
    use SingletonTrait;

    protected $user_model;
    protected $chat_model;


    public function __construct()
    {
        $this->user_model = UserModel::instance();
        $this->chat_model = ChatModel::instance();
    }

    public function sendWelcomeMsg($server, $fd, $chat_id, $from_im_id, $to_im_id)
    {
        $message = '同学你好，欢迎来到筑龙学社，22年建筑教育品牌，成就1600万建筑人的职业成长！';
        $data = [
            'msg_id' => uniqid(),
            "msg_time" => msectime(),
            "chat_id" => $chat_id,
            "from_im_id" => $from_im_id,
            "to_im_id" => $to_im_id,
            "msg_type" => 'system_welcome',
            "content"  => ["text"=>$message],
            "extra" => new \stdClass(),
        ];
        Response::instance()->send($server, $fd, 'recv_msg', $data);
    }


}