<?php
/**
 * @author Kenneth Pierce kcpiercejr@gmail.com
 * @package exceptions
 */
PackageManager::requireClassOnce('error.CustomException');

class IllegalArgumentException extends CustomException{}