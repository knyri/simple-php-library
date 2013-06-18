<?php
/**
 * @author Kenneth Pierce kcpiercejr@gmail.com
 */
//general
define('REG_STATE'			,'/^A[LKZR]|C[AOT]|D[EC]|FL|GA|HI|I[DLNA]|K[SY]|LA|M[EDAINSOT]|N[EVHJMYCD]|O[HKR]|PA|RI|S[CD]|T[NX]|UT|V[TA]|W[AVIY]$/');

define('E_FORM_EMPTY',1);
define('E_FORM_LIMITEXCEEDED',2);
define('E_FORM_MINNOTMET',3);
//date
define('E_FORM_DATEFORMAT',3);
define('E_FORM_DATEDAY',4);
define('E_FORM_DATEMONTH',5);
define('E_FORM_DATEYEAR',6);
//phone
define('E_FORM_PHONEAREA',7);
define('E_FORM_PHONEPREFIX',8);
define('E_FORM_PHONELINE',9);
define('E_FORM_PHONEFORMAT',10);
//define('E_FORM_',1);
require_once 'rfc822.php';
require_once 'html_helper.php';
PackageManager::requireFunctionOnce('lang.basic');
/**
 * Gets the value of at $index in $_GET or $_POST.
 * <em>Checks $_POST first.</em>
 * If neither have the value then $default is returned.
 * @param mixed $index
 * @param mixed $default
 * @return mixed The value found in $_GET or $_POST or $default if neither had the index defined.
 */
function form_get($index,$default=null){
	if(isset($_POST[$index])){
		if($_POST[$index]==='')return $default;
		return $_POST[$index];
	}
	if(isset($_GET[$index])){
		if($_GET[$index]==='')return $default;
		return $_GET[$index];
	}
	return $default;
}
/**
 * Creates a select element from a mysql result set where the first field is the value and the
 * second field is the display.
 * @param resource $result The first field is the value,the second field is the display.
 * @param array|string $attrib A string containing the name or an array of 'key'=>'value' mappings that will be added to the attribute list.
 * @param string|int $selected Defaults to null. If left blank it will be set to the value found in $_GET or $_POST.
 */
function form_select_mysql($result,$attrib,$selected=null){
	if(!is_resource($result))throw new IllegalArgumentException('Supplied result is not a valid mysql result.');
	if(is_array($attrib)){
		if(!isset($attrib['name'])){throw new IllegalArgumentException('name must be set in the attribute list');}
	}else{
		$attrib=array('name'=>$attrib);
	}

	if($selected===null)
		$selected=form_get($attrib['name']);

	echo '<select '.combine_attrib($attrib).'>';
	if($selected===null){
		$row=mysql_fetch_array($result,MYSQL_NUM);
		?><option value="<?php echo $row[0]?>" selected><?php echo $row[1]?></option><?php
		while($row=mysql_fetch_array($result,MYSQL_NUM)){
			?><option value="<?php echo $row[0]?>"><?php echo $row[1]?></option><?php
		}
	}else{
		while($row=mysql_fetch_array($result,MYSQL_NUM)){
			if($row[0]==$selected){
				?><option value="<?php echo $row[0]?>" selected><?php echo $row[1]?></option><?php
				break;
			}else{
				?><option value="<?php echo $row[0]?>"><?php echo $row[1]?></option><?php
			}
		}
		while($row=mysql_fetch_array($result,MYSQL_NUM)){?><option value="<?php echo $row[0]?>"><?php echo $row[1]?></option><?php }
	}
	echo '</select>'.EOL;
}
/**
 * Creates a select element from a mysql result set where the first field is the value and the
 * second field is the display.
 * @param resource $result The first field is the value,the second field is the display.
 * @param array|string $attrib A string containing the name or an array of 'key'=>'value' mappings that will be added to the attribute list.
 * @param string|int $selected Defaults to null. If left blank it will be set to the value found in $_GET or $_POST.
 */
