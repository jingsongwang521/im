<?php

namespace app\websocket;

use app\common\lib\Redis;
use app\websocket\handle\ActionHandle;

use think\Db;
use think\facade\Config;
use think\swoole\Server;

class WebSocketServer extends Server
{
    protected $host = '0.0.0.0';
    protected $serverType = 'socket';
    protected $port = 9502;
    protected $option = [
        'worker_num'=> 1,
        'daemonize'	=> false,
        'backlog'	=> 128,
        'file_monitor' => true, // 开启文件监控
        'file_monitor_interval' => 1, // 文件监控检测的时间间隔
    ];

    /**
     * 通道列表（保存会话连接）
     * @var array
     */
    public static $channel_list = [];

    /**
     * 在线列表（保存在线用户）
     * @var array
     */
    public static $online_list = [];

    /**
     * 主进程启动
     * @param $server
     */
    public function onStart($server)
    {
        echo "onStart: Swoole Start\n";
    }

    /**
     * 此事件在Worker进程/Task进程启动时发生。这里创建的对象可以在进程生命周期内使用
     * @param $server
     * @param $worker_id
     */
    public function onWorkerStart($server, $worker_id){
        $this->server = $server;

        // 初始化redis
        Redis::instance();

        //初始化db
//        $mongo = Db::connect(Config::get('database.mongodb'));
//        Container::set('mongo', $mongo);

        echo "onWorkerStart: Worker {$worker_id}\n";
    }

    /**
     * 有新的连接进入时，在worker进程中回调
     * @param $server
     * @param $fd
     * @param $reactorId
     */
    public function onConnect($server, $fd, $reactorId)
    {
        echo "onConnect: fd{$fd} reactorId:{$reactorId}\n";
    }

    public function onOpen($server, $request)
    {
        echo "onOpen: fd{$request->fd}\n";
//        var_dump($request);
        echo "server: handshake success with fd{$request->fd}\n";
    }

    public function onMessage($server, $frame)
    {
        echo "onMessage:\n";
        echo "receive from {$frame->fd}:{$frame->data},opcode:{$frame->opcode},fin:{$frame->finish}\n";
//        $server->push($frame->fd, "this is server");

        ActionHandle::instance()->handle($server, $frame);
    }

    public function onClose($server, $fd) {
        echo "onClose:\n";
        echo "client {$fd} closed\n";

        ActionHandle::instance()->handleClose($server, $fd);
    }
}