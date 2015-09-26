<?php
// Client
class Client{
	private $client;
	
	public function __construct(){
		// 创建tcp socket
		$this->client = new swoole_client(SWOOLE_SOCK_TCP);
	}
	
	public function connect(){
		if(!$this->client->connect("121.40.126.146",9501,1)){
			echo "Error: {$fp->errMsg}[{$fp->errCode}]\n";
		}
		$message = $this->client->recv();
		echo "Get Message From Server: {$message}\n";
		
		fwrite(STDOUT,"请输入消息： ");
		$msg = trim(fgets(STDIN));
		$this->client->send($msg);
	}
}
$client = new Client();
$client->connect();
?>
