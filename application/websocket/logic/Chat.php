<?php


namespace app\websocket\logic;

use app\websocket\logic\Connect;
use app\websocket\model\UserModel;
use app\websocket\model\ChatModel;

use app\common\lib\traits\SingletonTrait;
use think\Db;

class Chat
{
    use SingletonTrait;

    public function __construct()
    {
    }

    public function getChatData($im_id, $chat_id)
    {
        $chat_data = [];
        $chat_model = ChatModel::instance();
        $chat_info = $chat_model->getChat($chat_id);
        if(empty($chat_info)) {
            return [];
        }
        // 用户序号
        $im_index = ($im_id == $chat_info['im_id1']) ? 1 : 2;
        $to_im_index = $im_index == 1 ? 2 : 1;
        $to_im_id = $chat_info['im_id'.$to_im_index];

        $connect_id = Connect::instance()->getChatConncetId($chat_id);

        $user_model = UserModel::instance();
        $to_im_info = $user_model->getUser($to_im_id);
        $chat_data = [
            'chat_id' => $chat_id,
            'connect_id' => $connect_id,
            'im_id' => $to_im_id,
            'uid' => $to_im_info ? $to_im_info['uid'] : 0,
            'nickname' => $to_im_info ? $to_im_info['nickname'] : '',
            'avatar' => $to_im_info ? $to_im_info['avatar'] : '',
            'user_type' => $to_im_info ? $to_im_info['user_type'] : '',
            'online' => Connect::instance()->isOnline($to_im_id) ? 1 : 0,
        ];
        return $chat_data;
    }

}