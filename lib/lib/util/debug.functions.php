<?php
/**
 * @author Kenneth Pierce kcpiercejr@gmail.com
 * @package util
 */

/**
 * Dumps the session variable.
 */
function dump_session() {
	print_var($_SESSION);
}
/**
 * Dumps the variable to the ouput. (for HTML pages)
 * @param unknown_type $var
 */
function print_var($var) {
	echo '<pre>';
	var_export($var);
	echo '</pre>';
}