<?php
require_once 'cachepart.class.php';

/**
 * For caching entire files. Automaticall determines wether to use gzip or not
 * @author Ken
 *
 */
class CacheFile extends CachePart{
	private static $gz=null;
	/**
	 * Enter description here ...
	 * @param string $file
	 * @param int $ttl Time to live in seconds
	 */
	public function __construct($file,$ttl){
		if(self::$gz==null)self::$gz=extension_loaded('zlib');
		if(self::$gz)$file.='.gz';
		parent::__construct($file,$ttl);
	}
	public function open($mode){
		if(self::$gz)
			$this->handle=gzopen($this->file,$mode);
		else
			$this->handle=fopen($this->file,$mode);
		if($this->handle){
			$this->open=true;
			$this->closed=false;
		}
		return $this->open;
	}
	public function close(){
		if($this->isLocked())
			$this->unlock();
		if(self::$gz)
			$this->closed=gzclose($this->handle);
		else
			$this->closed=fclose($this->handle);
		$this->open=!$this->closed;
		return $this->closed;
	}
	public function isEof(){
		if(self::$gz)return gzeof($this->handle);
		return feof($this->handle) || $this->getPosition()>=$this->getLength();
	}
	public function write($string,$len=-1){
		if(self::$gz)
			if($len==-1)
				return gzwrite($this->handle,$string);
			else
				return gzwrite($this->handle,$string,$len);
		else
			return parent::write($string,$len);
	}
	public function read($len=1){
		if(self::$gz)
			return gzread($this->handle,$len);
		else
			return fread($this->handle,$len);
	}
	public function putContents($string){
		if(self::$gz){
			if(!$rfile=gzopen($this->file,'wb'))return false;
			gzwrite($rfile,$string);
			gzclose($rfile);
			return true;
		}else{
			return parent::putContents($string)!==false;
		}
	}
	/* Sets headers and reads file to output.
	 * Headers set:
	 * Last-Modified, Content-Length, and Content-Encoding(if needed)
	 * @see File::readToOutput()
	 */
	public function readToOutput(){
		clearstatcache();
		$modTime=filemtime($this->file);
		header('Last-Modified: '. gmdate('D, d M Y H:i:s',$modTime).' GMT' ,true);
		header('Content-Length: '.filesize($this->file),true);
		if(self::$gz)
			header('Content-Encoding: gzip',true);
		header('Expires: '.gmdate('D, d M Y H:i:s',$modTime+$this->ttl) .' GMT',true);
		return readfile($this->file);
	}
}