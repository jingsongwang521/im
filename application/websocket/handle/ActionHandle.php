<?php


namespace app\websocket\handle;

use app\websocket\lib\ResultRet;
use app\websocket\logic\Chat;
use app\websocket\logic\ChatList;
use app\websocket\logic\Im;
use app\websocket\logic\Connect;
use app\websocket\lib\Error;

use app\common\lib\traits\SingletonTrait;
use app\websocket\logic\Message;
use app\websocket\model\ChatModel;
use app\websocket\model\UserModel;
use think\facade\Request;

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
        $action = isset($data['action']) ? $data['action'] : '';
        $action_data = isset($data['data']) ? $data['data'] : [];
        if(empty($data)) {
            $this->reponse->error($server, $frame->fd, $action, Error::PARAM_PARSE_FAILED);
            return false;
        } else {
            $method = $this->getActionMethod($action);
            if(method_exists($this, $method)) {
                $this->$method($server, $frame, $action_data);
            } else {
                $this->reponse->error($server, $frame->fd, Error::INVALID_ACTION);
                return false;
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
        $src_action = 'init_client';
        $im = Im::instance();
        $connect = Connect::instance();
        $im_id = empty($action_data['im_id']) ? '' : $action_data['im_id'];
        $uid = empty($action_data['uid']) ? 0 : (int)$action_data['uid'];
        $nickname = empty($action_data['nickname']) ? '' : $action_data['nickname'];
        $avatar = empty($action_data['avatar']) ? '' : $action_data['avatar'];
        $user_type = empty($action_data['user_type']) ? 0 : (int)$action_data['user_type'];
        $page_title = empty($action_data['page_title']) ? '' : $action_data['page_title'];
        $page_url = empty($action_data['page_url']) ? '' : $action_data['page_url'];
        $to_uid = empty($action_data['to_uid']) ? 0 : (int)$action_data['to_uid'];
        $chat_id = empty($action_data['chat_id']) ? '' : $action_data['chat_id'];


        $user_im_id = $im->initImUser([
            'uid' => $uid,
            'im_id' => $im_id,
            'nickname' => $nickname,
            'avatar' => $avatar,
            'user_type' => $user_type,
        ]);
        if(empty($user_im_id)) {
            $this->reponse->error($server, $frame->fd, 'init_client', Error::USER_INIT_FAILED);
            return false;
        }
        // 刷新在线列表
        $im_info = UserModel::instance()->getUser($user_im_id);
        $connect->refreshOnline($frame->fd, $im_info);
        echo "[refreshOnline] fd:{$frame->fd} im_id:{$user_im_id}\n";

        echo "[now online_list]\n";
        var_dump($connect->getOnlineList());

        $chat_list = ChatList::instance()->getListData($user_im_id);
        echo "[now chat_list]\n";
//        var_dump($chat_list);

        $chat_data = [];
        $chat_show_welcome = 0;
        if($chat_id == '') {
            if($im_info['user_type'] != 3) {
                $to_im_id = "";
                if($to_uid > 0) {
                    $to_im_info = UserModel::instance()->getUserByUid($user_im_id);
                    if(!empty($to_im_info)) {
                        $to_im_id = (string)$to_im_info['_id'];
                    }
                }
                $chat_ret = $im->initChat($server, $frame, $user_im_id, $to_im_id);
                $chat = $chat_ret->getResult();
                echo "chat:\n";
                var_dump($chat);
                if(!empty($chat)) {
                    $chat_show_welcome = 1;
                } else {
                    echo date('Y-m-d H:i:s')."[init_chat_result] ".$chat_ret->getMsg()."\n";
                }
            } else {
                $chat_list_data = empty($chat_list['zhulong_list']) ?
                    (empty($chat_list['other_list']) ? [] : $chat_list['other_list'][0])
                    : $chat_list['zhulong_list'][0];
                if(!empty($chat_list_data)) {
                    $chat_ret = $im->initChatById($server, $frame, $user_im_id, $chat_list_data['chat_id']);
                    $chat = $chat_ret->getResult();
//                    $chat_data = [
//                        'chat_id' => $chat_list_data['chat_id'],
//                        'connect_id' => $chat_list_data['connect_id'],
//                        'im_id' => $chat_list_data['im_id'],
//                        'uid' => $chat_list_data['uid'],
//                        'user_type' => $chat_list_data['user_type'],
//                        'nickname' => $chat_list_data['nickname'],
//                        'avatar' => $chat_list_data['avatar'],
//                        'online' =>$chat_list_data['online'],
//                    ];
                }
            }
        } else {
//            $chat_data = Chat::instance()->getChatData($user_im_id, $chat_id);
            $chat_ret = $im->initChatById($server, $frame, $user_im_id, $chat_id);
            $chat = $chat_ret->getResult();
        }
        if(!empty($chat)) {
            $chat_data = [
                'chat_id' => $chat['chat_id'],
                'connect_id' => $chat['connect_id'],
                'im_id' => (string)$chat['to_im_info']['_id'],
                'uid' => $chat['to_im_info']['uid'],
                'user_type' => $chat['to_im_info']['user_type'],
                'nickname' => $chat['to_im_info']['nickname'],
                'avatar' => $chat['to_im_info']['avatar'],
                'online' => $connect->isOnline((string)$chat['to_im_info']['_id']) ? 1 : 0,
            ];
        }

//        // 建立channel
//        if(!empty($chat)) {
//            echo "[addChannel]\n";
//            $connect->addChannel($chat['connect_id'], $chat['chat_id'], $user_im_id,
//                (string)$chat['to_im_info']['_id'], $frame->fd, $chat['to_fd'],
//                $user_im_id, (string)$chat['to_im_info']['_id']);
//            echo "[addChannel] fd:{$frame->fd} im_id:{$user_im_id} chat_id:{$chat['chat_id']}\n";
//        }
//        echo "[now channel_list]\n";
//        var_dump($connect->getChannelList());

//        $chat_list = ChatList::instance()->getListData($user_im_id);
        $data =  [
            'im_id' => $user_im_id,
            'user_type' => $im_info['user_type'],
            'chat' => empty($chat_data) ? new \stdClass() : $chat_data,
            'chat_list' => $chat_list
        ];
        $this->reponse->send($server, $frame->fd, 'recv_init_client', $data);

        // 发送欢迎消息（有时间延迟放在最后执行）
        if(!empty($chat) && $chat_show_welcome) {
            sleep(3);
            Message::instance()->sendWelcomeMsg($server, $frame->fd, $chat['chat_id'], (string)$chat['to_im_info']['_id'], $user_im_id);
        }
        return true;
    }

    /**
     * 聊天初始化
     * @param $server
     * @param $frame
     * @param $action_data
     */
    public function handle_init_chat($server, $frame, $action_data)
    {
        $src_action = 'init_chat';
        $im = Im::instance();
        $im_id = empty($action_data['im_id']) ? '' : $action_data['im_id'];
        $to_im_id = empty($action_data['to_im_id']) ? '' : $action_data['to_im_id'];
        if(empty($im_id)) {
            $this->reponse->error($server, $frame->fd, $src_action, Error::INVALID_PARAM);
            return false;
        }
        $chat_ret = $im->initChat($server, $frame, $im_id, $to_im_id);
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
                'online' => Connect::instance()->isOnline((string)$chat['to_im_info']['_id']),
            ];
            $this->reponse->send($server, $frame->fd, 'recv_init_chat', $data);
        } else {
            $this->reponse->error($server, $frame->fd, $src_action, $chat_ret->getErrNo(), $chat_ret->getMsg());
            return false;
        }
        return true;
    }

    public function handle_init_chat_by_id($server, $frame, $action_data)
    {
        $src_action = 'init_chat_by_id';
        $im = Im::instance();
        $im_id = empty($action_data['im_id']) ? '' : $action_data['im_id'];
        $chat_id = empty($action_data['chat_id']) ? '' : $action_data['chat_id'];
        if(empty($im_id) || empty($chat_id)) {
            $this->reponse->error($server, $frame->fd, $src_action, Error::INVALID_PARAM);
            return false;
        }
        $chat_ret = $im->initChatById($server, $frame, $chat_id);
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
                'online' => Connect::instance()->isOnline((string)$chat['to_im_info']['_id']),
            ];
            $this->reponse->send($server, $frame->fd, 'recv_init_chat', $data);
        } else {
            $this->reponse->error($server, $frame->fd, $src_action, $chat_ret->getErrNo(), $chat_ret->getMsg());
            return false;
        }
        return true;
    }

    /**
     * 发送消息
     * @param $server
     * @param $frame
     * @param $action_data
     */
    public function handle_send_msg($server, $frame, $action_data)
    {
        $src_action = 'send_msg';
        // 获取channel
        $chat_id = empty($action_data['chat_id']) ? '' : $action_data['chat_id'];
        $from_im_id = empty($action_data['from_im_id']) ? '' : $action_data['from_im_id'];
        $to_im_id = empty($action_data['to_im_id']) ? '' : $action_data['to_im_id'];
        $client_msg_id = empty($action_data['client_msg_id']) ? '' : $action_data['client_msg_id'];
        $msg_type = empty($action_data['msg_type']) ? '' : $action_data['msg_type'];
        $content = empty($action_data['content']) ? '' : $action_data['content'];
        if(empty($chat_id) || empty($from_im_id) || empty($to_im_id) || empty($msg_type) || empty($content)) {
            $this->reponse->error($server, $frame->fd, $src_action, Error::INVALID_PARAM);
            return false;
        }
        if(!Message::instance()->isValidMsgType($msg_type)) {
            $this->reponse->error($server, $frame->fd, $src_action, Error::INVALID_PARAM, '消息类型错误：'.$msg_type);
            return false;
        }

        $connect = Connect::instance();
        $channel_info = $connect->getChatChannel($chat_id);
        if(empty($channel_info)) {
            $online = $connect->isOnline($to_im_id);
            if(!$online) {
                Message::instance()->sendOfflineMsg($server, $frame->fd, $chat_id, $from_im_id, $to_im_id);
                $this->reponse->error($server, $frame->fd, $src_action, Error::CHAT_USER_OFFLINE);
                return false;
            }
            $chat_ret = Im::instance()->initChatById($server, $frame, $from_im_id, $chat_id);
            $chat_data  = $chat_ret->getResult();
            if(!empty($chat_data)) {
                Im::instance()->sendChatList($server, $frame->fd, $to_im_id);
                $channel_info = $connect->getChatChannel($chat_id);
            }
        }
        if(empty($channel_info)) {
            $this->reponse->error($server, $frame->fd, $src_action, Error::CHAT_USER_OFFLINE);
            return false;
        }

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
        if($from_im_id == $channel_info['im_id1']) {
            $to_fd = $channel_info['fd2'];
        } else {
            $to_fd = $channel_info['fd1'];
        }
        $this->reponse->send($server, $to_fd, 'recv_msg', $data);

        return true;
    }

    public function handle_get_chat($server, $frame, $action_data)
    {
        $src_action = 'get_chat';
        $im_id = empty($action_data['im_id']) ? '' : $action_data['im_id'];
        $chat_id = empty($action_data['chat_id']) ? '' : $action_data['chat_id'];
        if(empty($im_id) || empty($chat_id)) {
            $this->reponse->error($server, $frame->fd, $src_action, Error::INVALID_PARAM);
            return false;
        }
        $chat_model = ChatModel::instance();
        $chat_info = $chat_model->getChat($chat_id);
        if(empty($chat_info)) {
            $this->reponse->error($server, $frame->fd, $src_action, Error::CHAT_USER_OFFLINE);
            return false;
        }
        if(!in_array($im_id, [$chat_info['im_id1'], $chat_info['im_id1']])) {
            $this->reponse->error($server, $frame->fd, $src_action, Error::USER_NOT_IN_CHAT);
            return false;
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
            'online' => Connect::instance()->isOnline($to_im_id),
        ];
        $this->reponse->send($server, $frame->fd, 'recv_chat', $data);
        return true;
    }

    /**
     * 获取会话列表
     * @param $server
     * @param $fd
     */
    public function handle_get_chat_list($server, $frame, $action_data)
    {
        $src_action = 'get_chat_list';
        $im_id = empty($action_data['im_id']) ? '' : $action_data['im_id'];
        if(empty($im_id)) {
            $this->reponse->error($server, $frame->fd, $src_action, Error::INVALID_PARAM);
            return false;
        }
        $chat_list = ChatList::instance()->getListData($im_id);
        $data = $chat_list;
        $this->reponse->send($server, $frame->fd, 'recv_chat_list', $data);
        return true;
    }

    public function handleClose($server, $fd)
    {
        $connect = Connect::instance();
        $im_id = $connect->getOnlineImIdByfd($fd);

        // 下线
        $connect->removeOnlineByFd($fd);

        // 移除Channel
        $connect->removeChannelByFd($fd);

        echo "[offline] fd:{$fd} im_id:{$im_id}\n";
        return true;
    }



}