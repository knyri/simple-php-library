<?php
/**
 * @author Kenneth Pierce kcpiercejr@gmail.com
 * @package exceptions
 * @subpackage io
 */
PackageManager::requireClassOnce('error.CustomException');

class IOException extends CustomException{}