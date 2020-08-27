<?php


namespace app\websocket\logic;

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
    public function initImUser($action_data)
    {
        $uid = $action_data['uid'];
        $im_id = $action_data['im_id'];
        $nickname = $action_data['nickname'];
        $avatar = $action_data['avatar'];
        $user_type = $action_data['user_type'];

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
    public function initChat($im_id, $to_im_id='')
    {
        if(empty($to_im_id)) {
            $saler_info = $this->getServiceSaler($im_id);
            if(!empty($saler_info)) {
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
            if($chat_id) {
                $chat_info = $chat_model->getChat($chat_id);
            }
        }
        if(empty($chat_info)) {
            return ResultRet::result_error(Error::INIT_CHAT_FAILED);
        }

        $chat_id = (string)$chat_info['_id'];
        $connect = Connect::instance();
        $channel_info = $connect->getChatChannel($chat_id);
        if(!empty($channel_info)) {
            $connect_id = $channel_info['connect_id'];
        } else {
            $connect_id = $chat_model->createChatConnect($chat_id, $im_id, $to_im_id);
        }

        // 添加双方会话列表
        echo "ChatList:addChat:".$im_id."_".$chat_id."_".$to_im_id."\n";
        ChatList::instance()->addChat($im_id, $chat_id, $to_im_id, time());
        ChatList::instance()->addChat($to_im_id, $chat_id, $im_id, time());

        $connect = Connect::instance();
        $data = [
            'chat_id' => $chat_id,
            'connect_id' => $connect_id,
            'chat_info' => $chat_info,
            'to_im_info' => $to_im_info,
            'to_fd' => Connect::instance()->getOnlineUserFd($to_im_id),
        ];
        return ResultRet::result_success($data);
    }

    /**
     * 分配客服
     */
    public function getServiceSaler($exclude_im_ids=[])
    {
        if(!empty($exclude_im_ids) && !is_array($exclude_im_ids)) {
            $exclude_im_ids = [$exclude_im_ids];
        }
        $connect = Connect::instance();
        $saler_list = $connect->getOnlineSalerList();
        if(!empty($saler_list)) {
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
        } else {
            $saler_info = false;
        }
        return $saler_info;
    }

}