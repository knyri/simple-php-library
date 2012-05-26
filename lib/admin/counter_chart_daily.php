<?php
$max = 1;
$daylist = array();
while ($day = mysql_fetch_array($days, MYSQL_NUM)) {
	$daylist[$day[0]]=$day[1];
	if ($day[1]>$max) $max=$day[1];
}
$barheight = imagefontheight(1)+2;
$yinc = $barheight + 2;
$barx = 5 + imagefontwidth(1)*11;
$height = 12 + $yinc*count($daylist);
$width = 110 + imagefontwidth(1)*11;
$barmax = $width - 5;
$barwidth = 100;
$barmid = $barx+$barwidth/2;
$image = imagecreate($width, $height);
$y = $barheight;
require 'counter_chart_create.php';
?>