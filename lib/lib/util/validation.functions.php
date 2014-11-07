<?php
/**
 * @author Kenneth Pierce kcpiercejr@gmail.com
 * @package util
 */
//echo "included";
/* ISVALID REGULAR EXPRESSIONS */
define('REG_DATE'			,'/^[1-9][0-9]{3}-0[1-9]|1[0-2]-0[1-9]|[12][0-9]|3[01]$/');
define('REG_RATING'			,'/^10|[1-9]$/');
define('REG_STATE'			,'/^A[LKZR]|C[AOT]|D[EC]|FL|GA|HI|I[DLNA]|K[SY]|LA|M[EDAINSOT]|N[EVHJMYCD]|O[HKR]|PA|RI|S[CD]|T[NX]|UT|V[TA]|W[AVIY]$/');
define('REG_DIGIT_SIGNED'	,'/^[+-]?\d+$/');
define('REG_EMAIL'			,'/^[a-zA-Z0-9][a-zA-Z0-9._-]*[a-zA-Z0-9]@[a-zA-Z0-9][a-zA-Z0-9._-]*.[a-zA-Z]{2,4}$/');
define('REG_DIGIT_UNSIGNED'	,'/^\d+$/');
define('REG_PASSWORD'		,'/^[\da-zA-Z_]{5,15}$/');
define('REG_TEXT'			,'/^[\w\s\d,\.?\/;:\'"{\[\]}`~!@#\$%\^&*\(\)\|\\-]+$/');
define('REG_WORD'			,'/^[a-zA-Z]+$/');
define('REG_NAME'			,'/^[a-zA-Z]{2,15}$/');
define('REG_ZIP'			,'/^\d{5}(-\d{4})?$/');
define('REG_CITY'			,'/^[a-zA-Z ]+$/');
define('REG_CURRENCY'		,/*'/^(\x{0024}|'.
								'\x{0024}\x{0062}|'.
								'\x{0024}\x{0055}|'.
								'\x{0042}\x{0073}|'.
								'\x{0042}\x{002f}\x{002e}|'.
								'\x{0042}\x{005a}\x{0024}|'.
								'\x{0043}\x{0048}\x{0046}|'.
								'\x{0043}\x{0024}|'.
								'\x{0046}\x{0074}|'.
								'\x{0047}\x{0073}|'.
								'\x{004b}\x{004d}|'.
								'\x{004b}\x{010d}|'.
								'\x{004c}\x{0073}|'.
								'\x{004c}\x{0074}|'.
								'\x{004d}\x{0054}|'.
								'\x{004e}\x{0054}\x{0024}|'.
								'\x{0050}|'.
								'\x{0050}\x{0068}\x{0070}|'.
								'\x{0051}|'.
								'\x{0052}\x{0024}|'.
								'\x{0052}\x{004d}|'.
								'\x{0052}\x{0070}|'.
								'\x{0053}\x{002f}\x{002e}|'.
								'\x{0054}\x{0054}\x{0024}|'.
								'\x{0054}\x{004c}|'.
								'\x{005a}\x{0024}|'.
								'\x{006b}\x{006e}|'.
								'\x{006b}\x{0072}|'.
								'\x{006c}\x{0065}\x{0069}|'.
								'\x{0070}\x{002e}|'.
								'\x{007a}\x{0142}|'.
								'\x{00a2}|'.
								'\x{00a3}|'.
								'\x{00a5}|'.
								'\x{0192}|'.
								'\x{0414}\x{0438}\x{043d}\x{002e}|'.
								'\x{043b}\x{0432}|'.
								'\x{043c}\x{0430}\x{043d}|'.
								'\x{0434}\x{0435}\x{043d}|'.
								'\x{0440}\x{0443}\x{0431}|'.
								'\x{060b}|'.
								'\x{0e3f}|'.
								'\x{17db}|'.
								'\x{20a1}|'.
								'\x{20a4}|'.
								'\x{20a6}|'.
								'\x{20a8}|'.
								'\x{20a9}|'.
								'\x{20aa}|'.
								'\x{20ab}|'.
								'\x{20ac}|'.
								'\x{20ad}|'.
								'\x{20ae}|'.
								'\x{20b1}|'.
								'\x{20b4}|'.
								'\x{fdfc}'.
								')?*/'/^((\d{1,3}(,\d\d\d)+)|\d+)(\.\d{2})?$/');
/* ISVALID FUNCTIONS */
function is_currency($value, $nullable = false) {
	if ($value==null || $value=='') {
		return $nullable;
	}
	return preg_match(REG_CURRENCY, $value);
}
function is_multi_word($value, $nullable = false) {
	if ($value==null || $value=='') {
		return $nullable;
	}
	return preg_match(REG_CITY, $value);
}
/**
 * Tests to see if the value is an integer.
 * @param string|number $value
 * @param boolean $nullable
 * @return boolean
 */
