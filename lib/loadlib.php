<?php
require_once 'libconfig.inc.php';
require_once('packagemanager.class.php');
if (!defined('DS'))
	define('DS',DIRECTORY_SEPARATOR);
PackageManager::addPath(dirname(__FILE__).DS);//add this directory
PackageManager::addPath(dirname(__FILE__).DS.'lib'.DS);//add the parent directory
PackageManager::requireClassOnce('config');