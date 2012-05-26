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
		128 => '€',
		130 => '‚',
		131 => 'ƒ',
		132 => '„',
		133 => '…',
		134 => '†',
		135 => '‡',
		136 => 'ˆ',
		137 => '‰',
		138 => 'Š',
		139 => '‹',
		140 => 'Œ',
		142 => '',
		145 => '‘',
		146 => '’',
		147 => '“',
		148 => '”',
		149 => '•',
		150 => '–',
		151 => '—',
		152 => '˜',
		153 => '™',
		154 => 'š',
		155 => '›',
		156 => 'œ',
		158 => '',
		159 => 'Ÿ');
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
		'¡' =>	'&iexcl;',
		'¢' =>	'&cent;',
		'£' =>	'&pound;',
		'¤' =>	'&curren;',
		'¥' =>	'&yen;',
		'¦' =>	'&brvbar;',
		'§' =>	'&sect;',
		'¨' =>	'&uml;',
		'©' =>	'&copy;',
		'ª' =>	'&ordf;',
		'«' =>	'&laquo;',
		'¬' =>	'&not;',
		'®' =>	'&reg;',
		'¯' =>	'&macr;',
		'°' =>	'&deg;',
		'±' =>	'&plusmn;',
		'²' =>	'&sup2;',
		'³' =>	'&sup3;',
		'´' =>	'&acute;',
		'µ' =>	'&micro;',
		'¶' =>	'&para;',
		'·' =>	'&middot;',
		'¸' =>	'&cedil;',
		'¹' =>	'&sup1;',
		'º' =>	'&ordm;',
		'»' =>	'&raquo;',
		'¼' =>	'&frac14;',
		'½' =>	'&frac12;',
		'¾' =>	'&frac34;',
		'¿' =>	'&iquest;',
		'×' =>	'&times;',
		'÷' =>	'&divide;',
		//128 => '€',
		'‚' => ',',
		//131 => 'ƒ',
		//132 => '„',
		//133 => '…',
		//134 => '†',
		//135 => '‡',
		'ˆ' => '^',
		//137 => '‰',
		//138 => 'Š',
		'‹' => '&lt;',
		//140 => 'Œ',
		//142 => '',
		'‘' => '\'',
		'’' => '\'',
		'“' => '"',
		'”' => '"',
		'•' => '*',
		'–' => '-',
		'—' => '--',
		'™' => '&#153;',
		//154 => 'š',
		'›' => '&gt;',
		' ' => ' ',
		//156 => 'œ',
		//158 => '',
		//159 => 'Ÿ'
	);
	$var = str_replace(array_keys($chars), $chars, $var);
	return $var;
}
function filter_rem_url_reserved($str){
	$str=str_replace(array(':','/',',',';','<','>','?','\\',')','(','*','&','^','%','$','#','@','!','`','~','\'','"','[',']','{','}','|','_','=','+'),'',$str);
	return str_replace(' ','-',$str);
}
?>