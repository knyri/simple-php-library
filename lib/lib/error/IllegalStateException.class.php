<?php
/**
 * @author Kenneth Pierce kcpiercejr@gmail.com
 */
//require_once 'class_CustomException.php';
PackageManager::requireClassOnce('error.CustomException');

class IllegalStateException extends CustomException {}