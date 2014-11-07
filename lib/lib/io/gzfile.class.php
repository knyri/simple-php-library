<?php
/**
 * @package io
 */
PackageManager::requireClassOnce('io.file');
/**
 * A class that represents a gz file.
 * Puts all the file functions in an object format.
 * @author Ken
 *
 */
class GZFile extends File{
	public function open($mode){
		$this->handle=gzopen($this->uri,$mode);
		if($this->handle){
			$this->open=true;
			$this->closed=false;
		}
		return $this->open;
	}
	public function close(){
		if($this->isLocked())
			$this->unlock();
		$this->closed=gzclose($this->handle);
		$this->open=!$this->closed;
		return $this->closed;
	}
	public function getPosition(){return gztell($this->handle);}
	public function seek($offset,$start=SEEK_CUR){$ret=gzseek($this->handle,$offset,$start);return $ret===0;}
	public function rewind(){return gzrewind($this->handle);}
	public function read($len=1){return gzread($this->handle,$len);}
	public function scanFormat($format){return false;}
	public function write($string,$len=-1){
		if($len==-1)
			return gzwrite($this->handle,$string);
		else
			return gzwrite($this->handle,$string,$len);
	}
	/* Always returns true
	 * @see Stream::flush()
	 */
	public function flush(){return true;}
	public function isEof(){
		return gzeof($this->handle) || $this->getPosition()>=$this->getLength();
	}
	/**
	 * Mimics file_get_contents()
	 * @return string The file contents
	 */
	public function getContents(){
		ob_start();
		$this->readToOutput();
		return ob_get_clean();
	}
	/**
	 * See file_put_contents(...) in the standard PHP library.
	 * Puts the contents into the file. You do not need to open the file to do this.
	 * @param string $string
	 * @return number
	 */
	public function putContents($string){
		$ret=false;
		if($this->open('wb')){
			$ret = $this->write($string);
			$this->close();
		}
		return $ret;
	}
	public function truncate($size=0){return false;}
	public function readToOutput(){return readgzfile($this->uri);}
}