<?php
class Stream{
	protected $handle,$uri,$use_include_path,$ctx;
	protected $open=false,$closed=false,$locked=false;
	public function __construct($uri,$use_include_path=false,$ctx=null){
		$this->uri=$uri;
		$this->use_include_path=$use_include_path;
		$this->ctx=$ctx;
	}

	public function setContext($ctx){$this->ctx=$ctx;}
	public function getContext(){return $this->ctx;}
	public function setUseIncludePath($bool){$this->use_include_path=$bool;}
	public function getUseIncludePath(){return $this->use_include_path;}

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
	public function getc(){return fgetc($this->handle);}
	public function gets($len=false){
		if($len)
			return fgets($this->handle,$len);
		return fgets($this->handle);
	}
	public function getss($len=false,$allowable_tags=null){
		if($allowable_tags)
			return fgetss($this->handle,$len,$allowable_tags);
		elseif($len)
			return fgetss($this->handle,$len);
		return fgetss($this->handle);
	}

	public function scanFormat($format){return fscanf($this->handle,$format);}
	public function read($len=1){return fread($this->handle,$len);}
	public function unlock(&$wouldBlock=null){$this->locked= !$this->lock(LOCK_UN,$wouldBlock);return !$this->locked;}
	public function isLocked(){return $this->locked;}
	public function getPosition(){return ftell($this->handle);}
	public function seek($offset,$start=SEEK_CUR){$ret=fseek($this->handle,$offset,$start);return $ret===0;}
	public function rewind(){return rewind($this->handle);}
	public function write($string,$len=-1){
		if($len==-1)
			return fwrite($this->handle,$string);
		else
			return fwrite($this->handle,$string,$len);
	}

	public function flush(){return fflush($this->handle);}
	public function isEof(){return feof($this->handle);}
	public function truncate($size=0){return ftruncate($this->handle,$size);}
	/*********************************
	 * stream_* functions
	 *********************************/
	public function setEncoding($charset){
		return stream_encoding($this->handle,$charset);
	}
	public function copyTo($to,$maxlen=-1,$offset=0){
		if(!is_resource($to))
			$to=$to->handle;
		return stream_copy_to_stream($this->handle,$to,$maxlen,$offset);
	}
	public function appendFilter($name,$read_write=false,$params=false){
		if($params!==false)
			return stream_filter_append($this->handle,$name,$read_write,$params);
		elseif($read_write!==false)
			return stream_filter_append($this->handle,$name,$read_write);
		else
			return stream_filter_append($this->handle,$name);
	}
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
	public function getLine($len=2048,$ending=null){
		if($ending==null)
			return stream_get_line($this->handle,$len);
		else
			return stream_get_line($this->handle,$len,$ending);
	}
	public function getMeta(){
		return stream_get_meta_data($this->handle);
	}
	public function isLocal(){
		return stream_is_local($this->handle);
	}
	public function setBlocking($mode){
		return stream_set_blocking($this->handle,$mode);
	}
	public function setChunkSize($size){
		return stream_set_chunk_size($this->handle,$size);
	}
	public function setReadBuffer($size){
		return stream_set_read_buffer($this->handle,$size);
	}
	public function setWriteBuffer($size){
		return stream_set_write_buffer($this->handle,$size);
	}
	public function setTimeout($sec,$micro=0){
		return stream_set_timeout($this->handle,$sec,$micro);
	}
	public function canLock(){
		return stream_supports_lock($this->handle);
	}
	public function passthru(){
		return fpassthru($this->handle);
	}

}