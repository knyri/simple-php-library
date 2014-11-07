<?php
/**
 * @package io
 */



/**
 * Base class for all IO classes
 * @author Ken
 */
class Stream{
	protected $handle,$uri,$use_include_path,$ctx;
	protected $open=false,$closed=false,$locked=false;
	/**
	 * @param string $uri
	 * @param string $use_include_path
	 * @param string $ctx
	 */
	public function __construct($uri,$use_include_path=false,$ctx=null){
		$this->uri=$uri;
		$this->use_include_path=$use_include_path;
		$this->ctx=$ctx;
	}

	/**
	 * Sets the context that will be passed to fopen()
	 * @param array $ctx
	 */
	public function setContext($ctx){$this->ctx=$ctx;}
	public function getContext(){return $this->ctx;}
	/**
	 * Sets what will be passed as the use_include_path arguement for fopen()
	 * @param boolean $bool
	 */
	public function setUseIncludePath($bool){$this->use_include_path=$bool;}
	public function getUseIncludePath(){return $this->use_include_path;}

	/**
	 * fopen()
	 * Stream context can be set using setContext()
	 * @param string $mode
	 * @return boolean
	 */
	public function open($mode){
		if($this->ctx)
			$this->handle=fopen($this->uri,$mode,$this->use_include_path,$this->ctx);
		else
			$this->handle=fopen($this->uri,$mode,$this->use_include_path);
		if($this->handle){
			$this->open=true;
			$this->closed=false;
		}
		return $this->open;
	}
	/**
	 * Unlocks and closes the stream
	 * flock() and fclose()
	 * @return boolean
	 */
	public function close(){
		if($this->isLocked())
			$this->unlock();
		$this->closed=fclose($this->handle);
		$this->open=!$this->closed;
		return $this->closed;
	}
	public function isOpen(){return $this->open;}
	public function isClosed(){return $this->closed;}
	/**
	 * flock()
	 * @param int $operation
	 * 		LOCK_SH to acquire a shared lock (reader).
	 * 		LOCK_EX to acquire an exclusive lock (writer).
	 * Do not pass LOCK_UN. Use unlock()
	 * 		LOCK_UN to release a lock (shared or exclusive).
	 * @param int $wouldBlock
	 * @return boolean
	 */
	public function lock($operation,&$wouldBlock=null){
		if($wouldBlock==null)
			$this->locked= flock($this->handle,$operation);
		else
			$this->locked= flock($this->handle,$operation,$wouldBlock);
		return $this->locked;
	}
	/**
	 * fgetc()
	 * @return string|boolean returns false on EOF
	 */
	public function getc(){return fgetc($this->handle);}
	/**
	 * fgets()
	 * @param number $len
	 * @return string|boolean returns false on EOF and errors
	 */
	public function gets($len=false){
		if($len)
			return fgets($this->handle,$len);
		return fgets($this->handle);
	}
	/**
	 * fscanf()
	 * @param string $len
	 * @param string $allowable_tags
	 * @return string
	 */
	public function getss($len=false,$allowable_tags=null){
		if($allowable_tags)
			return fgetss($this->handle,$len,$allowable_tags);
		elseif($len)
			return fgetss($this->handle,$len);
		return fgetss($this->handle);
	}

	/**
	 * fscanf()
	 * @param string $format
	 * @return mixed
	 */
	public function scanFormat($format){return fscanf($this->handle,$format);}
	/**
	 * fread()
	 * @param number $len
	 * @return string
	 */
	public function read($len=1){return fread($this->handle,$len);}
	/**
	 * flock()
	 * @param string $wouldBlock
	 * @return boolean
	 */
	public function unlock(&$wouldBlock=null){$this->locked= !$this->lock(LOCK_UN,$wouldBlock);return !$this->locked;}
	public function isLocked(){return $this->locked;}
	/**
	 * ftell()
	 * @return number
	 */
	public function getPosition(){return ftell($this->handle);}
	/**
	 * fseek()
	 * @param number $offset
	 * @param number $start defaults to SEEK_CUR
	 * @return boolean
	 */
	public function seek($offset,$start=SEEK_CUR){$ret=fseek($this->handle,$offset,$start);return $ret===0;}
	/**
	 * rewind()
	 * @return boolean
	 */
	public function rewind(){return rewind($this->handle);}
	/**
	 * fwrite()
	 * @param string $string
	 * @param number $len
	 * @return number
	 */
	public function write($string,$len=-1){
		if($len==-1)
			return fwrite($this->handle,$string);
		else
			return fwrite($this->handle,$string,$len);
	}

