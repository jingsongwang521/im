<?php


namespace app\websocket\logic;

use app\websocket\WebSocketServer;
use app\common\lib\traits\SingletonTrait;

class Connect
{
    use SingletonTrait;

    public function __construct()
    {
    }

    public function getChannelList()
    {
        return WebSocketServer::$channel_list;
    }

    public function getOnlineList()
    {
        return WebSocketServer::$online_list;
    }

    /**
     * 建立通道
     * @param $connect_id
     * @param $chat_id
     * @param $im_id1
     * @param $im_id2
     * @param $fd1
     * @param $fd2
     * @param $from_im_id
     * @param $to_im_id
     */
    public function addChannel($connect_id, $chat_id, $im_id1, $im_id2, $fd1, $fd2, $from_im_id, $to_im_id)
    {
        WebSocketServer::$channel_list[$connect_id] = [
            'chat_id' => $chat_id,
            'connect_id' => $connect_id,
            'im_id1' => $im_id1,
            'im_id2' => $im_id2,
            'fd1' => $fd1,
            'fd2' => $fd2,
            'from_im_id' => $from_im_id,
            'to_im_id' => $to_im_id,
            'connect_time' => time(),
            'last_time' => time()
        ];
    }

    /**
     * 更新通道最近通信时间戳
     */
    public function updateChannelLastTime($connect_id)
    {
        if(isset(WebSocketServer::$channel_list[$connect_id])) {
            WebSocketServer::$channel_list[$connect_id]['last_time'] = time();
        }
    }

    public function removeChannel($im_id, $fd=0)
    {
        $remove_keys = [];
        foreach (WebSocketServer::$channel_list as $k => $v) {
            if($fd == 0) {
                if($v['im_id1'] == $im_id || $v['im_id2'] == $im_id) {
                    $remove_keys[] = $k;
                }
            } else {
                if(($v['im_id1'] == $im_id && $v['fd1'] != $fd)
                    || ($v['im_id2'] == $im_id && $v['fd2'] != $fd)
                ) {
                    $remove_keys[] = $k;
                }
            }
        }
        if(!empty($remove_keys)) {
            foreach ($remove_keys as $v_key) {
                unset(WebSocketServer::$channel_list[$v_key]);
            }
            return count($remove_keys);
        } else {
            return 0;
        }
    }

    public function removeChannelByFd($fd)
    {
        $remove_keys = [];
        foreach (WebSocketServer::$channel_list as $k => $v) {
            if($v['fd1'] == $fd || $v['fd2'] == $fd) {
                $remove_keys[] = $k;
            }
        }
        if(!empty($remove_keys)) {
            foreach ($remove_keys as $v_key) {
                unset(WebSocketServer::$channel_list[$v_key]);
            }
            return count($remove_keys);
        } else {
            return false;
        }
    }

    /**
     * 刷新在线列表
     * @param $fd
     * @param $im_info
     */
    public function refreshOnline($fd, $im_info)
    {
        $im_id = (string)$im_info['_id'];
        $this->removeChannel($im_id, $fd);

        if(!isset(WebSocketServer::$online_list[$im_id])) {
            WebSocketServer::$online_list[$im_id] = [
                'fd' => $fd,
                'im_id' => $im_id,
                'uid' => $im_info['uid'],
                'nickname' => $im_info['nickname'],
                'avatar' => $im_info['avatar'],
                'user_type' => $im_info['user_type'],
                'online_time' => time(),
                'last_time' => time(),
            ];
        } else {
            $online_time = WebSocketServer::$online_list[$im_id]['online_time'];
            WebSocketServer::$online_list[$im_id] = [
                'fd' => $fd,
                'im_id' => (string)$im_info['_id'],
                'uid' => $im_info['uid'],
                'nickname' => $im_info['nickname'],
                'avatar' => $im_info['avatar'],
                'user_type' => $im_info['user_type'],
                'online_time' => $online_time,
                'last_time' => time(),
            ];
        }
    }



    /**
     * 删除在线列表
     * @param $fd
     * @param $im_info
     */
    public function removeOnline($im_id)
    {
        unset(WebSocketServer::$online_list[$im_id]);
    }

    /**
     * 删除在线列表
     * @param $fd
     * @param $im_info
     */
    public function removeOnlineByFd($fd)
    {
        $remove_keys = [];
        foreach (WebSocketServer::$online_list as $k => $v) {
            if($v['fd'] == $fd) {
                $remove_keys[] = $k;
            }
        }
        if(!empty($remove_keys)) {
            foreach ($remove_keys as $v_key) {
                unset(WebSocketServer::$online_list[$v_key]);
            }
            return count($remove_keys);
        } else {
            return 0;
        }
    }

    public function isOnline($im_id) {
        return isset(WebSocketServer::$online_list[$im_id]) ? true : false;
    }

    public function getOnlineSalerList()
    {
        $saler_list = [];
        foreach (WebSocketServer::$online_list as $v) {
            if($v['user_type'] == 3) {
                $saler_list[] = $v;
            }
        }
        return $saler_list;
    }

    public function getChatChannel($chat_id)
    {
        $channel_info = [];
        foreach (WebSocketServer::$channel_list as $v) {
            if($v['chat_id'] == $chat_id) {
                $channel_info = $v;
            }
        }
        return $channel_info;
    }

    public function getChatConncetId($chat_id)
    {
        $channel_info = $this->getChatChannel($chat_id);
        if(!empty($channel_info)) {
            $connect_id = $channel_info['connect_id'];
        } else {
            $connect_id = '';
        }
        return $connect_id;
    }


    public function getOnlineUserFd($im_id)
    {
        $fd = 0;
        foreach (WebSocketServer::$online_list as $v) {
            if($v['im_id'] == $im_id) {
                $fd = $v['fd'];
            }
        }
        return $fd;
    }

    public function getOnlineImIdByfd($fd)
    {
        $im_id = '';
        foreach (WebSocketServer::$online_list as $v) {
            if($v['fd'] == $fd) {
                $im_id = $v['im_id'];
                break;
            }
        }
        return $im_id;
    }
}