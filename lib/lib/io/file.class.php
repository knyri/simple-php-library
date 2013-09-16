<?php
/**
 * A class that represents a file.
 * Puts all the file functions in an object format.
 * @author Ken
 *
 */
class File{
	protected $file=null;
	private $handle=null;
	private $open=false,$closed=false,$locked=false;
	public function __construct($file){
		$this->file=$file;
	}
	/**
	 * @return string The name of the file
	 */
	public function getFile(){return $this->file;}
	public function open($mode){
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
		$this->closed=fclose($this->handle);
		$this->open=!$this->closed;
		return $this->closed;
	}
	public function isOpen(){return $this->open;}
	public function isClosed(){return $this->closed;}
	public function getLength(){return filesize($this->file);}
	public function copy($to,$context=null){
		if($context==null)
			return copy($this->file,$to);
		else
			return copy($this->file,$to,$context);
	}
	/**
	 * Creates the directory structure for this file if it does not exist.
	 * @return boolean
	 */
	public function ensureDir(){
		if(!file_exists(dirname($this->file)))
			return mkdir(dirname($this->file),0777,true);
		return true;
	}
	/**
	 * Moves/renames the file.
	 * See rename(...) and move_uploaded_file(...) in the PHP standard library.
	 * @param string $to Destination
	 * @param resource $context Ignored if $this->isUploadedFile()==true
	 */
	public function move($to,$context=null){
		if($this->isUploadedFile()){
			return move_uploaded_file($this->file,$to);
		}
		if($context==null)
			return rename($this->file,$to);
		else
			return rename($this->file,$to,$context);
	}
	/**
	 * Alias of move(...)
	 * @param string $to
	 * @param resource $context
	 */
	public function rename($to,$context=null){$this->move($to,$context);}
	public function lock($operation,&$wouldBlock=null){
		if($wouldBlock==null)
			$this->locked= flock($this->handle,$operation);
		else
			$this->locked= flock($this->handle,$operation,$wouldBlock);
		return $this->locked;
	}
	public function unlock(&$wouldBlock=null){$this->locked= !$this->lock(LOCK_UN,$wouldBlock);return $this->locked;}
	public function isLocked(){return $this->locked;}
	public function getPosition(){return ftell($this->handle);}
	public function seek($offset,$start=SEEK_CUR){$ret=fseek($this->handle,$offset,$start);return $ret===0;}
	public function rewind(){return rewind($this->handle);}
	public function read($len=1){return fread($this->handle,$len);}
	public function scanFormat($format){return fscanf($this->handle,$format);}
	public function write($string,$len=-1){
		if($len==-1)
			return fwrite($this->handle,$string);
		else
			return fwrite($this->handle,$string,$len);
	}
	public function flush(){return fflush($this->handle);}
	public function isEof(){
		return feof($this->handle) || $this->getPosition()>=$this->getLength();
	}
	public function exists(){return file_exists($this->file);}
	public function isDir(){return is_dir($this->file);}
	public function isFile(){return is_file($this->file);}
	/**
	 * Checks to see if the file is a symbolic link
	 * @return boolean
	 */
	public function isLink(){return is_link($this->file);}
	public function isReadable(){return is_readable($this->file);}
	public function isWriteable(){return is_writable($this->file);}
	public function isExecutable(){return is_executable($this->file);}
	public function isUploadedFile(){return is_uploaded_file($this->file);}
	/** See file_get_contents(...) in the standard PHP library.
	 * Returns the contents of the file as a string. You do not need to open the file to do this.
	 * @return string The file contents
	 */
	public function getContents(){return file_get_contents($this->file);}
	/**
	 * See file_put_contents(...) in the standard PHP library.
	 * Puts the contents into the file. You do not need to open the file to do this.
	 * @param string $string
	 * @return number
	 */
	public function putContents($string){return file_put_contents($this->file, $string);}
	public function delete(){return unlink($this->file);}
	public function unlink(){return $this->delete();}
	public function touch($time=0,$atime=0){if($time==0)$time=time();if($atime==0)$atime=$time;return touch($this->file,$time,$atime);}
	public function truncate($size=0){return ftruncate($this->handle,$size);}
	public function getModTime(){return filemtime($this->file);}
	public function getCreatedTime(){return filectime($this->file);}
	public function basename($suffix=''){return basename($this->file,$suffix);}
	public function readToOutput(){return readfile($this->file);}
}