<?php
/**
 * @author Kenneth Pierce kcpiercejr@gmail.com
 */

/**
 *
 * returns formatted number of bytes.
 * two parameters: the bytes and the precision (optional).
 * if no precision is set, function will determine clean
 * result automatically.
 * @param int b bytes
 * @param int p precision (0 being Bytes and 8 being YB) Leave blank for auto selection.
 * @return The formatted output
 **/
function formatBytes($b,$p=null){
	static $units = array("B","kB","MB","GB","TB","PB","EB","ZB","YB"),
			$div = array(1,1024,1048576,1073741824,1099511627776,1125899906842624,1152921504606846976,1180591620717411303424,1208925819614629174706176);
	if($p!== null && $p< 9)
		return number_format($b/ $div[$p]) ." ". $units[$p];
	$c=0;
	foreach($units as $k => $u)
		if($b< $div[$k])
			$c= $k- 1;
	return number_format($b/ $div[$c],2) ." ". $units[$c];
}
/**
 * Lists the files in the supplied directory. One file per line.
 * Creates a direct link.
 * @param string $dir Target directory
 * @param string $urlpath url directory to access these files
 * @param boolean $usecounter
 */
function listFiles($dir= '.',$urlpath= '/', $usecounter= false){
	$pdir= opendir($dir);
	$files= array();
	while($file= readdir($pdir)){
		if(!is_dir($dir.$file))
			$files[]= $file;
	}
	sort($files);?>
<table>
	<thead>
	<tr><th>icon</th><th>name</th><th>size</th></tr>
	<tbody><?php
	foreach($files as $file) {?>
			<tr>
				<td><?php
				$pos = strrpos($file, '.');
				if($pos===false)
					echo " ";
				else{
					?><img src="<?php echo URLPATH?>i/ico/file/<?php echo strtolower(substr($file, $pos+1)); ?>.png" alt=""><?php
				}
				?></td>
				<td>
					<a href="<?php echo ($usecounter?URLPATH.'downloadcounter.php?url=':'').$urlpath.$file; ?>"><?php echo $file; ?></a>
				</td>
				<td><?php echo formatBytes(filesize($dir.$file)); ?></td>
		</tr><?php
	}
	?>
	</table>
	<?php
}
function file_extention($file){
	$dot=strrpos($file,'.');
	if(is_bool($dot))
		return null;
	return substr($file,$dot+1);
}
/**
 * Examines the first 256 bytes of a file and attempts to identify it. Returns the common MIME type for the file.
 * Can currently identify
 * <ul>
 * 	<li>3gp	video/3gpp
 * 	<li>mov	video/quicktime
 * 	<li>mpeg	video/mpeg
 * 	<li>mpeg2	video/mpeg
 * 	<li>mp3	audio/mpeg
 * 	<li>mp4	video/mp4
 * 	<li>webm	video/webm
 *	<li>asf container(wmv,wma,asf)	video/x-ms-asf
 *	<li>mkv container	video/x-matroska
 *	<li>flv	video/x-flv
 *	<li>ogg	video/ogg
 *	<li>avi	video/x-msvideo
 *	<li>wav	audio/x-wav
 * </ul>
 * @param string $file The file to identify.
 * @param string $default Value returned if the file could not be identified
 * @return string Returns the common MIME type for the file.
 */
