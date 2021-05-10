<?php
/**
 * Exclusive (start, end)
 * @param number $num
 * @param number $start
 * @param number $end
 * @return boolean
 */
function numberBetween($num, $start, $end){
	return $num > $start && $num < $end;
}
/**
 * Inclusive [start, end]
 * @param number $num
 * @param number $start
 * @param number $end
 * @return boolean
 */
function numberInRange($num, $start, $end){
	return $num >= $start && $num <= $end;
}