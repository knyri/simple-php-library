<?php
/**
 * @author Kenneth Pierce kcpiercejr@gmail.com
 */

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
	echo '<pre>';
	echo "<b>$etype</b>($errno):$errstr in <b>$errfile</b>($errline)\n<b>Variables:</b>\n";
	array_walk($errcontext,create_function('$a,$b','print "\\$$b=".var_export($a,true)."\n";'));
	echo "<b>Backtrace:</b>\n";
	debug_print_backtrace();
	echo '</pre>';
	if($errno==E_ERROR||$errno==E_COMPILE_ERROR||$errno==E_CORE_ERROR||$errno==E_USER_ERROR||$errno==E_RECOVERABLE_ERROR){
		die('Server Error');
	}
	return true;
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