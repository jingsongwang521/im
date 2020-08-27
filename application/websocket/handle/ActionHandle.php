<?php


namespace app\websocket\handle;

use app\websocket\lib\ResultRet;
use app\websocket\logic\ChatList;
use app\websocket\logic\Im;
use app\websocket\logic\Connect;
use app\websocket\lib\Error;

use app\common\lib\traits\SingletonTrait;
use app\websocket\logic\Message;
use app\websocket\model\ChatModel;
use app\websocket\model\UserModel;

class ActionHandle
{
    use SingletonTrait;

    protected $reponse;

    public function __construct()
    {
        $this->reponse = Response::instance();
    }

    public function handle($server, $frame)
    {
        $data = json_decode($frame->data, 1);
        $action = isset($data['action']) ? $data['action'] : [];
        $action_data = isset($data['data']) ? $data['data'] : [];
        if(empty($data)) {
            $this->reponse->error($server, $frame->fd, $action, Error::PARAM_PARSE_FAILED);
        } else {
            $method = $this->getActionMethod($action);
            if(method_exists($this, $method)) {
                $this->$method($server, $frame, $action_data);
            } else {
                $this->reponse->error($server, $frame->fd, Error::INVALID_ACTION);
            }
        }
    }

    protected function getActionMethod($action)
    {
//        $action_arr = array_map('ucfirst', explode('_', $action));
//        $method = implode('', $action_arr);
        $method = 'handle_' . $action;
        return $method;
    }

    /**
     * 客户端初始化
     * @param $server
     * @param $frame
     * @param $action_data
     */
    public function handle_init_client($server, $frame, $action_data)
    {
        $im = Im::instance();
        $connect = Connect::instance();

        $to_uid = empty($action_data['to_uid']) ? 0 : $action_data['to_uid'];
        $user_im_id = $im->initImUser($action_data);
        // 刷新在线列表
        if(!empty($user_im_id)) {
            $im_info = UserModel::instance()->getUser($user_im_id);
            $connect->refreshOnline($frame->fd, $im_info);
            echo "[refreshOnline] fd:{$frame->fd} im_id:{$user_im_id}\n";
        } else {
            $this->reponse->error($server, $frame->fd, 'init_client', Error::USER_INIT_FAILED);
        }

        echo "[now online_list]\n";
        var_dump($connect->getOnlineList());

        if($im_info['user_type'] != 3) {
            $to_im_id = "";
            if($to_uid > 0) {
                $to_im_info = UserModel::instance()->getUserByUid($user_im_id);
                if(!empty($to_im_info)) {
                    $to_im_id = (string)$to_im_info['_id'];
                }
            }
            $chat_ret = $im->initChat($user_im_id, $to_im_id);
            $chat = $chat_ret->getResult();
        } else {
            $chat = [];
        }

        // 建立channel
        if(!empty($chat)) {
            echo "[addChannel]\n";
            var_dump($chat);
            $connect->addChannel($chat['connect_id'], $chat['chat_id'], $user_im_id,
                (string)$chat['to_im_info']['_id'], $frame->fd, $chat['to_fd'],
                $user_im_id, (string)$chat['to_im_info']['_id']);
            echo "[addChannel] fd:{$frame->fd} im_id:{$user_im_id} chat_id:{$chat['chat_id']}\n";
        }
        echo "[now channel_list]\n";
        var_dump($connect->getChannelList());

        //TODO 会话列表
        $chat_list = ChatList::instance()->getListData($user_im_id);
        echo "[now chat_list]\n";
        var_dump($chat_list);

//        $chat_list = ['zhulong_list'=>[], 'other_list'=>[]];
//        if(!empty($chat)) {
//            if(in_array($chat['to_im_info']['user_type'],[2,3])) {
//                $chat_list_type = 'zhulong_list';
//            } else {
//                $chat_list_type = 'other_list';
//            }
//            $chat_list[$chat_list_type][] = [
//                'chat_id' => $chat['chat_id'],
//                'connect_id' => $chat['connect_id'],
//                'im_id' => (string)$chat['to_im_info']['_id'],
//                'uid' => $chat['to_im_info']['uid'],
//                'user_type' => $chat['to_im_info']['user_type'],
//                'nickname' => $chat['to_im_info']['nickname'],
//                'avatar' => $chat['to_im_info']['avatar'],
//                'unread' => 0,
//                'online' => 1,
//            ];
//        }
        $data =  [
            'im_id' => $user_im_id,
            'user_type' => $im_info['user_type'],
            'chat' => empty($chat) ? new \stdClass() : [
                'chat_id' => $chat['chat_id'],
                'connect_id' => $chat['connect_id'],
                'im_id' => (string)$chat['to_im_info']['_id'],
                'uid' => $chat['to_im_info']['uid'],
                'user_type' => $chat['to_im_info']['user_type'],
                'nickname' => $chat['to_im_info']['nickname'],
                'avatar' => $chat['to_im_info']['avatar'],
                'unread' => 0,
                'online' => 1,
            ],
            'chat_list' => $chat_list
        ];
        $this->reponse->send($server, $frame->fd, 'recv_init_client', $data);

        if(!empty($chat)) {
            // 通知对方聊天已建立
            $notify_data = [
                    'chat_id' => $chat['chat_id'],
                    'connect_id' => $chat['connect_id'],
                    'im_id' => $user_im_id,
                    'uid' => $im_info['uid'],
                    'nickname' => $im_info['nickname'],
                    'avatar' => $im_info['avatar'],
                    'user_type' => $im_info['user_type'],
                    'unread' => 0,
                    'online' => 1,
            ];
            $to_fd = Connect::instance()->getOnlineUserFd((string)$chat['to_im_info']['_id']);
            $this->reponse->send($server, $to_fd, 'recv_chat_notify', $notify_data);

            sleep(3);
            Message::instance()->sendWelcomeMsg($server, $frame->fd, $chat['chat_id'], (string)$chat['to_im_info']['_id'], $user_im_id);
        }
    }

