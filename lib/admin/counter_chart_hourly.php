<?php
$max = 1;
$daylist = array(0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0);
while ($day = mysql_fetch_array($days, MYSQL_NUM)) {
	$daylist[$day[0]]=$day[1];
	if ($day[1]>$max) $max=$day[1];
}
$barheight = imagefontheight(1)+2;
$yinc = $barheight + 2;
$barx = 5 + imagefontwidth(1)*3;
$height = 12 + $yinc*24;
$width = 110 + imagefontwidth(1)*11;
$barmax = $width - 5;
$barwidth = 100;
$barmid = $barx+$barwidth/2;
$y = $barheight;
require 'counter_chart_create.php';
?>