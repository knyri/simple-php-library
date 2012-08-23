<?php
/**
 * @author Kenneth Pierce kcpiercejr@gmail.com
 */

/**
 * Tests to see if $str contains something useful
 * @param string $str
 * @return boolean true if ($str==NULL || strlen($str)===0)
 */
function str_empty($str) {
	return $str==NULL || strlen($str)===0;
}
/**
 *
 * Tests to see if the string starts with the needle.
 * @param string $haystack
 * @param string $needle
 */
function str_starts_with($haystack, $needle) {
	if(strlen($haystack)<strlen($needle))return false;
	return substr($haystack,0,strlen($needle))==$needle;
}
/**
 * Tests to see if the string ends with the needle.
 * @param string $needle
 * @param string $haystack
 * @return boolean
 */
function str_ends_with($haystack, $needle) {
	return (substr($haystack, -strlen($needle))==$needle);
}
/**
 * Allows use of the char list argument when calling trim on an array.
 * @param string $charlist set to null to use the default.(though array_map is a better option in this case)
 * @param array $ary
 * @return array The resulting array.
 */
function str_trim_array($charlist, $ary) {
	if ($charlist===null) return array_map('trim', $ary);
	foreach ($ary as $key => $value) {
		$ary[$key] = trim($value,$charlist);
	}
	return $ary;
}

/**
 * Returns a substring of the <var>string</var> to the nearest word. Does not support negative numbers(for now).
 * @param string $string
 * @param int $offset
 * @param int $length
 * @param array $separators Default is <var>array(' ','-','.','/',',')</var>
 * @return string
 */
function smart_substring($string,$offset,$length,array $separators=array(' ','-','.','/',',')){
	while($length>0 && !in_array($string[$offset+$length],$separators))$length--;
	return substr($string, $offset,$length);
}