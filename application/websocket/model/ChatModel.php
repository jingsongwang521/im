<?php


namespace app\websocket\model;

use app\common\lib\traits\SingletonTrait;
use MongoDB\BSON\ObjectId;
use think\Db;

class ChatModel
{
    use SingletonTrait;

    protected $table = 'zl_im_chat';

    public function __construct()
    {

    }

    /**
     * 聊天 im_id 排序
     * @param $im_id1
     * @param $im_id2
     * @return array
     */
    public function sortChatImId($im_id1, $im_id2)
    {
        if($im_id1 >  $im_id2) {
            $tmp = $im_id1;
            $im_id1 = $im_id2;
            $im_id2 = $tmp;
        }
        return ['im_id1'=>$im_id1, 'im_id2'=>$im_id2];
    }

    public function getChat($chat_id)
    {
        $info = Db::table($this->table)->where('_id', $chat_id)->findOrEmpty();
        return $info;
    }

    public function getChatByUser($im_id1, $im_id2)
    {
        $sortImIds = $this->sortChatImId($im_id1, $im_id2);
        $info = Db::table($this->table)->where('im_id1', $sortImIds['im_id1'])
            ->where('im_id2', $sortImIds['im_id2'])
            ->findOrEmpty();
//        echo Db::table($this->table)->getLastSql();
        return $info;
    }

    public function createChat($im_id1, $im_id2)
    {
        $sortImIds = $this->sortChatImId($im_id1, $im_id2);
        $chat_id = new ObjectId();
        $data = [
            '_id' => $chat_id,
            'im_id1' => $sortImIds['im_id1'],
            'im_id2' => $sortImIds['im_id2'],
            'create_time' => $_SERVER['REQUEST_TIME'],
            'update_time' => $_SERVER['REQUEST_TIME'],
        ];
        $result = Db::table($this->table)->insert($data);
        if($result !== false) {
            return $chat_id;
        } else {
            return false;
        }
    }

    public function createChatConnect($chat_id, $from_im_id, $to_im_id)
    {
        $chat_info = $this->getChat($chat_id);
        if(empty($chat_info)) {
            return false;
        }
        if(!in_array($from_im_id, [$chat_info['im_id1'], $chat_info['im_id2']])) {
            return false;
        }
        if(!in_array($to_im_id, [$chat_info['im_id1'], $chat_info['im_id2']])) {
            return false;
        }
        $connect_id = (string)new ObjectId();
        $data = [
            '_id' => $connect_id,
            'chat_id' => $chat_id,
            'im_id1' => $chat_info['im_id1'],
            'im_id2' => $chat_info['im_id2'],
            'from_im_id' => $from_im_id,
            'to_im_id' => $to_im_id,
            'create_time' => $_SERVER['REQUEST_TIME'],
            'update_time' => $_SERVER['REQUEST_TIME'],
        ];
        $result = Db::table('zl_im_chat_connect')->insert($data);
        if($result !== false) {
            return $connect_id;
        } else {
            return false;
        }
    }


}