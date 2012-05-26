<?php
function filter_mq($var){
	if(get_magic_quotes_gpc())
		return stripcslashes($var);
	return $var;
}
function filter_special($var,$nl2br = false){
	if (is_array($var)) {
		foreach ($var as $key => $value) {
			$var[$key] = filter_special($value);
		}
		return $var;
	}
	$chars = array(
		128 => '�',
		130 => '�',
		131 => '�',
		132 => '�',
		133 => '�',
		134 => '�',
		135 => '�',
		136 => '�',
		137 => '�',
		138 => '�',
		139 => '�',
		140 => '�',
		142 => '�',
		145 => '�',
		146 => '�',
		147 => '�',
		148 => '�',
		149 => '�',
		150 => '�',
		151 => '�',
		152 => '�',
		153 => '�',
		154 => '�',
		155 => '�',
		156 => '�',
		158 => '�',
		159 => '�');
	$var = str_replace(array_map('chr', array_keys($chars)), $chars, $var);
	if($nl2br){
		return nl2br($var);
	} else {
		return $var;
	}
}
function filter_msword($var) {
	if (is_array($var)) {
		foreach ($var as $key => $value) {
			$var[$key] = filter_msword($value);
		}
		return $var;
	}
	$chars = array(
		'�' =>	'&iexcl;',
		'�' =>	'&cent;',
		'�' =>	'&pound;',
		'�' =>	'&curren;',
		'�' =>	'&yen;',
		'�' =>	'&brvbar;',
		'�' =>	'&sect;',
		'�' =>	'&uml;',
		'�' =>	'&copy;',
		'�' =>	'&ordf;',
		'�' =>	'&laquo;',
		'�' =>	'&not;',
		'�' =>	'&reg;',
		'�' =>	'&macr;',
		'�' =>	'&deg;',
		'�' =>	'&plusmn;',
		'�' =>	'&sup2;',
		'�' =>	'&sup3;',
		'�' =>	'&acute;',
		'�' =>	'&micro;',
		'�' =>	'&para;',
		'�' =>	'&middot;',
		'�' =>	'&cedil;',
		'�' =>	'&sup1;',
		'�' =>	'&ordm;',
		'�' =>	'&raquo;',
		'�' =>	'&frac14;',
		'�' =>	'&frac12;',
		'�' =>	'&frac34;',
		'�' =>	'&iquest;',
		'�' =>	'&times;',
		'�' =>	'&divide;',
		//128 => '�',
		'�' => ',',
		//131 => '�',
		//132 => '�',
		//133 => '�',
		//134 => '�',
		//135 => '�',
		'�' => '^',
		//137 => '�',
		//138 => '�',
		'�' => '&lt;',
		//140 => '�',
		//142 => '�',
		'�' => '\'',
		'�' => '\'',
		'�' => '"',
		'�' => '"',
		'�' => '*',
		'�' => '-',
		'�' => '--',
		'�' => '&#153;',
		//154 => '�',
		'�' => '&gt;',
		'�' => ' ',
		//156 => '�',
		//158 => '�',
		//159 => '�'
	);
	$var = str_replace(array_keys($chars), $chars, $var);
	return $var;
}
function filter_rem_url_reserved($str){
	$str=str_replace(array(':','/',',',';','<','>','?','\\',')','(','*','&','^','%','$','#','@','!','`','~','\'','"','[',']','{','}','|','_','=','+'),'',$str);
	return str_replace(' ','-',$str);
}
?>