    /**
     * 聊天初始化
     * @param $server
     * @param $frame
     * @param $action_data
     */
    public function handle_init_chat($server, $frame, $action_data)
    {
        $im = Im::instance();

        $im_id = $action_data['im_id'];
        $to_im_id = $action_data['to_im_id'];

        $chat_ret = $im->initChat($im_id, $to_im_id);
        if($chat_ret->isSuccess()) {
            $chat = $chat_ret->getResult();
            $data =  [
                'chat_id' => $chat['chat_id'],
                'connect_id' => $chat['connect_id'],
                'im_id' => (string)$chat['to_im_info']['_id'],
                'uid' => $chat['to_im_info']['uid'],
                'user_type' => $chat['to_im_info']['user_type'],
                'nickname' => $chat['to_im_info']['nickname'],
                'avatar' => $chat['to_im_info']['avatar'],
            ];
            $this->reponse->send($server, $frame->fd, 'recv_init_chat', $data);
        } else {
            $this->reponse->error($server, $frame->fd, 'init_chat', $chat_ret->getErrNo(), $chat_ret->getMsg());
        }
    }

    /**
     * 发送消息
     * @param $server
     * @param $frame
     * @param $action_data
     */
    public function handle_send_msg($server, $frame, $action_data)
    {
        // 获取channel
        $im_id = $action_data['from_im_id'];
        $chat_id = $action_data['chat_id'];
        $channel_info = Connect::instance()->getChatChannel($chat_id);

        echo "[now channel_list]\n";
        var_dump(Connect::instance()->getChannelList());
        echo "[channel_info]\n";
        var_dump($channel_info);

        if(!empty($channel_info)) {
            $from_im_info = UserModel::instance()->getUser($action_data['from_im_id']);
            $data = [
                    'msg_id' => uniqid(),
                    "msg_time" => msectime(),
                    "chat_id" => $chat_id,
                    "from_im_id" => $action_data['from_im_id'],
                    "from_avatar" => $from_im_info ? $from_im_info['avatar'] : '',
                    "to_im_id" => $action_data['to_im_id'],
                    "msg_type" => $action_data['msg_type'],
                    "content"  => $action_data['content'],
                    "extra" => empty($action_data['extra']) ? new \stdClass() : $action_data['extra'],
            ];
            if($im_id == $channel_info['im_id1']) {
                $to_fd = $channel_info['fd2'];
            } else {
                $to_fd = $channel_info['fd1'];
            }
            $this->reponse->send($server, $to_fd, 'recv_msg', $data);
        } else {
            $this->reponse->error($server, $frame->fd, 'send_msg', Error::CHAT_USER_OFFLINE);
        }
    }

    public function handle_init_chat_by_id($server, $frame, $action_data)
    {
        $im_id = $action_data['im_id'];
        $chat_id = $action_data['chat_id'];

        $chat_model = ChatModel::instance();
        $chat_info = $chat_model->getChat($chat_id);
        if(empty($chat_info)) {
            $this->reponse->error($server, $frame->fd, 'init_chat_by_id', Error::CHAT_USER_OFFLINE);
        }
        if(!in_array($im_id, [$chat_info['im_id1'], $chat_info['im_id1']])) {
            $this->reponse->error($server, $frame->fd, 'init_chat_by_id', Error::USER_NOT_IN_CHAT);
        }
        // 用户序号
        $im_index = ($im_id == $chat_info['im_id1']) ? 1 : 2;
        $to_im_index = $im_index == 1 ? 2 : 1;
        $to_im_id = $chat_info['im_id'.$to_im_index];

        $channel_info = Connect::instance()->getChatChannel($chat_id);
        $connect_id = $channel_info ? $channel_info['connect_id'] : '';

        $user_model = UserModel::instance();
        $to_im_info = $user_model->getUser($to_im_id);
        $data = [
            'chat_id' => $chat_id,
            'connect_id' => $connect_id,
            'im_id' => $to_im_id,
            'uid' => $to_im_info ? $to_im_info['uid'] : 0,
            'nickname' => $to_im_info ? $to_im_info['nickname'] : '',
            'avatar' => $to_im_info ? $to_im_info['avatar'] : '',
            'user_type' => $to_im_info ? $to_im_info['user_type'] : '',
        ];
        $this->reponse->send($server, $frame->fd, 'recv_init_chat_by_id', $data);
    }

    /**
     * 获取会话列表
     * @param $server
     * @param $fd
     */
    public function handle_get_chat_list($server, $frame, $action_data)
    {
        $im_id = $action_data['im_id'];
        $chat_list = ChatList::instance()->getListData($im_id);
        $data = $chat_list;
        $this->reponse->send($server, $frame->fd, 'recv_chat_list', $data);
    }

    public function handleClose($server, $fd)
    {
        $connect = Connect::instance();
        $im_id = $connect->getOnlineImIdByfd($fd);

        // 下线
        $connect->removeOnline($im_id);

        // 移除Channel



        echo "[offline] fd:{$fd} im_id:{$im_id}\n";
    }



}