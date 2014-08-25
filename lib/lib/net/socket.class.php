<?php
class Socket{
	protected
		$socket=false,
		$connected=false,
		$canread=false,
		$canwrite=false,
		$readlen=2048,
		$readtype=PHP_BINARY_READ;
	public function __construct($socket=false){
		$this->socket=$socket;
	}
	public function isConnected(){
		return $this->connected!==false;
	}
	public function connect($address,$port=0){
		if($this->connected)return true;
		if(!$this->socket && !$this->createSocket())return false;
		return $this->canread=$this->canwrite=$this->connected=socket_connect($address,$port);
	}
	public function create($domain=AF_INET,$type=SOCK_STREAM,$protocol=SOL_TCP){
		if($this->socket!==false)
			socket_close($this->socket);
		$this->socket=socket_create($domain,$type,$protocol);
		return $this->socket!==false;
	}
	public function close(){
		if(!$this->socket)return;
		$this->canread=$this->canwrite=$this->connected=false;
		socket_close($this->socket);
	}
	public function set($level, $name,$value){
		return socket_set_option($this->socket,$level,$name,$value);
	}
	public function get($level,$name){
		return socket_get_option($this->socket,$level,$name);
	}
	public function lastError(){
		return socket_last_error($this->socket);
	}
	public function clearError(){
		socket_clear_error($this->socket);
		return $this;
	}
	public function shutdown(){
		if(!$this->connected)return true;
		$this->connected=!socket_shutdown($this->socket);
		return !$this->connected;
	}
	public function shutdownInput(){
		$this->canread=!socket_shutdown($this->socket,0);
		$this->connected= $this->canread|$this->canwrite;
		return !$this->canread;
	}
	public function shutdownOutput(){
		$this->canwrite=!socket_shutdown($this->socket,1);
		$this->connected= $this->canread|$this->canwrite;
		return !$this->canwrite;
	}
	/**
	 * See socket_cmsg_space
	 * @param int $level
	 * @param int $type
	 */
	public function calcBufferSize($level,$type){
		return socket_cmsg_space($level,$type);
	}
	public function bind($address,$port=0){
		return socket_bind($this->socket,$address,$port);
	}
	public function setBlocking($bool){
		if($bool)
			return socket_set_block($this->socket);
		else
			return socket_set_nonblock($this->socket);
	}
	public function read($len=false,$type=false){
		return socket_read($this->socket,$len?$len:$this->readlen,$type?$type:$this->readtype);
	}
	public function send($buf,$len=-1,$flags=0){
		if($flags&MSG_EOF==MSG_EOF)$this->canwrite=false;
		if($len==-1)$len=strlen($buf);
		return socket_send($buf,$len,$flags);
	}
	public function write($buf,$len=0){
		return socket_write($this->socket,$buf,$len);
	}
}
class ServerSocket extends Socket{
	/**
	 * See socket_create_listen
	 * @param int $port
	 * @param int $queuelen
	 * @return boolean
	 */
	public function createServerSocket($port,$queuelen=SOMAXCONN){
		if($this->socket)socket_close($this->socket);
		return ($this->socket=socket_create_listen($port,$queuelen))!==false;
	}
	public function accept(){
		$sock=socket_accept($this->socket);
		if($sock===false)return false;
		return new Socket($sock);
	}
}