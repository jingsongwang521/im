<?php
namespace app\system\controller;

use app\system\logic\test\TestWsFrame;
use app\system\logic\test\TestWsServer;
use app\websocket\handle\ActionHandle;
use app\websocket\logic\ChatList;
use app\websocket\logic\Connect;
use app\websocket\model\UserModel;
use http\Client\Curl\User;

class IndexController extends BaseController
{

    public function index()
    {
        return 'System Index';
    }

    public function test_init_client()
    {
        $connect = Connect::instance();
        $user_model = UserModel::instance();
        $im_info = $user_model->getUserByUid(32);
        $connect->refreshOnline(1, $im_info);

        $data = '{
            "action" : "init_client", 
            "data":{
                "im_id" : "", 
                "uid" : 123, 
                "nickname" : "张三",
                "avatar" : "http://xxx.jpg",
                "user_type" :1, 
                "page_title" : "BIM高级工程师——从BIM建筑模型制作到BIM施工三维动画", 
                "page_url" : "https://edu.zhulong.com/lesson/8461-1.html#f=edu_syzb_0_4"
            }
        }';
        $server = new TestWsServer();
        $frame = new TestWsFrame();
        $frame->fd = 999;
        $frame->data = $data;
        $action_handle = new ActionHandle();
        $result = $action_handle->handle($server, $frame);
    }

    public function test_init_chat()
    {
        $data = '{
            "action" : "init_chat", 
            "data":{
                "im_id" : "5f44d9c4a04c882d848b4299",
                "to_im_id" : "5f44d9c4a04c882d848b42a1"
            }
        }';
        $server = new TestWsServer();
        $frame = new TestWsFrame();
        $frame->fd = 999;
        $frame->data = $data;
        $action_handle = new ActionHandle();
        $result = $action_handle->handle($server, $frame);
    }

    public function test_send_msg()
    {
        $data = '{
            "action" : "send_msg",
            "data":{
                "chat_id":"xxxxxxxxxxxxxxx",
                "from_im_id" : "xxxxxxxxxxxx",
                "to_im_id" : "xxxxxxxxxxxxx",
                "client_msg_id" : "xxxxxxxxxxxxxx",
                "msg_type" : "text",
                "content" : {
                    "text" : "发送消息格式"
                },
                "extra" : {}
            }
        }';
        $server = new TestWsServer();
        $frame = new TestWsFrame();
        $frame->fd = 999;
        $frame->data = $data;
        $action_handle = new ActionHandle();
        $result = $action_handle->handle($server, $frame);
    }

    public function test_chat_list()
    {
        $chat_list = ChatList::instance();
        $result = $chat_list->addChat('im111', 'chat111', '5f46060c37a7853ac341e4c2', time());
        var_dump($result);
        $result = $chat_list->addChat('im111', 'chat222', '5f45fc7a6df7cf3754c8d83d', time()+2);
        var_dump($result);
        $result = $chat_list->addChat('im111', 'chat333', 'im666', time()+4);
        var_dump($result);

        $result = $chat_list->removeChat('im111', 'chat111', 'im222');
        var_dump($result);

        $result = $chat_list->getList('im111');
        var_dump($result);

        $result = $chat_list->getListData('im111');
        var_dump($result);
    }




}
