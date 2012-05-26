<?php
require_once 'libconfig.inc.php';
PackageManager::requireClassOnce('lib.util.counter.counter');
$c = new Counter();
$c->setPage($_GET['url']);
if (isset($_SERVER['HTTP_REFERER'])) {
	$c->setReferrer($_SERVER['HTTP_REFERER']);
	header('Referer: '.$_SERVER['HTTP_REFERER']);
}
$c->count();
header('Location: '.$_GET['url']);