<?php
function isEmpty(array $arr) {
	foreach ($arr as $ele) {
		if (!empty($ele)) return false;
	}
	return true;
}
function blank($var){return (empty($var)&&!is_numeric($var));}
function between($var,$start,$end){return $var>$start&&$var<$end;}