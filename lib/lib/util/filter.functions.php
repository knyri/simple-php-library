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
function filter_msword($var,$charset='utf-8') {
	static $chars=null;
	$charset=strtolower($charset);
	if (is_array($var)) {
		foreach ($var as $key => $value) {
			$var[$key] = filter_msword($value,$charset);
		}
		return $var;
	}
	if($chars==null){
		$chars = array(
			'utf-8'=>array(
				"\xe2\x80\x9a" => '&sbquo;',
				"\xe2\x80\x99" => '\'',
				"\xe2\x80\x9b" => '\'',
				"\xe2\x80\x9d" => '"',
				"\xe2\x80\x9f" => '"',
				"\xe2\x80\xa2" => '&bull;',
				"\xe2\x80\x93" => '&ndash;',
				"\xe2\x80\x94" => '&mdash;',
				"\xe2\x80\xa6" => '&hellip;',
				"\xc2\xa1" =>	'&iexcl;',
				"\xc2\xa2" =>	'&cent;',
				"\xc2\xa3" =>	'&pound;',
				"\xc2\xa4" =>	'&curren;',
				"\xc2\xa5" =>	'&yen;',
				"\xc2\xa6" =>	'&brvbar;',
				"\xc2\xa7" =>	'&sect;',
				"\xc2\xa8" =>	'&uml;',
				"\xc2\xa9" =>	'&copy;',
				"\xc2\xaa" =>	'&ordf;',
				"\xc2\xab" =>	'&laquo;',
				"\xc2\xac" =>	'&not;',
				"\xc2\xae" =>	'&reg;',
				"\xc2\xaf" =>	'&macr;',
				"\xc2\xb0" =>	'&deg;',
				"\xc2\xb1" =>	'&plusmn;',
				"\xc2\xb2" =>	'&sup2;',
				"\xc2\xb3" =>	'&sup3;',
				"\xc2\xb4" =>	'&acute;',
				"\xc2\xb5" =>	'&micro;',
				"\xc2\xb6" =>	'&para;',
				"\xc2\xb7" =>	'&middot;',
				"\xc2\xb8" =>	'&cedil;',
				"\xc2\xb9" =>	'&sup1;',
				"\xc2\xba" =>	'&ordm;',
				"\xc2\xbb" =>	'&raquo;',
				"\xc2\xbc" =>	'&frac14;',
				"\xc2\xbd" =>	'&frac12;',
				"\xc2\xbe" =>	'&frac34;',
				"\xc2\xbd" =>	'&iquest;',
				"\xc3\x97" =>	'&times;',
				"\xc3\xb7" =>	'&divide;',
				"\x00\x5e" => '&circ;',
				"\xc2\xa0"=>' ',
				//malformed
				"\x00\xa9" =>	'&copy;',
				"\x00\xae" =>	'&reg;',
				"\x00\xb2" =>	'&sup2;',
				"\x00\xb3" =>	'&sup3;',
				"\x00\xb4" =>	'&acute;',
				"\x00\xb7" =>	'&middot;',
				"\x00\xb9" =>	'&sup1;',
				"\x00\xbc" =>	'&frac14;',
				"\x00\xbd" =>	'&frac12;',
				"\x00\xbe" =>	'&frac34;',
				"\x00\x18" => '\'',
				"\x00\x19" => '\'',
				"\x00\x1c" => '"',
				"\x00\x1d" => '"',
				"\x00\x22" => '&bull;',
				"\x00\x13" => '&ndash;',
				"\x00\x14" => '&mdash;',
				"\x00\x26" => '&hellip;',
			),
			'windows-1252'=>array(
				"\xa0"=>' ',
				"\xa1" =>	'&iexcl;',
				"\xa2" =>	'&cent;',
				"\xa3" =>	'&pound;',
				"\xa4" =>	'&curren;',
				"\xa5" =>	'&yen;',
				"\xa6" =>	'&brvbar;',
				"\xa7" =>	'&sect;',
				"\xa8" =>	'&uml;',
				"\xa9" =>	'&copy;',
				"\xaa" =>	'&ordf;',
				"\xab" =>	'&laquo;',
				"\xac" =>	'&not;',
				"\xae" =>	'&reg;',
				"\xaf" =>	'&macr;',
				"\xb0" =>	'&deg;',
				"\xb1" =>	'&plusmn;',
				"\xb2" =>	'&sup2;',
				"\xb3" =>	'&sup3;',
				"\xb4" =>	'&acute;',
				"\xb6" =>	'&para;',
				"\xb7" =>	'&middot;',
				"\xb8" =>	'&cedil;',
				"\xb9" =>	'&sup1;',
				"\xba" =>	'&ordm;',
				"\xbb" =>	'&raquo;',
				"\xbc" =>	'&frac14;',
				"\xbd" =>	'&frac12;',
				"\xbe" =>	'&frac34;',
				"\xbf" =>	'&iquest;',
				"\xd7" =>	'&times;',
				"\xf7" =>	'&divide;',
				"\x92"=>'\'',
				"\x97"=>'&mdash;',
				"\x96"=>'&ndash;',
				"\x85"=>'&hellip;',
				"\x99"=>'&tm;'
			),/*
			,
			'windows-1252'=>array(
				"\x00" =>	'&iexcl;',
				"\x00" =>	'&cent;',
				"\x00" =>	'&pound;',
				"\x00" =>	'&curren;',
				"\x00" =>	'&yen;',
				"\x00" =>	'&brvbar;',
				"\x00" =>	'&sect;',
				"\x00" =>	'&uml;',
				"\x00" =>	'&copy;',
				"\x00" =>	'&ordf;',
				"\x00" =>	'&laquo;',
				"\x00" =>	'&not;',
				"\x00" =>	'&reg;',
				"\x00" =>	'&macr;',
				"\x00" =>	'&deg;',
				"\x00" =>	'&plusmn;',
				"\x00" =>	'&sup2;',
				"\x00" =>	'&sup3;',
				"\x00" =>	'&acute;',
				"\x00" =>	'&micro;',
				"\x00" =>	'&para;',
				"\x00" =>	'&middot;',
				"\x00" =>	'&cedil;',
				"\x00" =>	'&sup1;',
				"\x00" =>	'&ordm;',
				"\x00" =>	'&raquo;',
				"\x00" =>	'&frac14;',
				"\x00" =>	'&frac12;',
				"\x00" =>	'&frac34;',
				"\x00" =>	'&iquest;',
				"\x00" =>	'&times;',
				"\x00" =>	'&divide;',
				"\x20" => '&sbquo;',
				"\x00" => '&circ;',
				"\x20" => "\'',
				"\x20" => "\'',
				"\x20" => '"',
				"\x20" => '"',
				"\x20" => '&bull;',
				"\x20" => '&ndash;',
				"\x20" => '&mdash;',
				"\x20" => '&hellip;',
			)
			*/
		);
		$chars['cp1252']=&$chars['windows-1252'];

	}
	$var = strtr($var, $chars[$charset]);
	return $var;
}
function filter_rem_url_reserved($str){
	$str=str_replace(array(':','/',',',';','<','>','?','\\',')','(','*','&','^','%','$','#','@','!','`','~','\'','"','[',']','{','}','|','_','=','+'),'',$str);
	return str_replace(' ','-',$str);
}
?>