<?php
function combine_attrib(array $attrib=null){
	if($attrib==null)return '';
	$res='';
	foreach($attrib as $key=>$value){
		$res.=$key.'="'.$value.'" ';
	}
	return trim($res);
}