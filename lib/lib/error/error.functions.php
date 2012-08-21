<?php
/**
 * @author Kenneth Pierce kcpiercejr@gmail.com
 */

define('E_FATAL', E_ERROR | E_USER_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_RECOVERABLE_ERROR);

/**
 * Creates an image from an array of text.
 * @param array $error List of errors. One line per entry.
 * @return resource The resulting image.
 */
function errimg($error) {
	// $error is an array of error messages, each taking up one line
	// initialization
	$font_size = 2;
	$text_width = imagefontwidth($font_size);
	$text_height = imagefontheight($font_size);
	$width = 0;
	// the height of the image will be the number of items in $error
	$height = count($error);

	// this gets the length of the longest string, in characters to determine
	// the width of the output image
	for($x = 0; $x < count($error); $x++) {
		if(strlen($error[$x]) > $width) {
		 $width = strlen($error[$x]);
		}
	}

	// next we turn the height and width into pixel values
	$width = $width * $text_width;
	$height = $height * $text_height;

	// create image with dimensions to fit text, plus two extra rows and
	// two extra columns for border
	$im = imagecreatetruecolor($width+(2*$text_width),$height+(2*$text_height));
	if($im){
		// image creation success
		$text_color = imagecolorallocate($im, 233, 14, 91);
		// this loop outputs the error message to the image
		for($x = 0; $x < count($error); $x++) {
		 // imagestring(image, font, x, y, msg, color);
		 imagestring($im, $font_size, $text_width,$text_height + $x * $text_height, $error[$x],$text_color);
		}
		// now, render your image using your favorite image* function
		// (imagejpeg, for instance)
		return $im;
	} else {
		// image creation failed, so just dump the array along with extra error
		$error[] = "Is GD Installed?";
		die(var_dump($error));
	}
}
function default_error_handler($errno, $errstr, $errfile, $errline,$errcontext) {
	static $c_error=0;
	if($c_error==0){
		echo '<script>function toggle(id){var item=document.getElementById(id);if(item.className=="collapsed"){item.className="expanded"}else{item.className="collapsed"}}</script>';
		echo '<style>.collapsed{display:none} pre{background:#fff !important;color:#000 !important}</style>';
	}
	$c_error++;
	switch ($errno) {
		case E_NOTICE:
		case E_USER_NOTICE:
			$etype = "Notice";
		break;
		case E_WARNING:
		case E_USER_WARNING:
			$etype = "Warning";
		break;
		case E_ERROR:
		case E_USER_ERROR:
			$etype = "Fatal Error";
		break;
		case E_STRICT:
			return false;//Let PHP handle these. Always causes a recursive overflow.
		break;
		case E_RECOVERABLE_ERROR:
			$etype='Recoverable Error';
			break;
		default:
			$etype = "Unknown";
			break;
	}

	echo "<p><b>$etype</b>($errno):$errstr in <b>$errfile</b>($errline)</p>";
	echo '<p><b onclick="toggle(\'errorvars'.$c_error.'\')">Variables:</b><small>(click to show)</small>'.
		'<pre id="errorvars'.$c_error.'" class="collapsed">';
	if($errcontext && count($errcontext))
		array_walk($errcontext,create_function('$a,$b','print "\\$$b=";var_dump($a);echo "<br>";'));
	echo "</pre><p><b>Backtrace:</b>\n<pre>";
	foreach(debug_backtrace() as $k=>$v){
		//if($v['function']=='default_error_handler')continue;
		if($v['function'] == "include" || $v['function'] == "include_once" || $v['function'] == "require_once" || $v['function'] == "require"){
			echo "#$k <b>{$v['function']}</b>({$v['args'][0]}) called at [{$v['file']}:{$v['line']}]<br />";
		}else{
			echo "#$k <b>{$v['function']}</b>() called at [{$v['file']}:{$v['line']}]<br />";
		}
	}
	//debug_print_backtrace();
	echo '</pre>';
	if($errno==E_ERROR||$errno==E_COMPILE_ERROR||$errno==E_CORE_ERROR||$errno==E_USER_ERROR||$errno==E_RECOVERABLE_ERROR){
		die('Server Error');
	}
	return true;
}
function shutdown(){
	$error = error_get_last();

	if($error && ($error['type'] & E_FATAL)){
		default_error_handler($error['type'], $error['message'], $error['file'], $error['line']);
	}

}
function default_exception_handler(Exception $e) {
	echo '<pre>';
	echo 'Uncaught exception:'.get_class($e).': ('.$e->getCode().')'.' in '.$e->getFile().'('.$e->getLine().'):'.$e->getMessage()."\n";
	echo $e->getTraceAsString();
	echo '</pre>';
}
function default_customexception_handler(CustomException $e) {
	echo '<pre>';
	echo $e->__toString();
	echo '</pre>';
}
set_exception_handler('default_exception_handler');