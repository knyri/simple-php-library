<?php
/*
 * Place this in your document root and include it using:
 * require_once $_SERVER['DOCUMENT_ROOT'].'/libconfig.php';
 */
if (!defined('LIB')) define('LIB', '/lib');//path to the lib directory from server root. With trailing '/'. Leave blank if on the php.ini search path.
if (!defined('URLPATH')) define('URLPATH', '/lib/');//path to the lib directory from the HTTP root ('/')
if (!defined('EOL')) define('EOL', "\n");
if (!defined('TAB')) define('TAB', "\t");
class LibConfig{
	private static $conf=array('db'=>array(),'counter'=>array()),
		$init=false;
	public static function init(){
		if(self::$init)return;
		self::$conf['db']= parse_ini_file(LIB.'/database.ini', true);
		self::$conf['util.counter']['database']= '';//leave blank if the same as lib/db/database
		self::$conf['util.counter']['expire']= 60*24;//time in minutes between page views before incremented again for an individual visitor
		self::$conf['util.counter']['multidomain']= false;//enable this to track multiple domains(including subdomains)
		self::$init=true;
	}
	public static function &getConfig($conf){
		return isset(self::$conf[$conf])? self::$conf[$conf] : null;
	}
}
LibConfig::init();
require_once LIB.'/packagemanager.class.php';
PackageManager::addPath(LIB);

// PackageManager::addPath($path); // add paths to be searched for library files.