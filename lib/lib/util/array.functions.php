<?php
/**
 * @author Kenneth Pierce kcpiercejr@gmail.com
 */

/**
 * Extracts the specified keys from the first array and returns the
 * resulting array.
 * @param array $from Raw array
 * @param array $keys List of wanted keys
 * @return array The resulting array.
 */
function array_extract_values(array $from, array $keys) {
	$ret = array();
	foreach ($keys as $key)
		$ret[$key] = $from[$key];
	return $ret;
}
?>