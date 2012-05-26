<?php
require_once 'libconfig.inc.php';
PackageManager::requireClassOnce('util.counter.counter');
$c = new Counter();
$c->setPage($_SERVER['HTTP_REFERER']);
if (empty($_GET['referrer'])) $_GET['referrer'] = 'direct';
$c->setReferrer($_GET['referrer']);
$count = explode('<br />',$c->count());

$font = 2;
$linespacing = 1;
$margin['top'] = 2;
$margin['bottom'] = 2;
$margin['left'] = 2;
$margin['right'] = 2;
$lineheight = imagefontheight($font);

$lines = count($count);
$maxlen = max(array_map('strlen',$count));
$width = imagefontwidth($font)*$maxlen+$margin['top']+$margin['bottom'];
$im = imagecreate($width,(imagefontheight($font)+$linespacing)*count($count)+$margin['right']+$margin['left']);//imagecreate($width, imagefontheight($font)+4);
$black = imagecolorallocate($im, 0,0,0);
$white = imagecolorallocate($im, 255, 255, 255);
$top = $margin['top'];
//imagestring($im, 2, $top, 2, $value, $white);
foreach ($count as $value) {
	imagestring($im, 2, 2, $top, $value, $white);
	$top += $lineheight+$linespacing;
}
header("Content-Type: image/png");
imagepng($im);
imagedestroy($im);