	/**
	 * fflush()
	 * @return boolean
	 */
	public function flush(){return fflush($this->handle);}
	/**
	 * feof()
	 * @return boolean
	 */
	public function isEof(){return feof($this->handle);}
	/**
	 * ftruncate()
	 * @param number $size
	 * @return boolean
	 */
	public function truncate($size=0){return ftruncate($this->handle,$size);}
	/**
	 * filetype(...)
	 * @return string
	 */
	public 	function getType(){return filetype($this->uri);}
	/* ********************************
	 * stream_* functions
	 * ********************************/
	/**
	 * stream_encoding()
	 * @param string $charset
	 */
	public function setEncoding($charset){
		return stream_encoding($this->handle,$charset);
	}
	/**
	 * stream_copy_to_stream()
	 * @param resource|Stream $to
	 * @param number $maxlen
	 * @param number $offset
	 * @return number
	 */
	public function copyTo($to,$maxlen=-1,$offset=0){
		if(!is_resource($to))
			$to=$to->handle;
		return stream_copy_to_stream($this->handle,$to,$maxlen,$offset);
	}
	/**
	 * stream_filter_append()
	 * @param string $name
	 * @param string $read_write
	 * @param string $params
	 * @return resource
	 */
	public function appendFilter($name,$read_write=false,$params=false){
		if($params!==false)
			return stream_filter_append($this->handle,$name,$read_write,$params);
		elseif($read_write!==false)
			return stream_filter_append($this->handle,$name,$read_write);
		else
			return stream_filter_append($this->handle,$name);
	}
	/**
	 * stream_filter_prepend()
	 * @param string $name
	 * @param string $read_write
	 * @param string $params
	 * @return resource
	 */
	public function prependFilter($name,$read_write=false,$params=false){
		if($params!==false)
			return stream_filter_prepend($this->handle,$name,$read_write,$params);
		elseif($read_write!==false)
		return stream_filter_prepend($this->handle,$name,$read_write);
		else
			return stream_filter_prepend($this->handle,$name);
	}
	/**
	 * @see stream_get_contents()
	 * @param int $maxlen
	 * @param int $offset
	 * @return string
	 */
	public function getContents($maxlen=-1,$offset=-1){
		return stream_get_contents($this->handle,$maxlen,$offset);
	}
	/**
	 * stream_get_line()
	 * @param number $len
	 * @param string $ending
	 * @return string
	 */
	public function getLine($len=2048,$ending=null){
		if($ending==null)
			return stream_get_line($this->handle,$len);
		else
			return stream_get_line($this->handle,$len,$ending);
	}
	/**
	 * stream_get_meta_data()
	 * @return multitype:
	 */
	public function getMeta(){
		return stream_get_meta_data($this->handle);
	}
	/**
	 * stream_is_local()
	 * @return boolean
	 */
	public function isLocal(){
		return stream_is_local($this->handle);
	}
	/**
	 * stream_set_blocking()
	 * @param number $mode 0 for non-blocking and 1 for blocking
	 * @return boolean
	 */
	public function setBlocking($mode){
		return stream_set_blocking($this->handle,$mode);
	}
	/**
	 * stream_set_chunk_size()
	 * @param number $size
	 * @return number
	 */
	public function setChunkSize($size){
		return stream_set_chunk_size($this->handle,$size);
	}
	/**
	 * stream_set_read_buffer()
	 * @param number $size
	 * @return number
	 */
	public function setReadBuffer($size){
		return stream_set_read_buffer($this->handle,$size);
	}
	/**
	 * stream_set_write_buffer()
	 * @param number $size
	 * @return number
	 */
	public function setWriteBuffer($size){
		return stream_set_write_buffer($this->handle,$size);
	}
	/**
	 * stream_set_timeout()
	 * @param number $sec
	 * @param number $micro
	 * @return boolean
	 */
	public function setTimeout($sec,$micro=0){
		return stream_set_timeout($this->handle,$sec,$micro);
	}
	/**
	 * stream_supports_lock()
	 * @return boolean
	 */
	public function canLock(){
		return stream_supports_lock($this->handle);
	}
	/**
	 * fpassthru()
	 * @return number
	 */
	public function passthru(){
		return fpassthru($this->handle);
	}

}