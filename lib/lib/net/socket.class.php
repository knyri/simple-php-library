<?php
/**
 *
 * Wrapper for all the socket_* functions
 *
 * @author Ken
 * @package io
 * @subpackage net
 *
 */
class Socket{
	protected
		$socket= false,
		$connected= false,
		$canread= false,
		$canwrite= false,
		$readlen= 2048,
		$readtype= PHP_BINARY_READ
	;
	/**
	 * @param resource $socket (false)
	 */
	public function __construct($socket= false){
		$this->socket= $socket;
	}
	public function setReadLen($len){
		$this->readlen= $len;
	}
	public function setReadType($type){
		$this->readtype= $type;
	}
	/**
	 * This function may be wrong if a socket was supplied to the constructor.
	 * This function also does not take in to account timeouts or disconnections
	 * from the remote end.
	 * @return boolean true if connected
	 */
	public function isConnected(){
		return $this->connected !== false;
	}
	/**
	 * @param string $address
	 * @param number $port (0)
	 * @return boolean
	 */
	public function connect($address, $port= 0){
		if($this->connected){
			return true;
		}
		if(!$this->socket && !$this->create()){
			return false;
		}
		$this->canread=
		$this->canwrite=
		$this->connected= socket_connect($this->socket, $address, $port);
		return $this->connected;
	}
	/**
	 * @param string $domain (AF_INET)
	 * @param string $type (SOCK_STREAM)
	 * @param string $protocol (SOL_TCP)
	 * @return boolean
	 */
	public function create($domain= AF_INET, $type= SOCK_STREAM, $protocol= SOL_TCP){
		if($this->socket !== false){
			socket_close($this->socket);
		}
		$this->socket= socket_create($domain, $type, $protocol);
		return $this->socket !== false;
	}
	public function close(){
		if(!$this->socket){
			return;
		}
		$this->canread=
		$this->canwrite=
		$this->connected= false;
		socket_close($this->socket);
	}
	public function set($level, $name, $value){
		return socket_set_option($this->socket, $level, $name, $value);
	}
	public function get($level, $name){
		return socket_get_option($this->socket, $level, $name);
	}
	public function errorStr(){
		return socket_strerror($this->lastError());
	}
	public function lastError(){
		if($this->socket){
			return socket_last_error($this->socket);
		}else{
			return socket_last_error();
		}
	}
	public function clearError(){
		if($this->socket){
			socket_clear_error($this->socket);
		}else{
			socket_clear_error();
		}
		return $this;
	}
	public function shutdown(){
		if(!$this->connected){
			return true;
		}
		$this->connected = !socket_shutdown($this->socket);
		return !$this->connected;
	}
	public function shutdownInput(){
		$this->canread= !socket_shutdown($this->socket, 0);
		$this->connected= $this->canread | $this->canwrite;
		return !$this->canread;
	}
	public function shutdownOutput(){
		$this->canwrite= !socket_shutdown($this->socket, 1);
		$this->connected= $this->canread | $this->canwrite;
		return !$this->canwrite;
	}
	/**
	 * See socket_cmsg_space
	 * @param int $level
	 * @param int $type
	 */
	public function calcBufferSize($level, $type){
		return socket_cmsg_space($level, $type);
	}
	/**
	 * @param string $address
	 * @param int $port (0)
	 * @return boolean
	 */
	public function bind($address, $port=0){
		return socket_bind($this->socket, $address, $port);
	}
	public function setBlocking($bool){
		if($bool){
			return socket_set_block($this->socket);
		}else{
			return socket_set_nonblock($this->socket);
		}
	}
	/**
	 * @param int $len (false)
	 * @param int $type (false)
	 * @return string
	 */
	public function read($len=false, $type=false){
		return socket_read($this->socket, $len ? $len : $this->readlen, $type ? $type : $this->readtype);
	}
	/**
	 * @param string $buf
	 * @param int $len (false)
	 * @param int $flags (0)
	 * @return int
	 */
	public function send($buf, $len=false,$flags=0){
		if(($flags & MSG_EOF) == MSG_EOF){
			$this->canwrite= false;
		}
		if($len === false){
			$len= strlen($buf);
		}
		return socket_send($this->socket, $buf, $len, $flags);
	}
	/**
	 * @param string $buf
	 * @param int $len (false)
	 * @return int the number of bytes successfully written to the socket or false on failure. The error code can be retrieved with socket_last_error. This code may be passed to socket_strerror to get a textual explanation of the error.
	 *	It is perfectly valid for socket_write to return zero which means no bytes have been written. Be sure to use the === operator to check for false in case of an error.
	 */
	public function write($buf, $len=false){
		if($len){
			return socket_write($this->socket, $buf, $len);
		}else{
			return socket_write($this->socket, $buf);
		}
	}
	public function sendTo($buf, $len, $flags, $addr, $port=0){
		return socket_sendto($this->socket, $buf, $len, $flags, $addr, $port);
	}
}
/**
 * @author Ken
 *
 */
class ServerSocket extends Socket{
	/**
	 * See socket_create_listen
	 * @param int $port
	 * @param int $queuelen (SOMAXCONN)
	 * @return boolean
	 */
	public function createServerSocket($port,$queuelen=SOMAXCONN){
		if($this->socket){
			socket_close($this->socket);
		}
		return ($this->socket= socket_create_listen($port, $queuelen)) !== false;
	}
	/**
	 * @return boolean|Socket A new Socket object or false if an error occured.
	 */
	public function accept(){
		$sock= socket_accept($this->socket);
		if($sock === false){
			return false;
		}
		return new Socket($sock);
	}
	/**
	 * @param int $queueLen (0)
	 * @return boolean
	 */
	public function listen($queueLen=0){
		return socket_listen($this->socket, $queueLen);
	}
}