function form_select_PDO($result,$attrib,$selected=null){
	//if(!is_resource($result))throw new IllegalArgumentException('Supplied result is not a valid mysql result.');
	if(is_array($attrib)){
		if(!isset($attrib['name'])){throw new IllegalArgumentException('name must be set in the attribute list');}
	}else{
		$attrib=array('name'=>$attrib);
	}

	if($selected===null)
		$selected=form_get($attrib['name']);

	echo '<select '.combine_attrib($attrib).'>';
	if($selected===null){
		$row=$result->fetch(PDO::FETCH_NUM);
		?><option value="<?php echo $row[0]?>" selected><?php echo $row[1]?></option><?php
		while($row=$result->fetch(PDO::FETCH_NUM)){
			?><option value="<?php echo $row[0]?>"><?php echo $row[1]?></option><?php
		}
	}else{
		while($row=$result->fetch(PDO::FETCH_NUM)){
			if($row[0]==$selected){
				?><option value="<?php echo $row[0]?>" selected><?php echo $row[1]?></option><?php
				break;
			}else{
				?><option value="<?php echo $row[0]?>"><?php echo $row[1]?></option><?php
			}
		}
		while($row=$result->fetch(PDO::FETCH_NUM)){?><option value="<?php echo $row[0]?>"><?php echo $row[1]?></option><?php }
	}
	echo '</select>'.EOL;
}
/**
 * Echoes a series of checkboxes.
 * @param string $name Name of the group
 * @param string $id Id of the group(used to associate labels with the checkbox)
 * @param resource $values a 2 column Mysql resource object.
 * @param array $attrib Optional. Array of attribute=>value mappings.
 * @param string $before Optional. Prepends this to each iteration.
 * @param string $after Optional. Appends this to each iteration.
 */
function form_checkbox_mysql($name,$id,$values,array $checked=null,array $attrib=null,$before=null,$after=null){
	$attrib=combine_attrib($attrib).' ';
	if($checked==null)
		$checked=form_get($name,array());
	while($row=mysql_fetch_array($values,MYSQL_NUM)){
		echo $before.'<label for="'.$id.$row[0].'"><input type="checkbox" name="'.$name.'[]" value="'.$row[0].'" id="'.$id.$row[0].'"'.(in_array($row[0],$checked)?' checked="checked"':'').$attrib.'/>&nbsp;'.$row[1].'</label>'.$after;
	}
}
/**
 * Takes the array and outputs a hidden form element for each
 * array element. The array must be 'name'=>'value' format.
 * @param array $list
 */
function form_hidden_from_array(array $list){
	foreach($list as $name=>$value)echo '<input type="hidden" name="'.$name.'" value="'.$value.'" />'."\n";
}
/**
 * Outputs a date element.
 * Element names are name[month],name[day],name[year]
 * @param string $name suffix for the elements.
 * @param string|array $date [optional]A unix datestamp(year-month-day) or an array('day'=>day,'month'=>month,'year'=>year).
 */
