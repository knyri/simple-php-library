<?php
require_once 'stream.class.php';
/**
 * A class that represents a file.
 * Puts all the file functions in an object format.
 * @author Ken
 *
 */
class File extends Stream{
	/**
	 * @return string The name of the file
	 */
	public function getFile(){return $this->uri;}
	/**
	 * filesize()
	 * @return number
	 */
	public function getLength(){return filesize($this->uri);}
	/**
	 * copy()
	 * Context can be set with setContext()
	 * @param string $to
	 * @return boolean
	 */
	public function copy($to){
		if($this->ctx==null)
			return copy($this->uri,$to);
		else
			return copy($this->uri,$to,$this->ctx);
	}
	/**
	 * Attempts to create the directory structure for this file if it does not exist.
	 * Context can be set with setContext()
	 * @param number $mask default is 0777
	 * @return boolean false if the directory does not exist and creation failed
	 */
	public function ensureDir($mask=0777){
		if(!file_exists(dirname($this->uri))){
			if($this->ctx)
				return mkdir(dirname($this->uri),$mask,true,$this->ctx);
			return mkdir(dirname($this->uri),$mask,true);
		}
		return true;
	}
	/**
	 * Moves/renames the file.
	 * See rename(...) and move_uploaded_file(...) in the PHP standard library.
	 * @param string $to Destination
	 * @param resource $context Ignored if $this->isUploadedFile()==true
	 */
	public function move($to){
		if($this->isUploadedFile())
			return move_uploaded_file($this->uri,$to);
		if($this->ctx==null)
			return rename($this->uri,$to);
		return rename($this->uri,$to,$this->ctx);
	}
	/**
	 * Alias of move(...)
	 * @param string $to
	 */
	public function rename($to){$this->move($to);}

	/**
	 * file_exists()
	 * @return boolean
	 */
	public function exists(){return file_exists($this->uri);}
	/**
	 * is_dir()
	 * @return boolean
	 */
	public function isDir(){return is_dir($this->uri);}
	/**
	 * is_file()
	 * @return boolean
	 */
	public function isFile(){return is_file($this->uri);}
	/**
	 * is_link()
	 * Checks to see if the file is a symbolic link
	 * @return boolean
	 */
	public function isLink(){return is_link($this->uri);}
	/**
	 * is_readable()
	 * @return boolean
	 */
	public function isReadable(){return is_readable($this->uri);}
	/**
	 * is_writable()
	 * @return boolean
	 */
	public function isWriteable(){return is_writable($this->uri);}
	/**
	 * is_executable()
	 * @return boolean
	 */
	public function isExecutable(){return is_executable($this->uri);}
	/**
	 * is_uploaded_file()
	 * @return boolean
	 */
	public function isUploadedFile(){return is_uploaded_file($this->uri);}
	/** See file_get_contents(...) in the standard PHP library.
	 * Returns the contents of the file as a string. You do not need to open the file to do this.
	 * @return string The file contents
	 */
	public function getContents(){return file_get_contents($this->uri);}
	/**
	 * See file_put_contents(...) in the standard PHP library.
	 * Puts the contents into the file. You do not need to open the file to do this.
	 * @param string $string
	 * @return number
	 */
	public function putContents($string,$flags=0,$ctx=null){
		if($ctx)
			return file_put_contents($this->uri, $string,$flags,$ctx);
		return file_put_contents($this->uri, $string,$flags);
	}
	/**
	 * unlink()
	 * @return boolean
	 */
	public function delete(){return unlink($this->uri);}
	/**
	 * unlink()
	 * @return boolean
	 */
	public function unlink(){return unlink($this->uri);}
	/**
	 * touch()
	 * @param number $time
	 * @param number $atime
	 * @return boolean
	 */
	public function touch($time=0,$atime=0){
		if($time==0)$time=time();
		if($atime==0)$atime=$time;
		return touch($this->uri,$time,$atime);
	}
	/**
	 * filemtime()
	 * @return number
	 */
	public function getModTime(){return filemtime($this->uri);}
	/**
	 * filectime()
	 * @return number
	 */
	public function getCreatedTime(){return filectime($this->uri);}
	/**
	 * basename()
	 * @param string $suffix
	 * @return string
	 */
	public function basename($suffix=''){return basename($this->uri,$suffix);}
	/**
	 * readfile()
	 * Context can be set with setContext()
	 * @return number
	 */
	public function readToOutput(){
		if($this->ctx)
			return readfile($this->uri,$this->use_include_path,$this->ctx);
		return readfile($this->uri,$this->use_include_path);
	}

}