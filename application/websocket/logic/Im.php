<?php


namespace app\websocket\logic;

use app\websocket\handle\Response;
use app\websocket\lib\Error;
use app\websocket\logic\Connect;
use app\websocket\model\UserModel;
use app\websocket\model\ChatModel;
use app\websocket\lib\ResultRet;
use app\websocket\logic\ChatList;

use app\common\lib\traits\SingletonTrait;
use think\Db;

class Im
{
    use SingletonTrait;

    protected $user_model;
    protected $chat_model;

    public function __construct()
    {
        $this->user_model = UserModel::instance();
        $this->chat_model = ChatModel::instance();
    }

    /**
     * 初始化用户
     * @param $action_data
     * @return bool|mixed|string
     */
    public function initImUser($user_data)
    {
        $uid = $user_data['uid'];
        $im_id = $user_data['im_id'];
        $nickname = $user_data['nickname'];
        $avatar = $user_data['avatar'];
        $user_type = $user_data['user_type'];

        $user_model = UserModel::instance();
        if($uid > 0) {
            $user_info = $user_model->getUserByUid($uid);
            if(empty($user_info)) {
                $user_im_id = $user_model->createUser($uid, $nickname, $avatar, $user_type);
            } else {
                if($uid > 50) {
                    $user_model->updateUser((string)$user_info['_id'], $uid, $nickname, $avatar, $user_type);
                }
                $user_im_id = (string)$user_info['_id'];

            }
        } else {
            $user_im_id = $user_model->createUser($uid, $nickname, $avatar, $user_type);
        }
        return $user_im_id;
    }

    /**
     * 初始化聊天
     * @param $im_id
     * @param string $to_im_id
     * @return ResultRet
     */
    public function initChat($server, $frame, $im_id, $to_im_id='')
    {
        if(empty($to_im_id)) {
            $saler_info = $this->getServiceSaler($im_id);
            echo date('Y-m-d H:i:s')."[assign saler] im_id:{$im_id}\n";
            if(!empty($saler_info)) {
                echo date('Y-m-d H:i:s')."[assign saler] assign_im_id:{$saler_info['im_id']} assign_uid:{$saler_info['uid']}\n";
                $to_im_info = UserModel::instance()->getUser($saler_info['im_id']);
                if(!empty($to_im_info)) {
                    $to_im_id = (string)$to_im_info['_id'];
                }
            }
            if(empty($to_im_info)) {
                return ResultRet::result_error(Error::NO_AVAILABLE_SALER);
            }
        } else {
            $to_im_info = UserModel::instance()->getUser($to_im_id);
            if(empty($to_im_info)) {
                return ResultRet::result_error(Error::CHAT_USER_NOT_EXIST);
            }
        }
        $chat_model = ChatModel::instance();
        $chat_info = $chat_model->getChatByUser($im_id, $to_im_id);

        if(empty($chat_info)) {
            $chat_id = $chat_model->createChat($im_id, $to_im_id);
//            if($chat_id) {
//                $chat_info = $chat_model->getChat($chat_id);
//            }
        } else {
            $chat_id = (string)$chat_info['_id'];
        }
        if(empty($chat_id)) {
            return ResultRet::result_error(Error::INIT_CHAT_FAILED);
        } else {
            return $this->initChatById($server, $frame, $im_id, $chat_id);
        }

//        $connect = Connect::instance();
//        $to_online = $connect->isOnline($to_im_id);
//        $to_fd = Connect::instance()->getOnlineUserFd($to_im_id);
//        $connect_id = "";
//        if($to_online) {
//            $channel_info = $connect->getChatChannel($chat_id);
//            if(!empty($channel_info)) {
//                $connect_id = $channel_info['connect_id'];
//            } else {
//                $connect_id = $chat_model->createChatConnect($chat_id, $im_id, $to_im_id);
//
//                echo "[addChannel]\n";
//                $connect->addChannel($connect_id, $chat_id, $im_id,
//                    $to_im_id, $frame->fd, Connect::instance()->getOnlineUserFd($to_im_id),
//                    $im_id, $to_im_id);
//                echo "[addChannel] fd:{$frame->fd} im_id:{$im_id} chat_id:{$chat_id}\n";
//
//                // 添加双方会话列表
//                echo "ChatList:addChat:".$im_id."_".$chat_id."_".$to_im_id."\n";
//                ChatList::instance()->addChat($im_id, $chat_id, $to_im_id, time());
//                ChatList::instance()->addChat($to_im_id, $chat_id, $im_id, time());
//            }
//
//            // 发送聊天建立通知
//            $this->sendChatNotify($server, $to_fd, $chat_id, $connect_id, $to_im_info);
//
//            // 通知刷新会话列表
//            $this->sendChatList($server, $to_fd, $to_im_id);
//        }
//
//        $data = [
//            'chat_id' => $chat_id,
//            'connect_id' => $connect_id,
//            'chat_info' => $chat_info,
//            'to_im_info' => $to_im_info,
//            'to_fd' => $to_fd,
//            'to_online' => $to_online,
//        ];
//        return ResultRet::result_success($data);
    }