function form_date($name,$date=null){
	$year=0;$month=1;$day=1;
	if($date===null)
		$date=form_get($name);

	if(is_array($date)){
		$date=array_map('intval',$date);
		$year=	$date['year'];
		$month=	$date['month'];
		$day=	$date['day'];
	} elseif(!empty($date)){
		list($year,$month,$day)=array_map('intval',explode('-',$date));
	}else{
		list($year,$month,$day)=array_map('intval',explode('-',date('Y-m-d')));
	}
?><select name="<?php echo $name;?>[month]"><option value="01"<?php echo $month==1?' selected':'';?>>Jan</option><option value="02"<?php echo $month==2?' selected':'';?>>Feb</option><option value="03"<?php echo $month==3?' selected':'';?>>Mar</option><option value="04"<?php echo $month==4?' selected':'';?>>Apr</option><option value="05"<?php echo $month==5?' selected':'';?>>May</option><option value="06"<?php echo $month==6?' selected':'';?>>Jun</option><option value="07"<?php echo $month==7?' selected':'';?>>Jul</option><option value="08"<?php echo $month==8?' selected':'';?>>Aug</option><option value="09"<?php echo $month==9?' selected':'';?>>Sep</option><option value="10"<?php echo $month==10?' selected':'';?>>Oct</option><option value="11"<?php echo $month==11?' selected':'';?>>Nov</option><option value="12"<?php echo $month==12?' selected':'';?>>Dec</option></select>
<select name="<?php echo $name;?>[day]"><option value="01"<?php echo $day==1?' selected':'';?>>1</option><option value="02"<?php echo $day==2?' selected':'';?>>2</option><option value="03"<?php echo $day==3?' selected':'';?>>3</option><option value="04"<?php echo $day==4?' selected':'';?>>4</option><option value="05"<?php echo $day==5?' selected':'';?>>5</option><option value="06"<?php echo $day==6?' selected':'';?>>6</option><option value="07"<?php echo $day==7?' selected':'';?>>7</option><option value="08"<?php echo $day==8?' selected':'';?>>8</option><option value="09"<?php echo $day==9?' selected':'';?>>9</option><option value="10"<?php echo $day==10?' selected':'';?>>10</option><option value="11"<?php echo $day==11?' selected':'';?>>11</option><option value="12"<?php echo $day==12?' selected':'';?>>12</option><option value="13"<?php echo $day==13?' selected':'';?>>13</option><option value="14"<?php echo $day==14?' selected':'';?>>14</option><option value="15"<?php echo $day==15?' selected':'';?>>15</option><option value="16"<?php echo $day==16?' selected':'';?>>16</option><option value="17"<?php echo $day==17?' selected':'';?>>17</option><option value="18"<?php echo $day==18?' selected':'';?>>18</option><option value="19"<?php echo $day==19?' selected':'';?>>19</option><option value="20"<?php echo $day==20?' selected':'';?>>20</option><option value="21"<?php echo $day==21?' selected':'';?>>21</option><option value="22"<?php echo $day==22?' selected':'';?>>22</option><option value="23"<?php echo $day==23?' selected':'';?>>23</option><option value="24"<?php echo $day==24?' selected':'';?>>24</option><option value="25"<?php echo $day==25?' selected':'';?>>25</option><option value="26"<?php echo $day==26?' selected':'';?>>26</option><option value="27"<?php echo $day==27?' selected':'';?>>27</option><option value="28"<?php echo $day==28?' selected':'';?>>28</option><option value="29"<?php echo $day==29?' selected':'';?>>29</option><option value="30"<?php echo $day==30?' selected':'';?>>30</option><option value="31"<?php echo $day==31?' selected':'';?>>31</option></select>
<select name="<?php echo $name;?>[year]"><?php
	$date=getdate();
	for($i=0;$i<80;$i++)
		echo '<option value="'.($date['year']-$i).'"'.($year==($date['year']-$i)?' selected':'').'>'.($date['year']-$i).'</option>';
?></select><?php
}

/**
 * Outputs a date element.
 * Element names are name[month],name[day],name[year]
 * @param string $name suffix for the elements.
 * @param string $date [optional]A unix datestamp(year-month-day) or an array('day'=>day,'month'=>month,'year'=>year).
 */
function form_time($name,$time=null){
	$second=0;$hour=1;$minute=1;$h=0;
	if($time===null){
		$time=form_get($name);
	}
	if(is_array($time)){
		$time=array_map('intval',$time);
		$hour=$time['hour'];
		$minute=$time['minute'];
		$second=$time['second'];
	} elseif(!empty($time)){
		list($hour,$minute,$second)=array_map('intval',explode(':',$time));
	}else{
		list($hour,$minute,$second)=array_map('intval',explode(':',date('H:i:s')));
	}
?><select name="<?php echo $name;?>[hour]"><?php
	for($h=0;$h<$hour;$h++){?>
		<option value="<?php echo $h ?>"><?php echo $h ?></option><?php
	}
	?><option value="<?php echo $h ?>" selected><?php echo $h ?></option><?php
	for(;$h<24;$h++){?>
		<option value="<?php echo $h ?>"><?php echo $h ?></option><?php
	}
?></select>
<select name="<?php echo $name;?>[minute]"><?php
for($h=0;$h<$minute;$h++){?>
		<option value="<?php echo $h ?>"><?php echo $h ?></option><?php
	}
	?><option value="<?php echo $h ?>" selected><?php echo $h ?></option><?php
	for(;$h<60;$h++){?>
		<option value="<?php echo $h ?>"><?php echo $h ?></option><?php
	}
?></select>
<select name="<?php echo $name;?>[second]"><?php
for($h=0;$h<$second;$h++){?>
		<option value="<?php echo $h ?>"><?php echo $h ?></option><?php
	}
	?><option value="<?php echo $h ?>" selected><?php echo $h ?></option><?php
	for(;$h<60;$h++){?>
		<option value="<?php echo $h ?>"><?php echo $h ?></option><?php
	}
?></select>
<?php
}
/**
 * Creates a select HTML element.
 * @param string $name The name of the element
 * @param array $data Array of values and their displays. array('value'=>'display'[,'value'=>'display'])
 * @param mixed $default [optional]The default value.
 * @param array $extra [optional]Array of attribute values. array('tag'=>'value'[,'tag'=>'value'])
 */
