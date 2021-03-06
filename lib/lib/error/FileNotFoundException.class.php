<?php
/**
 * @author Kenneth Pierce kcpiercejr@gmail.com
 * @package exceptions
 * @subpackage io
 */

PackageManager::requireClassOnce('error.IOException');

class FileNotFoundException extends IOException {
	public function __construct($file=null,$code=0){
		if (!$file)
			parent::__construct('The file was not found.',$code);
		else
			parent::__construct($file, $code);
	}
}