function is_digit($value, $nullable = false) {
	if ($value==null || $value=='') {
		return $nullable;
	}
	return ((is_numeric($value) === true) && ((int)$value == $value));
	//return preg_match(REG_DIGIT_UNSIGNED, $value);
}
/**
 * Tests the form elements created by form_create_phone($name,...) to see if it is a valid phone number.
 * @param array $phone_ary The phone form array. $_REQUEST[$name].
 * @param boolean $nullable Whether or not this element is allowed to be empty. Defaults to false.
 * @return boolean
 */
function is_form_phone(array $phone_ary, $nullable = false) {
	if ($nullable && empty($phone_ary['area']) && empty($phone_ary['prefix']) && empty($phone_ary['line'])) return true;
	if (is_digit($phone_ary['area']) && abs((int)$phone_ary['area']) < 1000) {
		if (is_digit($phone_ary['prefix']) && abs((int)$phone_ary['prefix']) < 1000) {
			if (is_digit($phone_ary['line']) && abs((int)$phone_ary['line']) < 10000) {
				return true;
			}
		}
	}
	return false;
}
/**
 * Tests to see if the value is text.
 * @param string $value
 * @param boolean $nullable May it be null or empty? Default is false.
 * @return number|boolean 0 or false on failure. 1 or true on success.
 */
function is_text($value, $nullable = false) {
	if ($value==null || $value=='') {
		return $nullable;
	}
	return preg_match(REG_TEXT, $value);
}
/**
 * Tests to see if the value is Y, N, y, or n.
 * @param string $value
 * @param boolean $nullable May it be null or empty? Default is false.
 * @return boolean
 */
function is_YN($value, $nullable = false) {
	if ($value==null || $value=='') {
		return $nullable;
	}
	return ($value == 'Y' || $value == 'N' || $value == 'y' || $value == 'n');
}
function is_city($value, $nullable = false) {
	if ($value==null || $value=='') {
		return $nullable;
	}
	return preg_match(REG_CITY, $value);
}
function is_address($value, $nullable = false) {
	if ($value==null || $value=='') {
		return $nullable;
	}
	return preg_match(REG_TEXT, $value);
}
function is_state($value, $nullable = false) {
	if ($value==null || $value=='') {
		return $nullable;
	}
	return preg_match(REG_STATE, $value);
}
function is_date($value, $nullable = false) {
	if ($value==null || $value=='') {
		return $nullable;
	}
	return preg_match(REG_DATE, $value);
}
function is_user_pass($value, $nullable = false) {
	if ($value==null || $value=='') {
		return $nullable;
	}
	return preg_match(REG_PASSWORD, $value);
}
function is_email($value, $nullable = false) {
	if ($value==null || $value=='') {
		return $nullable;
	}
	return preg_match(REG_EMAIL, $value);
}
function is_name($value, $nullable = false) {
	if ($value==null || $value=='') {
		return $nullable;
	}
	return preg_match(REG_NAME, $value);
}
function is_zip($value, $nullable = false) {
	if ($value==null || $value=='') {
		return $nullable;
	}
	return preg_match(REG_ZIP, $value);
}
function hash_pass($pass) {
	//echo $pass;
	$salt1 = "feed46babe";
	$salt2 = "bo5oga";
	$hash1 = sha1($salt1.$pass);
	$hash2 = md5($pass.$salt2);
	//echo $hash1 . " " . $hash2;
	$finalhash = substr($hash1, 0, 15) . substr($hash2, 16);
	return $finalhash;
}
function verificationCode($uname) {
	return sha1('deadbeefdeafbaba'.$uname.'feedbabe');
}
function getValidURL($url) {
	if (empty($url)) {
		return null;
	}

	$index = stripos('http://', $url);
	if ($index == false) {
		$index = 0;
		$index = stripos('https://', $url);
		if ($index == false) {
			$index = 0;
		} else {
			$index = 8;
		}
	} else {
		$index = 7;
	}
	$end = strpos('.', $url, $index);
	if ($end == false)
		return null;
	$end = strrpos('.', $url);
	$end = substr($url, $end);
	if (!preg_match('\.[a-zA-Z]{2,4}', $url))
		return null;
	return 'http://'.substr($url, $index);
}
function fixurl($url) {
	if ( !preg_match('!^(ftp|http(s)?)://!', $url) ) {
		$url = 'http://'.$url;
	}
	return $url;
}
?>