function form_select($name,array $data,$default=null,array $extra=null){
	if($default==null)$default=form_get($name);
	echo "<select name=\"$name\" ";
	echo combine_attrib($extra);
	echo '>'.EOL;
	if($default!=null){
		foreach($data as $key=> $value){
			echo "<option value=\"$key\"";
			if($key==$default)echo ' selected';
			echo ">$value</option>";
		}
	} else{
		foreach($data as $key=> $value){
			echo "<option value=\"$key\">$value</option>";
		}
	}
	echo '</select>';
}
/**
 * Creates an input field of the specified type with the specified value.
 * Type defaults to 'text'. If value is empty then it will try to find the value from the $_REQUEST super global.
 * @param string $name Name of the input element.
 * @param string $type  [optional]Type of the element. Defaults to text.
 * @param string $value [optional]Value of the element. Defaults to value returned by form_get()
 * @param array $attrib [optional]Array of key=>value mappings. These are appended to the tag's attributes.
 */
function form_input($name,$type='text',$value='',array $attrib=array()){
	if(blank($value)){$value=form_get($name);}
?><input type="<?php echo $type?>" name="<?php echo $name?>" value="<?php echo $value?>" <?php echo combine_attrib($attrib) ?>/><?php
}
function form_phone($name,$area='',$prefix='',$line=''){
	$phone=form_get($name);
	if(empty($area)&& isset($phone['area']))	$area=	$phone['area'];
	if(empty($prefix)&&isset($phone['prefix']))	$prefix=$phone['prefix'];
	if(empty($line)&&isset($phone['line']))		$line=	$phone['line'];
?>(<input type=text name="<?php echo $name;?>[area]" value="<?php echo $area;?>" maxlength="3">)<input type=text name="<?php echo $name;?>[prefix]" value="<?php echo $prefix;?>" maxlength="3">-<input type=text name="<?php echo $name;?>[line]" value="<?php echo $line;?>" maxlength="4"><?php
}
function form_phone_tostr(array $phone_ary){
	return $phone_ary['area'].'-'.$phone_ary['prefix'].'-'.$phone_ary['line'];
}
function form_date_tostr(array $date_ary){
	return $date_ary['year'].'-'.$date_ary['month'].'-'.$date_ary['day'];
}
function form_time_tostr(array $time_ary){
	return $time_ary['hour'].':'.$time_ary['minute'].':'.$time_ary['second'];
}
function form_text($name,$value=null,array $extra=null){
	if($value==null)$value=form_get($name);
	echo '<input type=text name="'.$name.'" value="'.$value.'" ';
	echo combine_attrib($extra);
	echo '>'.EOL;
}
function form_text_labeled($name,$id,$label,$value=null,array $attrib=null){
	if($value==null)$value=form_get($name);
	echo '<label for="'.$id.'">'.$label.'</label>';
	echo '<input type=text name="'.$name.'" value="'.$value.'" ';
	echo combine_attrib($attrib);
	echo '>'.EOL;
}
/**
 * Echoes a series of radio buttons.
 * @param string $name Name of the group
 * @param string $id Id of the group(used to associate labels with the radio button)
 * @param array $values Array of value=>display mappings.
 * @param array $extra Optional. Array of attribute=>value mappings.
 * @param string $before Optional. Prepends this to each iteration.
 * @param string $after Optional. Appends this to each iteration.
 */