function file_get_type($file,$default='application/octet-stream'){
	$fileh = fopen($file,'rb');
	/*
	3gp		00 00 00 .. 66 74 79 70 33 67 70
	mp4		00 00 00 .. 66 74 79 70 6D 70 34
	mov		00 00 00 .. 66 74 79 70 71 74	//video/quicktime
	mpeg	00 00 01
	mpeg2	00 00 01 BA 44
	mkv		1A 45 DF A3 .. .. .. .. 6D 61 74 72 6F 73 6B 61
	webm 	1A 45 DF A3 ... pos(31) 77 65 62 6D
	asf		30 26 B2 75 8E 66 CF 11 A6 D9 00 AA 00 62 CE 6C	//video/x-ms-wmv
	flv		46 4C 56 01
	ogg		4F 67 67 53
	RIFF container
		avi	52 49 46 46 .. .. .. .. 41 56 49 20 4C 49 53 54	//video/x-msvideo
		wav	52 49 46 46 .. .. .. .. 57 41 56 45//audio/x-wav, audio/wav
	mp3		FF
			FF FB
			FF FB 90
			FF FB 30
	mp3+id3		49 44 33 .. .. abcd0000 zz zz zz zz
		a - ignore
		b - exteded header- check for
		c - ignore
		d - footer
		zz zz zz zz-length of the id3 section
	*/
	$head=fread($fileh,256);
	//check for ID3 headers
	if(ord_eq($head[0],0x49) && ord_eq($head[1],0x44) && ord_eq($head[2],0x33)){
		$size=(ord($head[6])<<21) + (ord($head[7])<<14) + (ord($head[8])<<7) + (ord($head[9]));
		//echo ord($head[6]).'-'.ord($head[7]).'-'.ord($head[8]).'-'.ord($head[9]);
		fseek($fileh,$size+10);
		$head=fread($fileh,256);
		//echo 'ID3 detected. Size:'.$size.' - head:';
		//var_export($head);
	}
	fclose($fileh);
	if(ord_eq($head[0],0xFF)){//mp3
		return 'audio/mpeg';
	}elseif(ord_eq($head[0],0x00)&&ord_eq($head[1],0x00)){//3gpp,mp4,mpg,mov
		if(ord_eq($head[2],0x00)){//3gpp,mp4
			if(ord_eq($head[4],0x66)&&ord_eq($head[5],0x74)&&ord_eq($head[6],0x79)&&ord_eq($head[7],0x70)){//3gpp,mp4,mov
				if(ord_eq($head[8],0x6d)&&ord_eq($head[9],0x70)&&ord_eq($head[10],0x34)){
					return 'video/mp4';
				}elseif(ord_eq($head[8],0x33)&&ord_eq($head[9],0x67)&&ord_eq($head[10],0x70)){
					return 'video/3gpp';
				}elseif(ord_eq($head[4],0x66)&&ord_eq($head[5],0x74)&&ord_eq($head[6],0x79)&&ord_eq($head[7],0x70)&&ord_eq($head[8],0x71)&&ord_eq($head[9],0x74)){//mov
					return 'video/quicktime';
				}
			}
		}elseif(ord_eq($head[2],0x01)){//mpeg
			return 'video/mpeg';
		}
	}elseif(ord_eq($head[0],0x1a)&&ord_eq($head[1],0x45)&&ord_eq($head[2],0xdf)&&ord_eq($head[3],0xa3)){//mkv,webm
		if(ord_eq($head[8],0x6d)&&ord_eq($head[9],0x61)&&ord_eq($head[10],0x74)&&ord_eq($head[11],0x72)&&ord_eq($head[12],0x6f)&&ord_eq($head[13],0x73)&&ord_eq($head[14],0x6b)&&ord_eq($head[15],0x61)){//mkv
			return 'video/x-matroska';
		}elseif(ord_eq($head[31],0x77)&&ord_eq($head[32],0x65)&&ord_eq($head[33],0x62)&&ord_eq($head[34],0x6d)){//webm
			return'video/webm';
		}
	}elseif(ord_eq($head[0],0x30)&&ord_eq($head[1],0x26)&&ord_eq($head[2],0xb2)&&ord_eq($head[3],0x75)&&ord_eq($head[4],0x8e)&&ord_eq($head[5],0x66)&&ord_eq($head[6],0xcf)
	  &&ord_eq($head[7],0x11)&&ord_eq($head[8],0xa6)&&ord_eq($head[9],0xd9)&&ord_eq($head[10],0x00)&&ord_eq($head[11],0xaa)&&ord_eq($head[12],0x00)&&ord_eq($head[13],0x62)&&ord_eq($head[14],0xce)&&ord_eq($head[15],0x6c)){
		//asf container
		return 'video/x-ms-asf';
	}elseif(ord_eq($head[0],0x46)&&ord_eq($head[1],0x4c)&&ord_eq($head[2],0x56)&&ord_eq($head[3],0x01)){//flv
		return 'video/x-flv';
	}elseif(ord_eq($head[0],0x4f)&&ord_eq($head[1],0x67)&&ord_eq($head[2],0x67)&&ord_eq($head[3],0x53)){//ogg
		return 'video/ogg';
	}elseif(ord_eq($head[0],0x52)&&ord_eq($head[1],0x49)&&ord_eq($head[2],0x46)&&ord_eq($head[3],0x46)){//avi,wav
		if(ord_eq($head[8],0x57)&&ord_eq($head[9],0x41)&&ord_eq($head[10],0x56)&&ord_eq($head[11],0x45)){//wav
			return 'audio/x-wav';
		}
		if(ord_eq($head[8],0x41)&&ord_eq($head[9],0x56)&&ord_eq($head[10],0x49)&&ord_eq($head[11],0x20)&&ord_eq($head[12],0x4c)&&ord_eq($head[13],0x49)&&ord_eq($head[14],0x53)&&ord_eq($head[15],0x54)){//avi
			return 'video/x-msvideo';
		}
	}
	return $default;
}
function ord_eq($char,$ord){return ord($char)==$ord;}
function file_normalize_path($path){
	switch(DIRECTORY_SEPARATOR){
		case '/':
			return str_replace(array('\\','//'), '/', $path);
		case '\\':
			return str_replace(array('\\\\','//'), '\\', $path);
	}
}