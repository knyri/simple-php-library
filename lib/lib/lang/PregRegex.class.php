<?php

/**
 * preg_* functions
 * @see https://www.php.net/manual/en/ref.pcre.php
 *
 */
class PregRegex{
	private $regex;
	public function __construct($regex){
		$this->regex= $regex;
	}
	/**
	 * See preg_match
	 *
	 * @param string $string
	 * @param array $matches
	 * @param number $flags
	 * @param number $offset
	 * @return number 1 if the pattern matches given subject, 0 if it does not, or false if an error occurred.
	 */
	public function matches($string, array &$matches= null, $flags= 0, $offset= 0){
		$ret= preg_match($this->regex, $string, $matches, $flags, $offset);
		if($ret === false){
			throw new Exception(self::_errorMsg());
		}
		return $ret;
	}
	private static function _errorMsg(){
		switch(preg_last_error()){
			case PREG_BACKTRACK_LIMIT_ERROR:
				return 'PREG Backtrack limit hit';
			case PREG_BAD_UTF8_ERROR:
				return 'PREG bad UTF character';
			case PREG_BAD_UTF8_OFFSET_ERROR:
				return 'PREG bad UTF offset';
			case PREG_INTERNAL_ERROR:
				return 'PREG internal error';
			case PREG_JIT_STACKLIMIT_ERROR:
				return 'PREG stack limit reached';
			case PREG_RECURSION_LIMIT_ERROR:
				return 'PREG recursion limit reached';
			default:
				return 'Unknown PREG error code: ' . preg_last_error();
		}
	}

	/**
	 * See preg_match_all
	 *
	 * @param string $string
	 * @param array $matches
	 * @param number $flags
	 * @param number $offset
	 * @return number the number of full pattern matches (which might be zero), or false if an error occurred
	 */
	public function allMatches($string, array &$matches= null, $flags= 0, $offset= 0){
		$ret= preg_match_all($this->regex, $string, $matches, $flags, $offset);
		if($ret === false){
			throw new Exception(self::_errorMsg());
		}
		return $ret;
	}


	/**
	 * See preg_filter
	 *
	 * @param string|array $replacement
	 * @param string|array $string
	 * @param int $limit
	 * @param int $count
	 * @return mixed an array if the subject parameter is an array, or a string otherwise.
	 *     If no matches are found or an error occurred, an empty array is returned when subject is an array or &null; otherwise.
	 */
	public function filter($replacement, $string, $limit= -1, &$count= null){
		$ret= preg_filter($this->regex, $replacement, $string, $limit, $count);
		if(preg_last_error() != PREG_NO_ERROR){
			throw new Exception(self::_errorMsg());
		}
		return $ret;
	}

	/**
	 * see preg_grep
	 *
	 * @param array $input
	 * @param number $flags
	 * @return array an array indexed using the keys from the input array
	 */
	public function grep(array $input, $flags= 0){
		$ret= preg_grep($this->regex, $input, $flags);
		if(preg_last_error() != PREG_NO_ERROR){
			throw new Exception(self::_errorMsg());
		}
		return $ret;
	}

	/**
	 * @param callable $callback
	 * @param array $subject
	 * @param int $limit
	 * @param int $count
	 * @param number $flags
	 * @return mixed an array if the subject parameter is an array, or a string otherwise. On errors the return value is &null;
	 *     If matches are found, the new subject will be returned, otherwise subject will be returned unchanged
	 */
	public function replaceCallback(callable $callback ,array $subject, $limit = -1, &$count = null ,$flags = 0 ){
		$ret= preg_replace_callback($this->regex, $callback, $subject, $limit, $count, $flags);
		if(preg_last_error() != PREG_NO_ERROR){
			throw new Exception(self::_errorMsg());
		}
		return $ret;
	}

	/**
	 * @param string $subject
	 * @param string $replacement
	 * @param int $limit
	 * @param int $count
	 * @return mixed an array if the subject parameter is an array, or a string otherwise.
	 *     If matches are found, the new subject will be returned, otherwise subject will be returned unchanged or &null; if an error occurred.
	 */
	public function replace($subject, $replacement, $limit= -1, &$count= null){
		$ret= preg_replace($this->regex, $replacement, $subject, $limit, $count);
		if(preg_last_error() != PREG_NO_ERROR){
			throw new Exception(self::_errorMsg());
		}
		return $ret;
	}

	/**
	 * @param unknown $subject
	 * @param unknown $limit
	 * @param number $flags
	 * @return array an array containing substrings of subject split along boundaries matched by pattern, or false on failure.
	 */
	public function split($subject, $limit= -1, $flags= 0){
		$ret= preg_split($this->regex, $subject, $limit, $flags);
		if(preg_last_error() != PREG_NO_ERROR){
			throw new Exception(self::_errorMsg());
		}
		return $ret;
	}

	/**
	 * @return number one of the following constants (explained on their own page): PREG_NO_ERROR PREG_INTERNAL_ERROR PREG_BACKTRACK_LIMIT_ERROR (see also pcre.backtrack_limit) PREG_RECURSION_LIMIT_ERROR (see also pcre.recursion_limit) PREG_BAD_UTF8_ERROR PREG_BAD_UTF8_OFFSET_ERROR (since PHP 5.3.0) PREG_JIT_STACKLIMIT_ERROR (since PHP 7.0.0)
	 */
	public static function getError(){
		return preg_last_error();
	}
	/**
	 * See preg_quote
	 *
	 * @param string $string
	 * @param string $delim
	 * @return string
	 */
	public static function quote($string, $delim= null){
		return preg_quote($str, $delim);
	}

}