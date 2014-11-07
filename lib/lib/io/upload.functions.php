<?php
/**
 * @package io
 * @subpackage upload_helper
 */

/*
$_FILES['userfile']['name']
	The original name of the file on the client machine.
$_FILES['userfile']['type']
	The mime type of the file, if the browser provided this information. An example would be "image/gif". This mime type is however not checked on the PHP side and therefore don't take its value for granted.
$_FILES['userfile']['size']
	The size, in bytes, of the uploaded file.
$_FILES['userfile']['tmp_name']
	The temporary filename of the file in which the uploaded file was stored on the server.
$_FILES['userfile']['error']
	The error code associated with this file upload. This element was added in PHP 4.2.0
*/
/**
 *
 * @param integer $error_code
 * @return string Description of the error
 */
function file_upload_error_message($error_code) {
	if(!is_numeric($error_code))
		$error_code=file_upload_get_error($error_code);
	switch ($error_code) {
		case 1:
		case UPLOAD_ERR_INI_SIZE:
			return 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
		case 2:
		case UPLOAD_ERR_FORM_SIZE:
			return 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
		case 3:
		case UPLOAD_ERR_PARTIAL:
			return 'The uploaded file was only partially uploaded';
		case 4:
		case UPLOAD_ERR_NO_FILE:
			return 'No file was uploaded';
		case 6:
		case UPLOAD_ERR_NO_TMP_DIR:
			return 'Missing a temporary folder';
		case 7:
		case UPLOAD_ERR_CANT_WRITE:
			return 'Failed to write file to disk';
		case 8:
		case UPLOAD_ERR_EXTENSION:
			return 'File upload stopped by extension';
		default:
			return 'Unknown upload error('.$error_code.')';
	}
}
/**
 * @param string|array $name
 * @return string|unknown
 */
function file_upload_get_error($name){
	if (is_array($name)) {
		if(!isset($_FILES[$name[0]]))return UPLOAD_ERR_NO_FILE;
		$base=$_FILES[$name[0]]['error'];
		for ($i=1;$i<count($name);$i++) {
			$base=$base[$name[$i]];
		}
		return $base;
	} else {
		if(!isset($_FILES[$name]))return UPLOAD_ERR_NO_FILE;
		return $_FILES[$name]['error'];
	}
}
function file_upload_has_error($name) {
	if (is_array($name)) {
		if(!isset($_FILES[$name[0]]))return false;
		$base=$_FILES[$name[0]]['error'];
		for ($i=1;$i<count($name);$i++) {
			$base=$base[$name[$i]];
		}
		return $base!=0;//UPLOAD_ERR_OK;
	} else {
		return isset($_FILES[$name]) && $_FILES[$name]['error']!=0;//UPLOAD_ERR_OK;
	}
}
?>