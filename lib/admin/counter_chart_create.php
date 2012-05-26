<?php
$image = imagecreate($width, $height);
$color2 = imagecolorallocate($image, 255, 255, 255);
$color = imagecolorallocate($image, 0, 0, 0);
imagestring($image, 1, 1, 0, 'Max: '.$max, $color);

foreach ($daylist as $hour=>$count) {
	imagestring($image, 1, 5, $y, $hour, $color);
	imagerectangle($image, $barx, $y, $barx+(($count/$max)*$barwidth), $y + $barheight, $color);
	$strlen_half = imagefontwidth(1)*strlen($count)/2;
	imagefilledrectangle($image, $barmid-$strlen_half-2, $y+1, $barmid+$strlen_half+2, $y -1 + $barheight, $color2);
	imagestring($image, 1, $barmid-$strlen_half, $y+2, $count, $color);
	$y += $yinc;
}

header('Content-type: image/png');
imagepng($image);
imagedestroy($image);
?>