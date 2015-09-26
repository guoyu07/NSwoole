<?php
// Server
class Server{
	private $serv;
	
	public function __construct(){
		$this->serv = new swoole_server("0.0.0.0",9501);
		$this->serv->set(array(
			'worker_num' => 8, // worker进程数
			'daemonize' => 0, // uint32_t, 是否守护进程化
			'max_request' => 10000, // worker进程的最大任务数
			'dispatch_mode' => 2, // 数据包分发策略，默认为2（固定模式）
			'debug_mode' => 1 // 无效参数，可以传入，不会执行
		));
		
		$this->serv->on('Start',array($this,'onStart'));
		$this->serv->on('Connect',array($this,'onConnect'));
		$this->serv->on('Receive',array($this,'onReceive'));
		$this->serv->on('Close',array($this,'onClose'));
		
		$this->serv->start();
	}
	// onStart回调在server运行前被调用
	public function onStart($serv){ 
		echo "Start\n";
	}
	// onConnect在有新客户端连接过来时被调用
	public function onConnect($serv,$fd,$from_id){ 
		$serv->send( $fd,"Hello {$fd}!" );
	}
	// onReceive函数在有数据发送到server时被调用
	public function onReceive(swoole_server $serv,$fd,$from_id,$data){
		echo "Get Message From Client {$fd}:{$data}\n";
	}
	// onClose在有客户端断开连接时被调用
	public function onClose($serv, $fd, $from_id){
		echo "Client {$fd} close connection\n";
	}
}

// 启动服务器
$server = new Server();
?>
