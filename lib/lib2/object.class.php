<?php
/**
 * @author Kenneth Pierce
 */

/**
 * Base class.
 * @author Kenneth Pierce
 *
 */
class Object {
	/**
	 * Calls the method on this object.
	 * @param string $method Method to be called.
	 * @param array $params Array of parameters to be passed to the method
	 * @return mixed The result of the method.
	 */
	function callMethod($method, $params) {
		switch (count($params)) {
			case 0:
				return $this->{$method}();
			case 1:
				return $this->{$method}($params[0]);
			case 2:
				return $this->{$method}($params[0], $params[1]);
			case 3:
				return $this->{$method}($params[0], $params[1], $params[2]);
			case 4:
				return $this->{$method}($params[0], $params[1], $params[2], $params[3]);
			case 5:
				return $this->{$method}($params[0], $params[1], $params[2], $params[3], $params[4]);
			default:
				return call_user_func_array(array(&$this, $method), $params);
		}
	}
}
?>