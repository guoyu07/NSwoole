<?php
/**
 * Created by guoyu.
 * User: zhangguoyu
 * Date: 9/26/15
 * Time: 6:25 PM
 */
$serv = new swoole_websocket_server("0.0.0.0",9527,SWOOLE_PROCESS,SWOOLE_SOCK_TCP|SWOOLE_SSL);
$serv->set(array(
    "reactor_num" => 4,
    "worker_num" => 4,
    "max_request" => 50,
    "daemonize" => 1,
    "log_file" => "/tmp/swoole.log",
    "ssl_cert_file" => "/etc/nginx/dev.crt",
    "ssl_key_file" => "/etc/nginx/bz-inc.com.key",
    'heartbeat_idle_time' => 600, // 正式上线改为30
    'heartbeat_check_interval' => 30 // 正式上线改为5
));

$serv->on('Start',function(swoole_websocket_server $server){
    swoole_set_process_name('php swoole_websocket.php:master');
});

$serv->on('ManagerStart',function(swoole_websocket_server $server){
    swoole_set_process_name('php swoole_web_socket.php:manager');
});

$serv->on('WorkerStart',function(swoole_websocket_server $server,$worker_id){
    if($worker_id >= $server->setting['worker_num']){
        swoole_set_process_name('php swoole_websocket.php:task worker ' . $worker_id);
    }else{
        swoole_set_process_name('php swoole_websocket.php:event worker ' . $worker_id);
    }
});


$serv->on('Open', function(swoole_websocket_server $server,$req){
    echo date('Ymd H:i:s') . " connection open: " . $req->fd;
    echo "\n";

    // 处理断开非法连接和发送欢迎信息
    if($req->server["path_info"] != '/im'){
        echo 'illegal client connection close';
        $server->close($req->fd);
    }else{
        $welcome_array = array(
            'msg_type' => 'welcome',
            'server_id' => '001',
            'msg_content' => '欢迎登录IM'
        );
        $server->push($req->fd,json_encode($welcome_array));
        // 定时器
        // 用户必须在10s内认证身份，发一个auth包，否则断开连接
        swoole_timer_tick(1000000,function(swoole_websocket_server $server,$req){
            $server->close($req->fd);
        });
    }
});


$serv->on('Message',function(swoole_websocket_server $server,swoole_websocket_frame $frame){
    //echo date('Ymd H:i:s') . " message: " . $frame->data;
    //$server->push($frame->fd, json_encode(["hello","world"]));
    //echo "\n";
    // 检查文件是否发送完成（超过
    if($frame->finish != 1){
        // 之后传文件要用到，从这里开始处理
    }



    if($frame->opcode == WEBSOCKET_OPCODE_BINARY){
        // 之后传文件要用到，从这里开始处理
    }else{
        // 处理ping请求
        if(strlen($frame->data) > 6 && substr($frame->data,0,5) == 'ping:'){
            $ping_str = substr($frame->data, 5,strlen($frame->data)-5);
            $server->push($frame->fd,'resp:' . $ping_str);
        }else{
            // 处理json包

            $received_message_array = json_decode($frame->data,true);
            if(!$received_message_array){
                // 客户端发送的JSON字符串格式不对，IM服务器断开连接
                $server->push($frame->fd,'msg_content format wrong');
                $server->close($frame->fd);
            }else{
                if($received_message_array['msg_type'] == 'offline'){
                    // IM服务器断开连接
                    $server->push($frame->fd,'goodbye');
                    $server->close($frame->fd);

                }elseif($received_message_array['msg_type'] == 'auth'){
                    //if()
                }elseif($received_message_array['msg_type'] == 'report'){
                    // 根据msg_content中用户的ID，IM服务器将所有未度消息的状态置为已读
                    // 到数据库中执行更新操作
                }elseif($received_message_array['msg_type'] == 'chat'){
                    // 处理普通聊天消息
                    // 1. 检查cd时间（同一个人间隔1s，不同的人间隔30s）
                    // 1.1 检查违禁敏感词，否则悄悄丢弃
                    // 2. 查找到收信人是否在线，如果不在线则直接存储到数据库
                    // 3. 如果收信人在同一台IM服务器且在线，则直接转发给他，并且存储到数据库
                    // 4. 如果收信人不在同一台IM服务器且在线，则转发到中转服务器，由中转服务器进行转发，并存储到数据库
                    // 5.
                }else{
                    // 同步聊天记录
                    // msg_content是用户的最后一条消息的ID
                    //
                }
            }
        }
    }

});



$serv->on('Close',function(swoole_websocket_server $server,$fd){
    echo date('Ymd H:i:s') . " connection close: " . $fd;
    echo "\n";
});




$serv->start();