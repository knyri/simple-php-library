<?php
class Upload {
	private $files;
	public function __construct(){
		$this->files= array_keys($_FILES);
	}
	public function hasError($name){
		return isset($_FILES[$name]) && $_FILES[$name]['error'] != 0;//UPLOAD_ERR_OK;
	}
	public function getErrorMessage($name){
		$error_code= $_FILES[$name]['error'];
		switch ($error_code) {
			case UPLOAD_ERR_INI_SIZE:// 1
				return 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
			case UPLOAD_ERR_FORM_SIZE: // 2
				return 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
			case UPLOAD_ERR_PARTIAL: // 3
				return 'The uploaded file was only partially uploaded';
			case UPLOAD_ERR_NO_FILE: // 4
				return 'No file was uploaded';
			case UPLOAD_ERR_NO_TMP_DIR: // 6
				return 'Missing a temporary folder';
			case UPLOAD_ERR_CANT_WRITE: // 7
				return 'Failed to write file to disk';
			case UPLOAD_ERR_EXTENSION: // 8
				return 'File upload stopped by extension';
			default:
				return 'Unknown upload error('.$error_code.')';
		}
	}
	public function getType($name){
		return $_FILES[$name]['type'];
	}
	public function getSize($name){
		return $_FILES[$name]['size'];
	}
	public function getName($name){
		return $_FILES[$name]['name'];
	}
	public function getTempName($name){
		return $_FILES[$name]['tmp_name'];
	}
	public function moveFile($name, $to){
		return move_uploaded_file($_FILES[$name]['tmp_name'], $to);
	}
}
class UploadFile {
	private $file;
	public function __construct($name){
		$this->file= $_FILES[$name];
	}
	public function getType(){
		return $this->file['type'];
	}
	public function getSize(){
		return $this->file['size'];
	}
	public function getName(){
		return $this->file['name'];
	}
	public function getTempName(){
		return $this->file['tmp_name'];
	}
	public function moveFile($to){
		return move_uploaded_file($this->file['tmp_name'], $to);
	}
}