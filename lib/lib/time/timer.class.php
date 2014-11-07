<?php
/**
 * @author Kenneth Pierce kcpiercejr@gmail.com
 */

$__timers = array();
function addTimer($name) {
	global $__timers;
	$__timers[$name] = microtime(true);
}
function resetTimer($name) {
	global $__timers;
	$__timers[$name] = microtime(true);
}
function getStartTime($name) {
	global $__timers;
	return $__timers[$name];
}
function getElapsed($name) {
	global $__timers;
	return microtime(true)-$__timers[$name];
}
