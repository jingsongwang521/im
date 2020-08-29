<?php
/**
 * 会话列表
 */

namespace app\websocket\logic;

use app\common\lib\Redis;
use app\websocket\model\UserModel;

use app\common\lib\traits\SingletonTrait;

class ChatList
{
    use SingletonTrait;

    private $redis;

    public function __construct()
    {
        $this->redis = Redis::instance();
    }

    public function addChat($im_id, $chat_id, $to_im_id, $time)
    {
        $item = $chat_id .'_'. $to_im_id;
        return $this->redis->zAdd(Redis::keyChatList($im_id), $time, $item);
    }

    public function removeChat($im_id, $chat_id, $to_im_id)
    {
        $item = $chat_id .'_'. $to_im_id;
        return $this->redis->zRem(Redis::keyChatList($im_id), $item);
    }

    public function getList($im_id)
    {
        $list = $this->redis->zRange(Redis::keyChatList($im_id), 0, -1, true);
        return $list;
    }

    public function getListData($im_id)
    {
        $list_data = ['zhulong_list'=>[], 'other_list'=>[]];
        $im_id_arr = [];
        $user_model = UserModel::instance();
        $connect = Connect::instance();

        $list = $this->getList($im_id);
        if(!empty($list)) {
            $data = [];
            foreach ($list as $k => $v) {
                $item_arr = explode('_', $k);
                $data[] = [
                    'chat_id' => $item_arr[0],
                    'im_id' => $item_arr[1],
                    'last_time' => $v,
                ];
                $im_id_arr[] = $item_arr[1];
            }
            $im_arr = $user_model->getUserByIds($im_id_arr);
            foreach ($data as $v) {
                if(!isset($im_arr[$v['im_id']])) {
                    continue;
                }
                if(in_array($im_arr[$v['im_id']]['user_type'],[2,3])) {
                    $type = 'zhulong_list';
                } else {
                    $type = 'other_list';
                }
                $v['connect_id'] = $connect->getChatConncetId($v['chat_id']);
                $v['uid'] = $im_arr[$v['im_id']]['uid'];
                $v['nickname'] = $im_arr[$v['im_id']]['nickname'];
                $v['avatar'] = $im_arr[$v['im_id']]['avatar'];
                $v['user_type'] = $im_arr[$v['im_id']]['user_type'];
                $v['online'] = $connect->isOnline($v['im_id']) ? 1 : 0;
                $v['unread'] = 0;
                $list_data[$type][] = $v;
            }
        }
        return $list_data;
    }


}