function form_radio($name,$id,array $values,$default,array $attrib=null,$before=null,$after=null){
	$attrib=combine_attrib($attrib).' ';
	$val=form_get($name,$default);
	foreach($values as $value=>$disp){
		echo $before.'<label for="'.$id.$value.'"><input type=radio name="'.$name.'" value="'.$value.'" id="'.$id.$value.'"'.(($value==$val)?' checked ':' ').$attrib.'>&nbsp;'.$disp.'</label>'.$after;
	}
}
function form_radioYN($name,$id,$default,array $attrib=null,$before=null,$after=null){
	form_radio($name,$id,array('y'=>'Yes','n'=>'No'),$default,$attrib,$before,$after);
}
/**
 * Echoes a series of checkboxes.
 * @param string $name Name of the group
 * @param string $id Id of the group(used to associate labels with the checkbox)
 * @param array $values Array of value=>display mappings.
 * @param array $attrib Optional. Array of attribute=>value mappings.
 * @param string $before Optional. Prepends this to each iteration.
 * @param string $after Optional. Appends this to each iteration.
 */
function form_checkbox($name,$id,array $values,array $attrib=null,$before=null,$after=null){
	$attrib=combine_attrib($attrib).' ';
	$checked=form_get($name,array());
	foreach($values as $value=>$disp){
		echo $before.'<label for="'.$id.$value.'"><input type=checkbox name="'.$name.'[]" value="'.$value.'" id="'.$id.$value.'"'.(in_array($value,$checked)?' checked':'').$attrib.'>&nbsp;'.$disp.'</label>'.$after;
	}
}
function form_textarea($sName,$iCols,$iRows,array $attrib=array()){
	$attrib=combine_attrib($attrib);
	$val=form_get($sName,'');
	echo '<textarea name="'.$sName.'" cols="'.$iCols.'" rows="'.$iRows.'" '.$attrib.'>'.$val.'</textarea>';
}
/******************************************************************************************************
 * ***************************************/
/**
 * Enter description here ...
 * @param string $name name of the element
 * @return boolean|integer false if valid, error constant if error
 */
function is_date($name){
	$date=form_get($name,null);
	if($date==null)return E_FORM_EMPTY;
	if(!is_array($date)){
		$date=date_parse($date);
		if($date==false)					return E_FORM_DATEFORMAT;
	}
	if(!between($date['year'],0,10000))		return E_FORM_DATEYEAR;
	if(!between($date['month'],0,13))		return E_FORM_DATEMONTH;
	if($date['month']==2){
		if(0==$year%4&&0!=$year%100||0==$year%400){
			if(!between($date['day'],0,30))	return E_FORM_DATEDAY;
		}else{
			if(!between($date['day'],0,28))	return E_FORM_DATEDAY;
		}
	}elseif($date['month']<8){
		if($month%2==1){//31 days
			if(!between($date['day'],0,32))	return E_FORM_DATEDAY;
		}else{//30 days
			if(!between($date['day'],0,31))	return E_FORM_DATEDAY;
		}
	}elseif($month%2==0){//31 days
		if(!between($date['day'],0,32))		return E_FORM_DATEDAY;
	}else{//30 days
		if(!between($date['day'],0,31))		return E_FORM_DATEDAY;
	}
	return true;
}
/**
 * Enter description here ...
 * @param string $value
 * @return boolean|integer
 */
function is_phone($value){
	if(empty($value))
		return E_FORM_EMPTY;
	if(!is_array($value)){
		if(!preg_match('/^(\+?\d)?(\(?\d{3}[\)\-\.]?)?\d{3}[\.\-]?\d{4}$/',$value))
			return E_FORM_PHONEFORMAT;
	}else{
		if(!(isset($value['area'])&&	is_numeric($value['area'])&&	between($value['area'],99,1000)))	return E_FORM_PHONEAREA;
		if(!(isset($value['prefix'])&&	is_numeric($value['prefix'])&&	between($value['prefix'],99,1000)))	return E_FORM_PHONEPREFIX;
		if(!(isset($value['line'])&&	is_numeric($value['line'])&&	between($value['line'],999,10000)))	return E_FORM_PHONELINE;
	}
	return true;
}
/**
 * @param string $test Just the allowed characters. Not the full regex. Do not include space.
 * @param string $separator Allowed word separator characters
 * @param string $value
 * @param int $min
 * @param int $max
 * @return Ambigous <boolean, string>|boolean|Ambigous <string, boolean>|string
 */
