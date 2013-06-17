<?php
PackageManager::requireClassOnce('io.file');

/**
 * For caching part of a file or output.
 * @author Ken
 *
 */
class CachePart extends File{
	protected $ttl=0;
	public function __construct($file,$ttl){
		parent::__construct($file);
		$this->ttl=$ttl;
	}
	public function hasExpired(){
		if(!$this->exists())return true;
		return ($this->getModTime()+$this->ttl)<time();
	}
}
