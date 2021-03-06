<?php
/**
 * @package lang
 */
/**
 * Shallow test to see if all elements are empty.
 * @param array $arr
 * @return boolean
 */
function isEmpty(array $arr) {
	foreach ($arr as $ele) {
		if (!empty($ele)) return false;
	}
	return true;
}
/**
 * Works the same as empty() except 0 is not considered empty.
 * @param mixed $var
 * @return boolean
 */
function blank($var){return (empty($var)&&!is_numeric($var));}
/**
 * Tests if $var is between $start and $end. By default it returns false
 * if $var equals $start or $end.
 * @param int $var
 * @param int $start
 * @param int $end
 * @param boolean $inclusive [optional] defaults to false
 * @return boolean
 */
function between($var,$start,$end,$inclusive=false){
	if($inclusive)
		return $var>=$start&&$var<=$end;
	else
		return $var>$start&&$var<$end;
}
/**
 * @param number $options Bit options
 * @param number $option Bit(s) to test for
 * @return boolean ($options & $option) == $option
 */
function is_set($options,$option){
	return ($options&$option) == $option;
}