function form_regex_test($test,$separator,$value,$min=1,$max=1){
	if(blank($value))
		return ($min==0)?true:E_FORM_EMPTY;
	if($max==1)
		return preg_match('/^['.$test.']+$/',$value)===1;
	if($max<1){
		if($min>0){
			if(preg_match('/^(['.$test.']+['.$separator.']+){'.($min-1).',}['.$test.']+$/',$value)===1)
				return true;
			else
				return (preg_match('/^(['.$test.']+['.$separator.']+)/',$value)===1)?E_FORM_MINNOTMET:false;
		}else{//empty would be caught prior so + is used instead of *
			return preg_match('/^['.$test.$separator.']+$/',$value)===1;
		}
	}else{
		if($min>0){
			if(preg_match('/^(['.$test.']+['.$separator.']+){'.($min-1).','.($max-1).'}['.$test.']+$/',$value)===1)return true;
			if(preg_match('/^(['.$test.']+['.$separator.']+){'.$min.',}$/',$value)===1)return E_FORM_LIMITEXCEEDED;
			if(preg_match('/^(['.$test.']+['.$separator.']+){0,'.($max-1).'}['.$test.']+$/',$value)===1)return E_FORM_MINNOTMET;
			return false;
		}else{
			if(preg_match('/^(['.$test.']+['.$separator.']+){0,'.($max-1).'}['.$test.']+$/',$value)===1)return true;
			if(preg_match('/^['.$test.$separator.']+$/',$value)===1)return E_FORM_LIMITEXCEEDED;
			return false;
		}
	}
}
/**
 * Tests to see if the field only contains A-Z and a-z.
 * @param string $value
 * @param number $min Defaults to 1. Minimum number of space delimited words allowed. 0 returns true instead of E_FORM_EMPTY.
 * @param number $max Defaults to 1. Maximum number of space delimited words allowed. 0 means unlimited.
 * @return boolean|integer true if valid, false if not, or an error constant
 */
function is_alphastr($value,$min=1,$max=1){
	return form_regex_test('A-Za-z',' ',$value,$min,$max);
}
/**
 * Tests to see if the field only contains A-Z, a-z, and 0-9.
 * @param string $value
 * @param number $min Defaults to 1. Minimum number of space delimited words allowed. 0 returns true instead of E_FORM_EMPTY.
 * @param number $max Defaults to 1. Maximum number of space delimited words allowed. 0 means unlimited.
 * @return boolean|integer true if valid, false if not, or an error constant
 */
function is_alphanumstr($value,$min=1,$max=1){
	return form_regex_test('A-Za-z0-9',' ',$value,$min,$max);
}
/**
 * Tests to see if the field only contains A-Z, a-z, apostrophe, hyphen, comma and period.
 * @param string $value
 * @param number $min Defaults to 1. Minimum number of space delimited words allowed. 0 returns true instead of E_FORM_EMPTY.
 * @param number $max Defaults to 1. Maximum number of space delimited words allowed. 0 means unlimited.
 * @return boolean|integer true if valid, false if not, or an error constant
 */
function is_namestr($value,$min=1,$max=1){
	return form_regex_test('A-Za-z\'\.\-,',' ',$value,$min,$max);
}
/**
 * Tests to see if the field only contains starts with a number or PO followed by a number and one or more alpha strings maybe ending with a period.
 * @param string $value
  * @return boolean|integer true if valid, false if not, or E_FORM_EMPTY
 */
function is_address($value){
	if(empty($value))
		return E_FORM_EMPTY;
	return
	(preg_match('/^[0-9]+ [0-9A-Za-z\'\.\- ]+$/',$value)===1)||
	(preg_match('/^[Pp]\.?[Oo]\.?( +[Bb][Oo][Xx])? +[0-9]+( +[0-9A-Za-z\'\.\- ]+)?$/',$value)===1);
}
/**
 *
 * @param string $value
 * @return boolean|integer true if valid, false if not, or E_FORM_EMPTY
 */
