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

    /**
     * 刷新在线列表
     * @param $fd
     * @param $im_info
     */
    public function refreshOnline($fd, $im_info)
    {
        if(!isset(WebSocketServer::$online_list[(string)$im_info['_id']])) {
            WebSocketServer::$online_list[(string)$im_info['_id']] = [
                'fd' => $fd,
                'im_id' => (string)$im_info['_id'],
                'uid' => $im_info['uid'],
                'user_type' => $im_info['user_type'],
                'online_time' => time(),
                'last_time' => time(),
            ];
        } else {
            WebSocketServer::$online_list[(string)$im_info['_id']] = [
                'fd' => $fd,
                'im_id' => (string)$im_info['_id'],
                'uid' => $im_info['uid'],
                'user_type' => $im_info['user_type'],
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