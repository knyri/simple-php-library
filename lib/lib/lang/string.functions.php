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
 * @param string $needle
 * @param string $haystack
 */
function str_starts_with($needle, $haystack) {
	return (stripos($needle, $haystack)==0);
}
/**
 * Tests to see if the string ends with the needle.
 * @param string $needle
 * @param string $haystack
 * @return boolean
 */
function str_ends_with($needle, $haystack) {
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