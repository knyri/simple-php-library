<?php
/*
 * Place this in your document root and include it using:
 * require_once $_SERVER['DOCUMENT_ROOT'].'/libconfig.inc.php';
 */
if (!defined('ROOT')) define('ROOT', $_SERVER['DOCUMENT_ROOT'].'/');
if (!defined('LIB')) define('LIB', 'lib2/');//path to the lib directory from server root. With trailing '/'. Leave blank if on the php.ini search path.
if (!defined('EOL')) define('EOL', "\n");
if (!defined('TAB')) define('TAB', "\t");
$GLOBALS['simple']['lib']['db']['user'] = '';//database user
$GLOBALS['simple']['lib']['db']['password'] = '';//database password
$GLOBALS['simple']['lib']['db']['host'] = '';//database host. Normally 'localhost'
$GLOBALS['simple']['lib']['db']['database'] = '';//default database to use.
$GLOBALS['simple']['lib']['util']['counter']['database'] = '';//;leave blank if the same as lib/db/database
$GLOBALS['simple']['lib']['util']['counter']['expire'] = 60*24;//time in minutes between page views before incremented again for an individual visitor
require_once LIB.'packagemanager.class.php';

PackageManager::addPath(LIB);

// PackageManager::addPath($path); // add paths to be searched for library files.
?>