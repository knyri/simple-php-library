<?php
/**
 * @author Kenneth Pierce kcpiercejr@gmail.com
 */
//require_once 'class_CustomException.php';
PackageManager::requireClassOnce('error.CustomException');

/**
 * For database errors.
 * @author Kenneth Pierce
 */
class DatabaseException extends CustomException {}