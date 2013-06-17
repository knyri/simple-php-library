<?php
/**
 * @author Kenneth Pierce kcpiercejr@gmail.com
 */
PackageManager::requireClassOnce('error.CustomException');

/**
 * For database errors.
 * @author Kenneth Pierce
 */
class DatabaseException extends CustomException{}