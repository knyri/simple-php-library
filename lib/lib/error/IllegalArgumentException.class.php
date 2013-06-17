<?php
/**
 * @author Kenneth Pierce kcpiercejr@gmail.com
 */
PackageManager::requireClassOnce('error.CustomException');

class IllegalArgumentException extends CustomException{}