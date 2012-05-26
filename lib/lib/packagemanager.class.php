<?php
class PackageManager {
	private static $paths = array();
	/**
	 * Adds a path to search for files.
	 * @param string $path
	 */
	public static function addPath($path) {
		PackageManager::$paths[] = $path;
	}
	public static function includeFile($file) {
		foreach (PackageManager::$paths as $path) {
			if (file_exists($path.$file)) {
				include($path.$file);
				return true;
			}
		}
		return false;
	}
	public static function includeFileOnce($file) {
		foreach (PackageManager::$paths as $path) {
			if (file_exists($path.$file)) {
				include_once ($path.$file);
				return true;
			}
		}
		return false;
	}
	public static function requireFile($file) {
		foreach (PackageManager::$paths as $path) {
			if (file_exists($path.$file)) {
				require($path.$file);
				return true;
			}
		}
		throw new Exception("Could not find '".$file.'\' Paths: \''.PackageManager::getPaths().'\'', 0);
		//return false;
	}
	public static function requireFileOnce($file) {
		foreach (PackageManager::$paths as $path) {
			if (file_exists($path.$file)) {
				require_once($path.$file);
				return true;
			}
		}
		throw new Exception("Could not find '".$file.'\' Paths: \''.PackageManager::getPaths().'\'', 0);
		//return false;
	}
	private static function getPaths() {
		$ret = '.';
		foreach (PackageManager::$paths as $value) {
			$ret = $ret.';'.$value;
		}
		return $ret;
	}
	public static function includeClass($package) {
		return PackageManager::includeFile(str_replace('.', '/', $package).'.class.php');
	}
	public static function includeClassOnce($package) {
		return PackageManager::includeFileOnce(str_replace('.', '/', $package).'.class.php');
	}
	public static function requireClass($package) {
		return PackageManager::requireFile(str_replace('.', '/', $package).'.class.php');
	}
	public static function requireClassOnce($package) {
		return PackageManager::requireFileOnce(str_replace('.', '/', $package).'.class.php');
	}
	public static function includeFunction($package) {
		return PackageManager::includeFile(str_replace('.', '/', $package).'.functions.php');
	}
	public static function includeFunctionOnce($package) {
		return PackageManager::includeFileOnce(str_replace('.', '/', $package).'.functions.php');
	}
	public static function requireFunction($package) {
		return PackageManager::requireFile(str_replace('.', '/', $package).'.functions.php');
	}
	public static function requireFunctionOnce($package) {
		return PackageManager::requireFileOnce(str_replace('.', '/', $package).'.functions.php');
	}
}
?>