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


    public function __construct()
    {

    }

    /**
     * 支持的消息类型
     * @return array
     */
    public function getMsgTypeList()
    {
        $list = [
            'text',
            'image',
            'image_text',
            'system_welcome'
        ];
        return $list;
    }

    /**
     * 是否有效的消息类型
     * @param $msg_type
     * @return bool
     */
    public function isValidMsgType($msg_type)
    {
        $type_list = $this->getMsgTypeList();
        if(in_array($msg_type, $type_list)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 系统欢迎消息
     * @param $server
     * @param $fd
     * @param $chat_id
     * @param $from_im_id
     * @param $to_im_id
     */
    public function sendWelcomeMsg($server, $fd, $chat_id, $from_im_id, $to_im_id)
    {
        $message = '同学你好，欢迎来到筑龙学社，22年建筑教育品牌，成就1600万建筑人的职业成长！';
        $data = [
            'msg_id' => uniqid(),
            "msg_time" => msectime(),
            "chat_id" => $chat_id,
            "from_im_id" => 'system',
            "from_avatar" => '',
            "to_im_id" => $to_im_id,
            "msg_type" => 'system_welcome',
            "content"  => ["text"=>$message],
            "extra" => new \stdClass(),
        ];
        Response::instance()->send($server, $fd, 'recv_msg', $data);
    }

    /**
     * 聊天用户下线
     * @param $server
     * @param $fd
     * @param $chat_id
     * @param $from_im_id
     * @param $to_im_id
     */
    public function sendOfflineMsg($server, $fd, $chat_id, $from_im_id, $to_im_id)
    {
        $message = '对方已下线';
        $data = [
            'msg_id' => uniqid(),
            "msg_time" => msectime(),
            "chat_id" => $chat_id,
            "from_im_id" => 'system',
            "from_avatar" => '',
            "to_im_id" => $to_im_id,
            "msg_type" => 'system_to_offline',
            "content"  => ["text"=>$message],
            "extra" => new \stdClass(),
        ];
        Response::instance()->send($server, $fd, 'recv_msg', $data);
    }


}