    /**
     * 初始化聊天
     * @param $im_id
     * @param string $to_im_id
     * @return ResultRet
     */
    public function initChatById($server, $frame, $im_id, $chat_id)
    {
        $chat_model = ChatModel::instance();
        $user_model = UserModel::instance();
        $chat_info = $chat_model->getChat($chat_id);
        if(empty($chat_info)) {
            return ResultRet::result_error(Error::CHAT_NOT_EXIST);
        }
        if($im_id == $chat_info['im_id1']) {
            $to_im_id = $chat_info['im_id2'];
        } elseif($im_id == $chat_info['im_id2']) {
            $to_im_id = $chat_info['im_id1'];
        } else {
            return ResultRet::result_error(Error::USER_NOT_IN_CHAT);
        }
        $im_info = $user_model->getUser($im_id);
        $to_im_info = $user_model->getUser($to_im_id);

        $connect = Connect::instance();
        $to_online = $connect->isOnline($to_im_id);
        $to_fd = Connect::instance()->getOnlineUserFd($to_im_id);
        $connect_id = "";
        if($to_online) {
            $channel_info = $connect->getChatChannel($chat_id);
            if(!empty($channel_info)) {
                $connect_id = $channel_info['connect_id'];
            } else {
                $connect_id = $chat_model->createChatConnect($chat_id, $im_id, $to_im_id);

                echo "[addChannel]\n";
                $connect->addChannel($connect_id, $chat_id, $im_id,
                    $to_im_id, $frame->fd, Connect::instance()->getOnlineUserFd($to_im_id),
                    $im_id, $to_im_id);
                echo "[addChannel] fd:{$frame->fd} im_id:{$im_id} chat_id:{$chat_id}\n";

                // 添加双方会话列表
                echo "ChatList:addChat:".$im_id."_".$chat_id."_".$to_im_id."\n";
                ChatList::instance()->addChat($im_id, $chat_id, $to_im_id, time());
                ChatList::instance()->addChat($to_im_id, $chat_id, $im_id, time());
            }

            // 发送聊天建立通知
            $this->sendChatNotify($server, $to_fd, $to_online, $chat_id, $connect_id, $im_info);

            // 通知刷新会话列表
            $this->sendChatList($server, $to_fd, $to_im_id);
        }

        $data = [
            'chat_id' => $chat_id,
            'connect_id' => $connect_id,
            'chat_info' => $chat_info,
            'to_im_info' => $to_im_info,
            'to_fd' => $to_fd,
            'to_online' => $to_online,
        ];
        return ResultRet::result_success($data);
    }

    /**
     * 分配客服
     */
    public function getServiceSaler($im_id, $exclude_im_ids=[])
    {
        if(!empty($exclude_im_ids) && !is_array($exclude_im_ids)) {
            $exclude_im_ids = [$exclude_im_ids];
        }
        $im_info = UserModel::instance()->getUser($im_id);
        $uid = $im_info ? $im_info['uid'] : 0;
        echo "[assign router] im_id:{$im_id} uid:{$uid}\n";
        $router = [
            // 李航
            7170569 => 1057866,
            12236516 => 1057866,

            // 张旭
            10000900 => 11261287,
            10000980 => 11261287,

        ];
        $connect = Connect::instance();
        $saler_list = $connect->getOnlineSalerList();
        $saler_info = false;
        if(!empty($saler_list)) {
            if(isset($router[$uid])) {
                echo "[assign router] uid:{$uid} to_uid:{$router[$uid]}\n";
                foreach ($saler_list as $v) {
                    if(!empty($exclude_im_ids) && in_array($v['im_id'],$exclude_im_ids)) {
                        continue;
                    }
                    if($v['uid'] == $router[$uid]) {
                        $saler_info = $v;
                        echo "[assign router] success\n";
                    }
                }
            } else {
                $uids = [];
                foreach ($saler_list as $v) {
                    if(!empty($exclude_im_ids) && in_array($v['im_id'],$exclude_im_ids)) {
                        continue;
                    }
                    $uids[] = $v['uid'];
                }

                //TODO 调用接口
                $rand_key = array_rand($saler_list);
                $saler_info = $saler_list[$rand_key];
            }
        }
        return $saler_info;
    }

    // 通知对方聊天已建立
    public function sendChatNotify($server, $to_fd, $to_online, $chat_id, $connect_id, $from_im_info)
    {
        $notify_data = [
            'chat_id' => $chat_id,
            'connect_id' => $connect_id,
            'im_id' => (string)$from_im_info['_id'],
            'uid' => $from_im_info['uid'],
            'nickname' => $from_im_info['nickname'],
            'avatar' => $from_im_info['avatar'],
            'user_type' => $from_im_info['user_type'],
            'online' => $to_online,
        ];
        Response::instance()->send($server, $to_fd, 'recv_chat_notify', $notify_data);
    }

    public function sendChatList($server, $to_fd, $to_im_id)
    {
        $chat_list_data = ChatList::instance()->getListData($to_im_id);
        Response::instance()->send($server, $to_fd, 'recv_chat_list', $chat_list_data);
    }



}