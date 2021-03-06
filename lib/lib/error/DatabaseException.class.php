<?php
/**
 * @author Kenneth Pierce kcpiercejr@gmail.com
 * @package exceptions
 */
PackageManager::requireClassOnce('error.CustomException');

/**
 * For database errors.
 * @author Kenneth Pierce
 */
class DatabaseException extends CustomException{}