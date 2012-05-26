<?php
/**
 * @author Kenneth Pierce kcpiercejr@gmail.com
 */

if (!defined('root'))
	define('root', $_SERVER['DOCUMENT_ROOT'].'/');
require_once root.'lib/lang/string.php';
echo '<?xml version="1.0" encoding="Cp1252" ?>';
$path = $_GET['path'];
$file = $_GET['file'];
if (str_empty($path))
	$error = 'No path.';
else if (str_empty($file))
	$error = 'No file.';
else
	$file = $path.'/'.$file;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=Cp1252" />
<title>Editing <?php echo $file; ?></title>
</head>
<body>
<?php
if ($error) {
	?><p><?php echo $error; ?></p><?php
} else {
	?>
	<div>
	<form method="post">
	<fieldset>
		<textarea rows="30" cols="60"><?php echo file_get_contents($file); ?></textarea>
		<input name="action" type="submit" value="save" /><input name="action" type="submit" value="cancel" />
	</fieldset>
	</form>
	</div>
	<?php
}
?>
</body>
</html>