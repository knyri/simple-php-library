<?php
//ini_set('zlib.output_compression','On');
//ini_set('zlib.output_compression_level','3');
/*************************************************************************/
/* Configuration                                                         */
/*************************************************************************/

//Where to store cache files? Webserver needs wirte access!
$foocache['cachedir']	= $_SERVER['DOCUMENT_ROOT']."/cache";
//Maximum allowed age for cache files (in seconds)
$foocache['maxage']		= 24*3600; //one day
//Referencefile no cachefile may be older than that file
$foocache['reffile']	= $_SERVER['DOCUMENT_ROOT'].'/inc/recache';
//optional path to the tidy binary leave blank if you don't want it
$foocache['tidy']		= '';//$_SERVER['DOCUMENT_ROOT'].'/cgi-bin/tidy';
//options for tidy you should use the second one if your input files are
//not in iso-8859-1 charset
$foocache['tidyopts']	= '';//"-m -i -wrap 0 --drop-empty-paras false -ascii --tidy-mark false";
//$foocache['tidyopts'] = "-m -i -wrap 0 --drop-empty-paras false -raw --tidy-mark false";

//These tags are used when the optional 'query' marking is used (see README)
$foocache['hi_in']		= '<span style="background-color:#ffffcc">';
$foocache['hi_out']		= '</span>';
$foocache['gz_flag']=extension_loaded('zlib');//no change!

/*************************************************************************/
/* License                                                               */
/*************************************************************************/

// This is free Software (GPL)
// see COPYING for details

/*************************************************************************/
/* No changes needed below                                               */
/*************************************************************************/

function foocache_start(){
	if (defined("NOFOOCACHE")) return;

	global $foocache;
	$foocache['ltime']=foocache_time();

	//construct filename from requesturi and post params
	$md5  = md5($_SERVER['REQUEST_URI'].@join("",$_POST));
	if(stripos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false){
		$file = $foocache['cachedir'].'/'.$md5.'.gz';
	}else{
		$file = $foocache['cachedir'].'/'.$md5;
		$foocache['gz_flag']=false;
	}

	//valid cachedata available? load it and stop further processing
	if( (file_exists($file)) &&
	((time()-filemtime($file)) < $foocache['maxage']) &&
	(filemtime($foocache['reffile']) < filemtime($file)) &&
	(filemtime($file) > filemtime($_SERVER['SCRIPT_FILENAME'])) ){
		$cacheTime=filemtime($file);
		$gmt_mtime = gmdate('D, d M Y H:i:s',$cacheTime).' GMT';
		header('Last-Modified: '. $gmt_mtime ,true);
		header('Content-Length: '.filesize($file),true);
		if($foocache['gz_flag'])
			header('Content-Encoding: gzip',true);
		header('Expires: '.gmdate("D, d M Y H:i:s", $cacheTime + 3600*24*7) . " GMT",true);
		readfile($file);
		exit;
	}
	//no cachedata found start capturing output
	ob_start();
}

function foocache_stop(){
	if (defined("NOFOOCACHE")) return;
	global $foocache;

	//construct filename from requesturi and post params
	$md5  = md5($_SERVER['REQUEST_URI'].@join("",$_POST));
	if($foocache['gz_flag']){
		$file = $foocache['cachedir'].'/'.$md5.'.gz';
	}else{
		$file = $foocache['cachedir'].'/'.$md5;
	}
	//get contents from the outputbuffer
	$data = ob_get_contents();
	//$length = strlen($data);//ob_get_length();
	//flush the outputbuffer and stop capturing
	ob_end_clean();
	//write the cachefile
	if($foocache['gz_flag']){
		$rfile=gzopen($file,'wb');
		gzwrite($rfile,$data);
		gzclose($rfile);
	}else{
		$rfile=fopen($file,'w');
		fwrite($rfile,$data);
		fclose($rfile);
	}

	//get the cachefile
	$cacheTime=filemtime($file);
	$gmt_mtime = gmdate('D, d M Y H:i:s',$cacheTime).' GMT';
	header('Last-Modified: '. $gmt_mtime ,true);
	header('Content-Length: '.filesize($file),true);
	if($foocache['gz_flag'])
		header('Content-Encoding: gzip',true);
	header('Expires: '.gmdate("D, d M Y H:i:s", $cacheTime + 3600*24*7) . " GMT",true);
	//print the cachefile
	readfile($file);

	//clean up and delete all other old cache files
	foocache_cleanup();
}

function foocache_touch(){
	global $foocache;
	fwrite(fopen($foocache['reffile'], "w"),"touched");
}

function foocache_time(){
	list($usec, $sec) = explode(" ",microtime());
	return ((float)$usec + (float)$sec);
}

function foocache_cleanup(){
	global $foocache;

	$handle = opendir($foocache['cachedir']);
	while ($file = readdir ($handle)) {
		$file = $foocache['cachedir'].'/'.$file;
		if(is_dir($file)) continue;
		if(is_file($file) &&
		(( (time() - filemtime($file)) > $foocache['maxage']  ) ||
		( filemtime($foocache['reffile']) > filemtime($file) ))
		)
		{
			unlink($file);
		}
	}
	closedir($handle);
}