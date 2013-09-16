<?php

/**
 * Credit: http://www.php.net/manual/en/function.mb-detect-encoding.php#68607
 * @param string $string
 * @return number 1 or 0
 */
function detectUTF8($string){
	# non-overlong 2-byte
	# excluding overlongs
	# straight 3-byte
	# excluding surrogates
	# planes 1-3
	# planes 4-15
	# plane 16
	return preg_match('%(?:[\xC2-\xDF][\x80-\xBF]|\xE0[\xA0-\xBF][\x80-\xBF]|[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}|\xED[\x80-\x9F][\x80-\xBF]|\xF0[\x90-\xBF][\x80-\xBF]{2}|[\xF1-\xF3][\x80-\xBF]{3}|\xF4[\x80-\x8F][\x80-\xBF]{2})+%xs', $string);
}


/*
 * Code Credit: http://www.php.net/manual/en/function.mb-detect-encoding.php#91051
 */
// Unicode BOM is U+FEFF, but after encoded, it will look like this.
define ('UTF32_BIG_ENDIAN_BOM'   , chr(0x00) . chr(0x00) . chr(0xFE) . chr(0xFF));
define ('UTF32_LITTLE_ENDIAN_BOM', chr(0xFF) . chr(0xFE) . chr(0x00) . chr(0x00));
define ('UTF16_BIG_ENDIAN_BOM'   , chr(0xFE) . chr(0xFF));
define ('UTF16_LITTLE_ENDIAN_BOM', chr(0xFF) . chr(0xFE));
define ('UTF8_BOM'               , chr(0xEF) . chr(0xBB) . chr(0xBF));

/** Attempts to detect the UTF encoding based on the BOM.
 * @param string $filename
 * @return string|boolean UTF-8, UTF-16LE, UTF-16BE, UTF-32BE, UTF-32LE or false if the UTF encoding could not be determined
 */
function detect_utf_encoding($filename){
	$text = file_get_contents($filename);
	$first2 = substr($text, 0, 2);
	$first3 = substr($text, 0, 3);
	$first4 = substr($text, 0, 3);

	if ($first3 == UTF8_BOM) return 'UTF-8';
	elseif ($first4 == UTF32_BIG_ENDIAN_BOM) return 'UTF-32BE';
	elseif ($first4 == UTF32_LITTLE_ENDIAN_BOM) return 'UTF-32LE';
	elseif ($first2 == UTF16_BIG_ENDIAN_BOM) return 'UTF-16BE';
	elseif ($first2 == UTF16_LITTLE_ENDIAN_BOM) return 'UTF-16LE';
	return false;
}