function is_zip($value){
	if(empty($value))
		return E_FORM_EMPTY;
	return preg_match('/^[1-9][0-9]{4}(-?[0-9]{4})?$/',$value)===1;
}
/**
 * Tests to see if the field only contains A-Z, a-z, 0-9 and punctuation marks.
 * @param string $value Value to be tested
 * @param number $min Defaults to 1. Minimum number of space delimited words allowed. 0 returns true if blank
 * @param number $max Defaults to 1. Maximum number of space delimited words allowed. 0 means unlimited.
 * @return boolean|integer true if valid, false if not, or an error constant
 */
function is_wordstr($value,$min=1,$max=1){
	return form_regex_test('A-Za-z0-9\-\/\+=~`\.,:;<>\{\}\[\]\(\)\|!@\$%\^&\*\'"_\?\\\#',' \r\n\t',$value,$min,$max);
}
/**
 * Checks to see if the email address conforms to RFC 822.
 * @param string $value
 * @return boolean|number true,false,or E_FORM_EMPTY
 */
function is_email($value){
	if($value==null||$value=='')
		return E_FORM_EMPTY;
	return is_valid_email_address($value)==true;
}
/**
 * Tests to see if the URL is in a valid form. Does not check for validity of the path(would be quite complicated)
 * @param string $url
 * @return number|boolean true,false or E_FORM_EMPTY
 */
function is_url($url){
	if(empty($url))
		return E_FORM_EMPTY;
	return preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $url)===1;
}
/**
 * NOT A REPLACEMENT FOR in_array(). This checks to see if the selected value is a defined key in $valid. Will work for
 * multiple selection fields assuming the name of the field in the HTML is in the form of name[].
 * @param mixed $key The value from the form
 * @param array $valid
 * @return boolean|number true,false or E_FORM_EMPTY
 */
function is_validselection($key,array $valid){
	if($key===null)
		return E_FORM_EMPTY;
	if(is_array($key)){
		if(count($key)==0)
			return E_FORM_EMPTY;
		foreach($key as $k)
			if(!isset($valid[$k]))
				return false;
		return true;
	}
	return isset($valid[$key]);
}
/**
 * Checks to see if the selected amount is equal to or greater then $iMin.
 * @param string $name
 * @param number $iMin
 * @return number|boolean true,false or E_FORM_EMPTY
 */
function is_minselected($name,$iMin){
	$values=form_get($name,null);
	if($values==null)
		return E_FORM_EMPTY;
	return count($values)>=$iMin;
}
/**
 * Checks to see if th value is selected.
 * @param string $name Name of the field
 * @param string|number $value
 * @return number|boolean true,false or E_FORM_EMPTY
 */
function is_selected($name,$value){
	$fValues=form_get($name,null);
	if($fValues==null)return E_FORM_EMPTY;
	if(is_array($fValues)){
		if(count($fValues)==0)
			return E_FORM_EMPTY;
		foreach($fValues as $fValue)
			if($value==$fValue)
				return true;
		return false;
	}
	return $fValues==$value;
}
/**
 * Checks to see if $value is not checked. Will return true instead of E_FORM_EMPTY if the field is empty.
 * @param string $name name of the field
 * @param string|number $value
 * @return boolean True if the value is not checked.
 */
function is_notselected($name,$value){
	$fValues=form_get($name,null);
	if($fValues==null)return true;
	if(is_array($fValues)){
		if(count($fValues)==0)
			return true;
		foreach($fValues as $fValue)
			if($value==$fValue)
				return false;
		return true;
	}
	return $fValues!=$value;
}


function form_create_token($salt='saltymoonsailor'){
	$_SESSION['simple']['lib']['form']['token']=md5(time()+$salt);
	return $_SESSION['simple']['lib']['form']['token'];
}
function form_token_valid($token_name='token'){
	return form_get($token_name)==$_SESSION['simple']['lib']['form']['token'];
}
function form_token($token_name='token'){
	return form_input($token_name,'hidden',$_SESSION['simple']['lib']['form']['token']);
}
function form_error(array $errors){
	if(!$errors)return false;
	if(count($errors)==0)return false;
	echo '<div class="alert">';
	foreach($errors as $error){
		echo "<p>$error</p>";
	}
	echo '</div>';
	return true;
}