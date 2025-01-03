<?php
/**
 * @package io
 * @subpackage cache
 */


PackageManager::requireClassOnce('io.cachepart');
PackageManager::requireFunctionOnce('lang.string');

/**
 * For caching entire files. Automatically determines whether to use gzip
 * or not by seeing if the zlib extension is loaded.
 * Please note that this class does NOT use contexts.
 * @author Ken
 *
 */
class CacheFile extends CachePart{
	private static $gz= null;
	private $etag;
	/**
	 * @param string $file '.gz' is appended to the file if the zlib extension is found, the client supports it and $gzip is not specified or set to true
	 * @param number $ttl Time to live in seconds
	 * @param string $etag Optional. Is generated using md5() on the file's name if not set.
	 * @param boolean $gzip Optional. Whether to allow gzip encoding or not
	 */
	public function __construct($file, $ttl, $etag= null, $gzip= 0){
		if(self::$gz == null){
			if(!isset($_SERVER['HTTP_ACCEPT_ENCODING']) || strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false){
				self::$gz= extension_loaded('zlib');
			}
		}
		if(self::$gz && ($gzip === 0 || $gzip === true)){
			$file.='.gz';
		}
		parent::__construct($file, $ttl);
		if($etag == null) {
			$this->etag= md5($file);
		} else {
			$this->etag= $etag;
		}
	}
	public function setEtag($etag){
		$this->etag= $etag;
	}
	public function getEtag(){
		return $this->etag;
	}
	public function open($mode){
		if(self::$gz){
			$this->handle= gzopen($this->uri, $mode);
		}else{
			$this->handle= fopen($this->uri, $mode);
		}
		if($this->handle){
			$this->open= true;
			$this->closed= false;
		}
		return $this->open;
	}
	public function close(){
		if($this->isLocked()){
			$this->unlock();
		}
		if(self::$gz){
			$this->closed= gzclose($this->handle);
		}else{
			$this->closed= fclose($this->handle);
		}
		$this->open= !$this->closed;
		return $this->closed;
	}
	public function isEof(){
		if(self::$gz){
			return gzeof($this->handle);
		}
		return feof($this->handle) || $this->getPosition() >= $this->getLength();
	}
	public function write($string, $len= -1){
		if(self::$gz){
			if($len == -1){
				return gzwrite($this->handle, $string);
			}else{
				return gzwrite($this->handle, $string, $len);
			}
		}else{
			return parent::write($string, $len);
		}
	}
	public function read($len= 1){
		if(self::$gz){
			return gzread($this->handle, $len);
		}else{
			return fread($this->handle, $len);
		}
	}
	/* (non-PHPdoc)
	 * $flags and $ctx are ignored if using gzip.
	 * @see File::putContents()
	 */
	public function putContents($string,$flags=0,$ctx=null){
		if(self::$gz){
			if(!$rfile= gzopen($this->uri,'wb')){
				return false;
			}
			gzwrite($rfile,$string);
			gzclose($rfile);
			return true;
		}else{
			return parent::putContents($string,$flags,$ctx)!==false;
		}
	}
	/* Sets headers and reads file to output.
	 * Headers set:
	 *  Last-Modified
	 *  Content-Length
	 *  ETag
	 *  Expires
	 *  Vary
	 *  Content-Encoding(if needed)
	 *  Cache-Control
	 *  Unsets Pragma
	 * @see File::readToOutput()
	 */
	public function readToOutput(){
		clearstatcache();
		$modTime= filemtime($this->uri);
		header('Cache-Control: public,max-age='.$this->ttl,true);
		header('Pragma:',true);
		header('Last-Modified: '. gmdate('D, d M Y H:i:s',$modTime).' GMT' ,true);
		header('Content-Length: '.filesize($this->uri),true);
		header('ETag: "'.$this->etag.'"');
		header('Vary: Accept-Encoding');
		if(self::$gz && str_ends_with($this->uri, '.gz')){
			header('Content-Encoding: gzip',true);
		}
		header('Expires: '.gmdate('D, d M Y H:i:s',$modTime+$this->ttl) .' GMT',true);
		ob_end_flush();
		return readfile($this->uri);
	}
}