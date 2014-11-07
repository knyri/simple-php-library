<?php
/**
 * @package io
 */


/**
 * @param array $data
 * @param string $template
 * @param string $destination
 * @param string $php
 */
function cache_template(array $data,$template,$destination,$php=false){
	if($php){
		ob_start();
		include $template;
		$content=ob_get_clean();
	}else{
		$content=file_get_contents($template);
		$end=strlen($content);
		$from=array();
		$to=array();
		foreach($data as $key=>$value){
			$from[]=_prepare_cache_template($key);
			$to[]=$value;
		}
		$content=str_replace($from,$to,$content);
	}
	file_put_contents($destination,$content);
}
function _prepare_cache_template($key){return '!'.$key.'!';}