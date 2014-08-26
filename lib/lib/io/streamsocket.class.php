<?php
require_once 'stream.class.php';
abstract class StreamSocket extends Stream{
	protected $canread=false,$canwrite=false;
	protected $error_number=0,$error_string='';
	/**
	 * Error number from the connect
	 * @return number
	 */
	public function getError(){return $this->error_number;}
	/**
	 * Error string from the connect
	 * @return string
	 */
	public function getErrorStr(){return $this->error_string;}
	/**
	 * stream_socket_shutdown($this->handle,STREAM_SHUT_RD)
	 * @return boolean
	 */
	public function shutdownInput(){
		$this->canread=!stream_socket_shutdown($this->handle,STREAM_SHUT_RD);
		return !$this->canread;
	}
	/**
	 * stream_socket_shutdown($this->handle,STREAM_SHUT_WR)
	 * @return boolean
	 */
	public function shutdownOutput(){
		$this->canwrite=!stream_socket_shutdown($this->handle,STREAM_SHUT_WR);
		return !$this->canwrite;
	}
	/**
	 * stream_socket_shutdown($this->handle,STREAM_SHUT_RDWR)
	 * @return boolean
	 */
	public function shutdown(){
		$res=stream_socket_shutdown($this->handle,STREAM_SHUT_RDWR);
		if($res)
			$this->canread=$this->canwrite=false;
		return $res;
	}
	/**
	 * @see stream_socket_enable_crypto()
	 * @param bool $enable
	 * @param string $type
	 * @param string $session
	 * @return mixed true on success, false if negotiation has failed or 0 if there isn't enough data and you should try again (only for non-blocking sockets).
	 */
	public function enableCrypto($enable,$type=false,$session=false){
		if($session)
			return stream_socket_enable_crypto($this->handle,$enable,$type,$session);
		elseif($type!==false)
			return stream_socket_enable_crypto($this->handle,$enable,$type);
		return stream_socket_enable_crypto($this->handle,$enable);
	}
	/**
	 * @see stream_socket_get_name()
	 * @return string
	 */
	public function getLocalName(){return stream_socket_get_name($this->handle,false);}
	/**
	 * @see stream_socket_get_name()
	 * @return string
	 */
	public function getRemoteName(){return stream_socket_get_name($this->handle,true);}
	/**
	 * @see stream_socket_recvfrom()
	 * @param int $len
	 * @param number $flags
	 * @param string $from
	 * @return string
	 */
	public function recv($len,$flags=0,&$from=''){
		return stream_socket_recvfrom($this->handle,$len,$flags,$from);
	}
	/**
	 * @see stream_socket_sendto()
	 * @param string $data
	 * @param number $flags
	 * @param string $to
	 * @return number
	 */
	public function send($data,$flags=0,$to=null){
		if($to)
			return stream_socket_sendto($this->handle,$data,$flags,$to);
		return stream_socket_sendto($this->handle,$data,$flags);
	}
	/**
	 * false if not connected or if input stream is shutdown.
	 * Do not use this to check for read errors.
	 * @return boolean
	 */
	public function canRead(){
		return $this->canread;
	}
	/**
	 * false if not connected or if output stream is shutdown.
	 * Do not use this to check for write errors.
	 * @return boolean
	 */
	public function canWrite(){
		return $this->canwrite;
	}
}
class StreamSocketClient extends StreamSocket{
	/**
	 * @see stream_socket_client()
	 * @see Stream::open()
	 */
	public function open($flags=STREAM_CLIENT_CONNECT){
		if($this->ctx)
			$this->handle=stream_socket_client($this->uri,$this->error_number,$this->error_string,$flags,$this->ctx);
		else
			$this->handle=stream_socket_client($this->uri,$this->error_number,$this->error_string,$flags);
		if($this->handle)$this->open=true;
		return $this->open;
	}
}
define('STREAM_SERVER_DEFAULT',STREAM_SERVER_BIND | STREAM_SERVER_LISTEN);
class StreamSocketServer extends StreamSocket{
	/**
	 * @see stream_socket_server()
	 * @see Stream::open()
	 */
	public function open($flags=STREAM_SERVER_DEFAULT){
		if($this->ctx)
			$this->handle=stream_socket_server($this->uri,$this->error_number,$this->error_string,$flags,$this->ctx);
		else
			$this->handle=stream_socket_server($this->uri,$this->error_number,$this->error_string,$flags);
		if($this->handle)$this->open=true;
		return $this->open;
	}
	public function accept($timeout=-2,&$peername=''){
		if($timeout==-2)$timeout=ini_get("default_socket_timeout");
		return stream_socket_accept($this->handle,$timeout,$peername);
	}

}