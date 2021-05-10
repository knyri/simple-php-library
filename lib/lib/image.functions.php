<?php
PackageManager::requireFunctionOnce('io.file');
function is_image($file){
	return getimagesize($file);
}
/**
 * @param resource $dest
 * @param string $file
 * @param array $info Image info
 * @return boolean
 */
function image_save(&$dest,$file,array $info){
	$mime = explode('/', $info['mime']);
	$ext=strtolower(file_extention($file));
	if($mime[1] == 'jpeg' || $mime[1] == 'jpg'){
		if($ext=='jpg'||$ext=='jpeg')
			imagejpeg($dest, $file);
		else
			imagejpeg($dest, $file.'.jpg');
	}else if($mime[1] == 'gif'){
		if($ext=='gif')
			imagegif($dest, $file);
		else
			imagegif($dest, $file.'.gif');
	}else if($mime[1] == 'png'){
		if($ext=='png')
			imagepng($dest, $file);
		else
			imagepng($dest, $file.'.png');
	}else{
		echo 'Unsupported type: '.$mime[1];
		return false;
	}
	return true;
}
/**
 * @param string $file
 * @param array $info return array for image info
 * @return boolean|resource
 */
function image_create($file,array &$info=null){
	if($info==null)
		$info=getimagesize($file);
	if($info===false)return false;
	$mime=explode('/',$info['mime']);
	$src= false;
	if($mime[1]=='jpeg' || $mime[1]=='jpg'){
		$src = imagecreatefromjpeg($file);
	}elseif($mime[1] == 'gif'){
		$src = imagecreatefromgif($file);
	}elseif($mime[1] == 'png'){
		$src = imagecreatefrompng($file);
	}
	return $src;
}
/**
 * @param string $file
 * @param string $destf
 * @param int $x
 * @param int $y
 * @param int $width
 * @param int $height
 * @param array $info return array for image info
 * @return boolean
 */
function image_crop($file,$destf, $x, $y, $width, $height,array &$info = null){
	$src = image_create($file, $info);
	if(!$src)
		return false;
	$dest = imagecreatetruecolor($width, $height);
	if(imagecopy($dest, $src, 0, 0,$x, $y, $width, $height)){
		imagedestroy($src);
		image_save($dest,$destf,$info);
		imagedestroy($dest);
		return true;
	}else{
		imagedestroy($src);
		imagedestroy($dest);
		return false;
	}
	//bool imagecopyresized ( resource $dst_image , resource $src_image , int $dst_x , int $dst_y , int $src_x , int $src_y , int $dst_w , int $dst_h , int $src_w , int $src_h )
}
/**
 * @param string $file
 * @param string $destf
 * @param int $new_width Pass null to autoscale based on new height
 * @param int $new_height Pass null to autoscale based on new width
 * @param array $info return array for image info
 * @return boolean
 */
function image_resize($file,$destf, $new_width = null, $new_height = null,array &$info = null){
	if($new_width == null && $new_height == null) return false;
	$src = image_create($file, $info);
	if(!$src)
		return false;
	$ratio = $info[0]/$info[1];
	if($new_width != null && $new_height != null){
		$width = $new_width;
		$height = $new_height;
	}else{
		if($new_width != null){
			$width = $new_width;
			$height = $width/$ratio;
		}elseif($new_height!=null){
			$height = $new_height;
			$width = $height*$ratio;
		}else{
			return false;//should never be reached
		}
	}
	$dest = imagecreatetruecolor($width, $height);
	if(imagecopyresampled($dest, $src,0,0,0,0, $width, $height, $info[0], $info[1])){
		imagedestroy($src);
		image_save($dest,$destf,$info);
		imagedestroy($dest);
		return true;
	}else{
		imagedestroy($src);
		imagedestroy($dest);
		return false;
	}
}
/**
 * @param string $file
 * @param string $destf
 * @param int $max_width
 * @param int $max_height
 * @param array $info
 * @return boolean
 */
function image_resize_max($file,$destf, $max_width = null, $max_height = null,array &$info = null){
	if($max_width == null && $max_height == null) return false;
	$src = image_create($file, $info);
	if(!$src)
		return false;
	$ratio=$info[0]/$info[1];
	$width=$info[0];
	$height=$info[1];
	if($max_width!=null&&$width>$max_width){
		$width=$max_width;
		$height=$width/$ratio;
	}
	if($max_height!=null&&$height>$max_height){
		$height=$max_height;
		$width=$height*$ratio;
	}
	$dest = imagecreatetruecolor($width, $height);
	if(imagecopyresampled($dest, $src,0,0,0,0, $width, $height, $info[0], $info[1])){
		imagedestroy($src);
		image_save($dest,$destf,$info);
		imagedestroy($dest);
		return true;
	}else{
		imagedestroy($src);
		imagedestroy($dest);
		return false;
	}
}