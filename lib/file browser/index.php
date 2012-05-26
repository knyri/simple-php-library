<?php
/**
 * @author Kenneth Pierce kcpiercejr@gmail.com
 */

/**
 * Gets a string version of a PHP file upload error
 * @param $error_code
 */
function file_upload_error_message($error_code) {
    switch ($error_code) {
        case UPLOAD_ERR_INI_SIZE:
            return 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
        case UPLOAD_ERR_FORM_SIZE:
            return 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
        case UPLOAD_ERR_PARTIAL:
            return 'The uploaded file was only partially uploaded';
        case UPLOAD_ERR_NO_FILE:
            return 'No file was uploaded';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Missing a temporary folder';
        case UPLOAD_ERR_CANT_WRITE:
            return 'Failed to write file to disk';
        case UPLOAD_ERR_EXTENSION:
            return 'File upload stopped by extension';
        default:
            return 'Unknown upload error';
    }
}
if (!isset($_GET['path'])) {
	$path = $_SERVER['DOCUMENT_ROOT'];
} else {
	$path = $_GET['path'];
}
$path = realpath($path);
if (isset($_FILES['file'])) {
		foreach ($_FILES['file']['error'] as $key => $error) {
			if ($error == UPLOAD_ERR_OK) {
				$tmp_name = $_FILES['file']['tmp_name'][$key];
				$name = $_FILES['file']['name'][$key];
				$name = str_ireplace('%', '%25', $name);
				move_uploaded_file($tmp_name, $path."/$name");
			} else if ($error != UPLOAD_ERR_NO_FILE) {
				$name = $_FILES['pictures']['name'][$key];
				$error = "$name failed to upload: ".file_upload_error_message($error);
			}
		}
	}
$dir = opendir($path);
$folders = array();
$files = array();
while ($file = readdir($dir)) {
	if (is_dir($path.'/'.$file)) {
		$folders[] = $file;
	} else {
		$files[] = $file;
	}
}
sort($folders);
sort($files);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
<meta name="robots" content="NOINDEX, NOFOLLOW" />
<title>File Explorer</title>
<style type="text/css">
#folders {
	width: 200px;
	float: left;
	overflow: scroll;
}
#folderlist {
	list-style-image: url('http://<?php echo $_SERVER['SERVER_NAME']; ?>/images/icons/file/folder.png');
}
</style>
</head>
<body>
<p><?php echo $path; ?></p>
<div><?php if ($error) echo $error; ?>
	<form method="post">
		<fieldset><input name="file" type="file" /><input type="submit" value="upload" /></fieldset>
	</form>
</div>

<div id="folders">
<?php include 'folder.php';?>
</div>
<div>
<?php include 'file.php'; ?>
</div>
</body>
</html>