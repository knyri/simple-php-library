<?php

class FtpClient {
	private $con;
	private $cwd;
	private $pathSep;
	private $attr= array(false, FTP_BINARY);
	private $isPassive= false;
	public const
		ATTR_PREALLOCATE= 0,
		ATTR_TRANSFER_MODE= 1,
		ATTR_TIMEOUT= -1,
		ATTR_AUTOSEEK= -2,
		ATTR_PASSIVE= -3
	;
	public const
		MODE_BINARY= FTP_BINARY,
		MODE_ASCII= FTP_ASCII
	;
	public function connect($host, $port= null, $timeout= 10){
		if($this->con){
			return true;
		}
		$this->con= ftp_connect($host, $port, $timeout);
		return $this->con !== false;
	}
	public function connectSSL($host, $port= 21, $timeout= 10){
		if($this->con){
			return true;
		}
		$this->con= ftp_ssl_connect($host, $port, $timeout);
		return $this->con !== false;
	}
	/**
	 * @param int $attr
	 * @param mixed $value
	 * @return boolean true if the attribute was set. False if the attribute is unknown or the value isn't appropriate
	 */
	public function setAttribute($attr, $value){
		switch($attr){
			case self::ATTR_PREALLOCATE:
				$this->attr[$attr]= $value === true;
				break;
			case self::ATTR_TRANSFER_MODE:
				switch($value){
					case self::MODE_ASCII:
					case self::MODE_BINARY:
						$this->attr[$attr]= $value;
						break;
					default:
						return false;
				}
				break;
			case self::ATTR_TIMEOUT:
				return ftp_set_option($this->con, FTP_TIMEOUT_SEC, $value);
			case self::ATTR_AUTOSEEK:
				return ftp_set_option($this->con, FTP_AUTOSEEK, $value);
			case self::ATTR_PASSIVE:
				if(ftp_pasv($this->con, $value)){
					$this->isPassive= $value;
				}else{
					return false;
				}
				break;
			default:
				return false;
		}
		return true;
	}
	public function getAttribute($attr){
		switch($attr){
			case self::ATTR_TIMEOUT:
				return ftp_get_option($this->con, FTP_TIMEOUT_SEC);
			case self::ATTR_AUTOSEEK:
				return ftp_get_option($this->con, FTP_AUTOSEEK);
		}
		return $this->attr[$attr];
	}
	public function login($user, $pass){
		if(!ftp_login($this->con, $user, $pass)){
			return false;
		}
		$this->pwd();
		$this->pathSep= $this->cwd[0];
		return true;
	}

	public function cdup(){
		$ret= ftp_cdup($this->con);
		if($ret) $this->pwd();
		return $ret;
	}
	public function chdir($dir){
		$ret= ftp_chdir($this->con, $dir);
		if($ret) $this->pwd();
		return $ret;
	}
	public function fget($stream, $remote, $offset=0){
		return ftp_fget($this->con, $stream, $remote, $this->getAttribute(self::ATTR_TRANSFER_MODE), $offset);
	}
	public function fput($stream, $remote, $offset=0){
		return ftp_fput($this->con, $stream, $remote, $this->getAttribute(self::ATTR_TRANSFER_MODE), $offset);
	}
	public function lastModified($remote){
		return ftp_mdtm($this->con, $remote);
	}
	public function mkdir($remote){
		return ftp_mkdir($this->con, $remote);
	}
	public function fileList(){
		return ftp_nlist($this->con, $this->cwd);
	}
	public function directoryList($remoteDir){
		return ftp_nlist($this->con, $remoteDir);
	}
	public function fileListDetails() {
		return ftp_mlsd($this->con, $this->cwd);
	}
	public function directoryListDetails($remoteDir) {
		return ftp_mlsd($this->con, $remoteDir);
	}
	public function fileListRaw(){
		return ftp_rawlist($this->con, $this->cwd);
	}
	public function directoryListRaw($remoteDir){
		return ftp_rawlist($this->con, $remoteDir);
	}
	public function nbContinue(){
		return ftp_nb_continue($this->con);
	}
	public function nbFget($stream, $remote, $offset=0){
		return ftp_nb_fget($this->con, $stream, $remote, $this->getAttribute(self::ATTR_TRANSFER_MODE), $offset);
	}
	public function nbFput($stream, $remote, $offset=0){
		return ftp_nb_fput($this->con, $stream, $remote, $this->getAttribute(self::ATTR_TRANSFER_MODE), $offset);
	}
	public function nbGet($local, $remote, $offset=0){
		return ftp_nb_get($this->con, $local, $remote, $this->getAttribute(self::ATTR_TRANSFER_MODE), $offset);
	}
	public function nbPut($local, $remote, $offset=0){
		return ftp_nb_put($this->con, $local, $remote, $this->getAttribute(self::ATTR_TRANSFER_MODE), $offset);
	}
	public function rename($old, $new){
		return ftp_rename($this->con, $old, $new);
	}
	public function rmdir($remoteDir){
		return ftp_rmdir($this->con, $remoteDir);
	}
	public function fileSize($remote){
		return ftp_size($this->con, $remote);
	}
	public function systemType(){
		return ftp_systype($this->con);
	}
	/**
	 * @param string $remote
	 * @param int $permissions
	 * @return boolean
	 */
	public function chmod($remote, $permissions){
		return ftp_chmod($this->con, $permissions, $remote) === $permissions;
	}
	public function exec($cmd){
		return ftp_exec($this->con, $cmd);
	}
	public function execRaw($cmd){
		return ftp_raw($this->con, $cmd);
	}
	public function execSite($cmd){
		return ftp_site($this->con, $cmd);
	}

	public function pwd(){
		return ($this->cwd= ftp_pwd($this->con));
	}
	public function delete($path){
		if($path[0] == $this->pathSep){
			return ftp_delete($this->con, $path);
		}else{
			return ftp_delete($this->con, $this->cwd . $this->pathSep . $path);
		}
	}
	public function append($local, $remote){
		if($this->getAttribute(self::ATTR_PREALLOCATE)){
			ftp_alloc($this->con, filesize($local));
		}
		if($remote[0] == $this->pathSep){
			return ftp_append($this->con, $remote, $local, $this->getAttribute(self::ATTR_TRANSFER_MODE));
		}else{
			return ftp_append($this->con, $this->cwd . $this->pathSep . $remote, $local, $this->getAttribute(self::ATTR_TRANSFER_MODE));
		}
	}
	public function put($local, $remote, $offset= 0){
		if($this->getAttribute(self::ATTR_PREALLOCATE)){
			ftp_alloc($this->con, filesize($local));
		}
		if($remote[0] == $this->pathSep){
			return ftp_put($this->con, $remote, $local, $this->getAttribute(self::ATTR_TRANSFER_MODE), $offset);
		}else{
			return ftp_put($this->con, $this->cwd . $this->pathSep . $remote, $local, $this->getAttribute(self::ATTR_TRANSFER_MODE), $offset);
		}
	}
	public function get($local, $remote, $offset=0){
		if($$remote[0] == $this->pathSep){
			return ftp_get($this->con, $local, $remote, $this->getAttribute(self::ATTR_TRANSFER_MODE), $offset);
		}else{
			return ftp_get($this->con, $local, $this->cwd . $this->pathSep . $remote, $this->getAttribute(self::ATTR_TRANSFER_MODE), $offset);
		}
	}
	public function close(){
		if(ftp_close($this->con)){
			$this->con= null;
		}
		return $this->con === null;
	}
}