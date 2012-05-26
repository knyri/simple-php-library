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
		?><option value="<?php echo $row[0]?>" selected="selected"><?php echo $row[1]?></option><?php
		while($row=mysql_fetch_array($result,MYSQL_NUM)){
			?><option value="<?php echo $row[0]?>"><?php echo $row[1]?></option><?php
		}
	}else{
		while($row=mysql_fetch_array($result,MYSQL_NUM)){
			if($row[0]==$selected){
				?><option value="<?php echo $row[0]?>" selected="selected"><?php echo $row[1]?></option><?php
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
?><select name="<?php echo $name;?>[month]"><option value="01"<?php echo $month==1?' selected="selected"':'';?>>Jan</option><option value="02"<?php echo $month==2?' selected="selected"':'';?>>Feb</option><option value="03"<?php echo $month==3?' selected="selected"':'';?>>Mar</option><option value="04"<?php echo $month==4?' selected="selected"':'';?>>Apr</option><option value="05"<?php echo $month==5?' selected="selected"':'';?>>May</option><option value="06"<?php echo $month==6?' selected="selected"':'';?>>Jun</option><option value="07"<?php echo $month==7?' selected="selected"':'';?>>Jul</option><option value="08"<?php echo $month==8?' selected="selected"':'';?>>Aug</option><option value="09"<?php echo $month==9?' selected="selected"':'';?>>Sep</option><option value="10"<?php echo $month==10?' selected="selected"':'';?>>Oct</option><option value="11"<?php echo $month==11?' selected="selected"':'';?>>Nov</option><option value="12"<?php echo $month==12?' selected="selected"':'';?>>Dec</option></select>
<select name="<?php echo $name;?>[day]"><option value="01"<?php echo $day==1?' selected="selected"':'';?>>1</option><option value="02"<?php echo $day==2?' selected="selected"':'';?>>2</option><option value="03"<?php echo $day==3?' selected="selected"':'';?>>3</option><option value="04"<?php echo $day==4?' selected="selected"':'';?>>4</option><option value="05"<?php echo $day==5?' selected="selected"':'';?>>5</option><option value="06"<?php echo $day==6?' selected="selected"':'';?>>6</option><option value="07"<?php echo $day==7?' selected="selected"':'';?>>7</option><option value="08"<?php echo $day==8?' selected="selected"':'';?>>8</option><option value="09"<?php echo $day==9?' selected="selected"':'';?>>9</option><option value="10"<?php echo $day==10?' selected="selected"':'';?>>10</option><option value="11"<?php echo $day==11?' selected="selected"':'';?>>11</option><option value="12"<?php echo $day==12?' selected="selected"':'';?>>12</option><option value="13"<?php echo $day==13?' selected="selected"':'';?>>13</option><option value="14"<?php echo $day==14?' selected="selected"':'';?>>14</option><option value="15"<?php echo $day==15?' selected="selected"':'';?>>15</option><option value="16"<?php echo $day==16?' selected="selected"':'';?>>16</option><option value="17"<?php echo $day==17?' selected="selected"':'';?>>17</option><option value="18"<?php echo $day==18?' selected="selected"':'';?>>18</option><option value="19"<?php echo $day==19?' selected="selected"':'';?>>19</option><option value="20"<?php echo $day==20?' selected="selected"':'';?>>20</option><option value="21"<?php echo $day==21?' selected="selected"':'';?>>21</option><option value="22"<?php echo $day==22?' selected="selected"':'';?>>22</option><option value="23"<?php echo $day==23?' selected="selected"':'';?>>23</option><option value="24"<?php echo $day==24?' selected="selected"':'';?>>24</option><option value="25"<?php echo $day==25?' selected="selected"':'';?>>25</option><option value="26"<?php echo $day==26?' selected="selected"':'';?>>26</option><option value="27"<?php echo $day==27?' selected="selected"':'';?>>27</option><option value="28"<?php echo $day==28?' selected="selected"':'';?>>28</option><option value="29"<?php echo $day==29?' selected="selected"':'';?>>29</option><option value="30"<?php echo $day==30?' selected="selected"':'';?>>30</option><option value="31"<?php echo $day==31?' selected="selected"':'';?>>31</option></select>
<select name="<?php echo $name;?>[year]"><?php
	$date=getdate();
	for($i=0;$i<80;$i++)
		echo '<option value="'.($date['year']-$i).'"'.($year==($date['year']-$i)?' selected="selected"':'').'>'.($date['year']-$i).'</option>';
?></select><?php
}

/**
 * Outputs a date element.
 * Element names are name[month],name[day],name[year]
 * @param string $name suffix for the elements.
 * @param string $date [optional]A unix datestamp(year-month-day) or an array('day'=>day,'month'=>month,'year'=>year).
 */
function form_time($name,$time=null){
	$second=0;$hour=1;$minute=1;
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
?><select name="<?php echo $name;?>[hour]"><option value="00"<?php echo $hour==0?' selected="selected"':'';?>>00</option><option value="01"<?php echo $hour==1?' selected="selected"':'';?>>01</option><option value="02"<?php echo $hour==2?' selected="selected"':'';?>>02</option><option value="03"<?php echo $hour==3?' selected="selected"':'';?>>03</option><option value="04"<?php echo $hour==4?' selected="selected"':'';?>>04</option><option value="05"<?php echo $hour==5?' selected="selected"':'';?>>05</option><option value="06"<?php echo $hour==6?' selected="selected"':'';?>>06</option><option value="07"<?php echo $hour==7?' selected="selected"':'';?>>07</option><option value="08"<?php echo $hour==8?' selected="selected"':'';?>>08</option><option value="09"<?php echo $hour==9?' selected="selected"':'';?>>09</option><option value="10"<?php echo $hour==10?' selected="selected"':'';?>>10</option><option value="11"<?php echo $hour==11?' selected="selected"':'';?>>11</option><option value="12"<?php echo $hour==12?' selected="selected"':'';?>>12</option><option value="13"<?php echo $hour==13?' selected="selected"':'';?>>13</option><option value="14"<?php echo $hour==14?' selected="selected"':'';?>>14</option><option value="15"<?php echo $hour==15?' selected="selected"':'';?>>15</option><option value="16"<?php echo $hour==16?' selected="selected"':'';?>>16</option><option value="17"<?php echo $hour==17?' selected="selected"':'';?>>17</option><option value="18"<?php echo $hour==18?' selected="selected"':'';?>>18</option><option value="19"<?php echo $hour==19?' selected="selected"':'';?>>19</option><option value="20"<?php echo $hour==20?' selected="selected"':'';?>>20</option><option value="21"<?php echo $hour==21?' selected="selected"':'';?>>21</option><option value="22"<?php echo $hour==22?' selected="selected"':'';?>>22</option><option value="23"<?php echo $hour==23?' selected="selected"':'';?>>23</option></select>
<select name="<?php echo $name;?>[minute]"><option value="00"<?php echo $minute==0?' selected="selected"':'';?>>00</option><option value="01"<?php echo $minute==1?' selected="selected"':'';?>>01</option><option value="02"<?php echo $minute==2?' selected="selected"':'';?>>02</option><option value="03"<?php echo $minute==3?' selected="selected"':'';?>>03</option><option value="04"<?php echo $minute==4?' selected="selected"':'';?>>04</option><option value="05"<?php echo $minute==5?' selected="selected"':'';?>>05</option><option value="06"<?php echo $minute==6?' selected="selected"':'';?>>06</option><option value="07"<?php echo $minute==7?' selected="selected"':'';?>>07</option><option value="08"<?php echo $minute==8?' selected="selected"':'';?>>08</option><option value="09"<?php echo $minute==9?' selected="selected"':'';?>>09</option><option value="10"<?php echo $minute==10?' selected="selected"':'';?>>10</option><option value="11"<?php echo $minute==11?' selected="selected"':'';?>>11</option><option value="12"<?php echo $minute==12?' selected="selected"':'';?>>12</option><option value="13"<?php echo $minute==13?' selected="selected"':'';?>>13</option><option value="14"<?php echo $minute==14?' selected="selected"':'';?>>14</option><option value="15"<?php echo $minute==15?' selected="selected"':'';?>>15</option><option value="16"<?php echo $minute==16?' selected="selected"':'';?>>16</option><option value="17"<?php echo $minute==17?' selected="selected"':'';?>>17</option><option value="18"<?php echo $minute==18?' selected="selected"':'';?>>18</option><option value="19"<?php echo $minute==19?' selected="selected"':'';?>>19</option><option value="20"<?php echo $minute==20?' selected="selected"':'';?>>20</option><option value="21"<?php echo $minute==21?' selected="selected"':'';?>>21</option><option value="22"<?php echo $minute==22?' selected="selected"':'';?>>22</option><option value="23"<?php echo $minute==23?' selected="selected"':'';?>>23</option><option value="24"<?php echo $minute==24?' selected="selected"':'';?>>24</option><option value="25"<?php echo $minute==25?' selected="selected"':'';?>>25</option><option value="26"<?php echo $minute==26?' selected="selected"':'';?>>26</option><option value="27"<?php echo $minute==27?' selected="selected"':'';?>>27</option><option value="28"<?php echo $minute==28?' selected="selected"':'';?>>28</option><option value="29"<?php echo $minute==29?' selected="selected"':'';?>>29</option><option value="30"<?php echo $minute==30?' selected="selected"':'';?>>30</option><option value="31"<?php echo $minute==31?' selected="selected"':'';?>>31</option><option value="32"<?php echo $minute==32?' selected="selected"':'';?>>32</option><option value="33"<?php echo $minute==33?' selected="selected"':'';?>>33</option><option value="34"<?php echo $minute==34?' selected="selected"':'';?>>34</option><option value="35"<?php echo $minute==35?' selected="selected"':'';?>>35</option><option value="36"<?php echo $minute==36?' selected="selected"':'';?>>36</option><option value="37"<?php echo $minute==37?' selected="selected"':'';?>>37</option><option value="38"<?php echo $minute==38?' selected="selected"':'';?>>38</option><option value="39"<?php echo $minute==39?' selected="selected"':'';?>>39</option><option value="40"<?php echo $minute==40?' selected="selected"':'';?>>40</option><option value="41"<?php echo $minute==41?' selected="selected"':'';?>>41</option><option value="42"<?php echo $minute==42?' selected="selected"':'';?>>42</option><option value="43"<?php echo $minute==43?' selected="selected"':'';?>>43</option><option value="44"<?php echo $minute==44?' selected="selected"':'';?>>44</option><option value="45"<?php echo $minute==45?' selected="selected"':'';?>>45</option><option value="46"<?php echo $minute==46?' selected="selected"':'';?>>46</option><option value="47"<?php echo $minute==47?' selected="selected"':'';?>>47</option><option value="48"<?php echo $minute==48?' selected="selected"':'';?>>48</option><option value="49"<?php echo $minute==49?' selected="selected"':'';?>>49</option><option value="50"<?php echo $minute==50?' selected="selected"':'';?>>50</option><option value="51"<?php echo $minute==51?' selected="selected"':'';?>>51</option><option value="52"<?php echo $minute==52?' selected="selected"':'';?>>52</option><option value="53"<?php echo $minute==53?' selected="selected"':'';?>>53</option><option value="54"<?php echo $minute==54?' selected="selected"':'';?>>54</option><option value="55"<?php echo $minute==55?' selected="selected"':'';?>>55</option><option value="56"<?php echo $minute==56?' selected="selected"':'';?>>56</option><option value="57"<?php echo $minute==57?' selected="selected"':'';?>>57</option><option value="58"<?php echo $minute==58?' selected="selected"':'';?>>58</option><option value="59"<?php echo $minute==59?' selected="selected"':'';?>>59</option></select>
<select name="<?php echo $name;?>[second]"><option value="00"<?php echo $second==0?' selected="selected"':'';?>>00</option><option value="01"<?php echo $second==1?' selected="selected"':'';?>>01</option><option value="02"<?php echo $second==2?' selected="selected"':'';?>>02</option><option value="03"<?php echo $second==3?' selected="selected"':'';?>>03</option><option value="04"<?php echo $second==4?' selected="selected"':'';?>>04</option><option value="05"<?php echo $second==5?' selected="selected"':'';?>>05</option><option value="06"<?php echo $second==6?' selected="selected"':'';?>>06</option><option value="07"<?php echo $second==7?' selected="selected"':'';?>>07</option><option value="08"<?php echo $second==8?' selected="selected"':'';?>>08</option><option value="09"<?php echo $second==9?' selected="selected"':'';?>>09</option><option value="10"<?php echo $second==10?' selected="selected"':'';?>>10</option><option value="11"<?php echo $second==11?' selected="selected"':'';?>>11</option><option value="12"<?php echo $second==12?' selected="selected"':'';?>>12</option><option value="13"<?php echo $second==13?' selected="selected"':'';?>>13</option><option value="14"<?php echo $second==14?' selected="selected"':'';?>>14</option><option value="15"<?php echo $second==15?' selected="selected"':'';?>>15</option><option value="16"<?php echo $second==16?' selected="selected"':'';?>>16</option><option value="17"<?php echo $second==17?' selected="selected"':'';?>>17</option><option value="18"<?php echo $second==18?' selected="selected"':'';?>>18</option><option value="19"<?php echo $second==19?' selected="selected"':'';?>>19</option><option value="20"<?php echo $second==20?' selected="selected"':'';?>>20</option><option value="21"<?php echo $second==21?' selected="selected"':'';?>>21</option><option value="22"<?php echo $second==22?' selected="selected"':'';?>>22</option><option value="23"<?php echo $second==23?' selected="selected"':'';?>>23</option><option value="24"<?php echo $second==24?' selected="selected"':'';?>>24</option><option value="25"<?php echo $second==25?' selected="selected"':'';?>>25</option><option value="26"<?php echo $second==26?' selected="selected"':'';?>>26</option><option value="27"<?php echo $second==27?' selected="selected"':'';?>>27</option><option value="28"<?php echo $second==28?' selected="selected"':'';?>>28</option><option value="29"<?php echo $second==29?' selected="selected"':'';?>>29</option><option value="30"<?php echo $second==30?' selected="selected"':'';?>>30</option><option value="31"<?php echo $second==31?' selected="selected"':'';?>>31</option><option value="32"<?php echo $second==32?' selected="selected"':'';?>>32</option><option value="33"<?php echo $second==33?' selected="selected"':'';?>>33</option><option value="34"<?php echo $second==34?' selected="selected"':'';?>>34</option><option value="35"<?php echo $second==35?' selected="selected"':'';?>>35</option><option value="36"<?php echo $second==36?' selected="selected"':'';?>>36</option><option value="37"<?php echo $second==37?' selected="selected"':'';?>>37</option><option value="38"<?php echo $second==38?' selected="selected"':'';?>>38</option><option value="39"<?php echo $second==39?' selected="selected"':'';?>>39</option><option value="40"<?php echo $second==40?' selected="selected"':'';?>>40</option><option value="41"<?php echo $second==41?' selected="selected"':'';?>>41</option><option value="42"<?php echo $second==42?' selected="selected"':'';?>>42</option><option value="43"<?php echo $second==43?' selected="selected"':'';?>>43</option><option value="44"<?php echo $second==44?' selected="selected"':'';?>>44</option><option value="45"<?php echo $second==45?' selected="selected"':'';?>>45</option><option value="46"<?php echo $second==46?' selected="selected"':'';?>>46</option><option value="47"<?php echo $second==47?' selected="selected"':'';?>>47</option><option value="48"<?php echo $second==48?' selected="selected"':'';?>>48</option><option value="49"<?php echo $second==49?' selected="selected"':'';?>>49</option><option value="50"<?php echo $second==50?' selected="selected"':'';?>>50</option><option value="51"<?php echo $second==51?' selected="selected"':'';?>>51</option><option value="52"<?php echo $second==52?' selected="selected"':'';?>>52</option><option value="53"<?php echo $second==53?' selected="selected"':'';?>>53</option><option value="54"<?php echo $second==54?' selected="selected"':'';?>>54</option><option value="55"<?php echo $second==55?' selected="selected"':'';?>>55</option><option value="56"<?php echo $second==56?' selected="selected"':'';?>>56</option><option value="57"<?php echo $second==57?' selected="selected"':'';?>>57</option><option value="58"<?php echo $second==58?' selected="selected"':'';?>>58</option><option value="59"<?php echo $second==59?' selected="selected"':'';?>>59</option></select><?php
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
			if($key==$default)echo ' selected="selected"';
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
?>(<input type="text" name="<?php echo $name;?>[area]" value="<?php echo $area;?>" maxlength="3" />)<input type="text" name="<?php echo $name;?>[prefix]" value="<?php echo $prefix;?>" maxlength="3" />-<input type="text" name="<?php echo $name;?>[line]" value="<?php echo $line;?>" maxlength="4" /><?php
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
	echo '<input type="text" name="'.$name.'" value="'.$value.'" ';
	echo combine_attrib($extra);
	echo '/>'.EOL;
}
function form_text_labeled($name,$id,$label,$value=null,array $attrib=null){
	if($value==null)$value=form_get($name);
	echo '<label for="'.$id.'">'.$label.'</label>';
	echo '<input type="text" name="'.$name.'" value="'.$value.'" ';
	echo combine_attrib($attrib);
	echo '/>'.EOL;
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
		echo $before.'<label><input type="radio" name="'.$name.'" value="'.$value.'" id="'.$id.$value.'"'.(($value==$val)?' checked="checked" ':' ').$attrib.'/>&nbsp;'.$disp.'</label>'.$after;
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
		echo $before.'<label for="'.$id.$value.'"><input type="checkbox" name="'.$name.'[]" value="'.$value.'" id="'.$id.$value.'"'.(in_array($value,$checked)?' checked="checked"':'').$attrib.'/>&nbsp;'.$disp.'</label>'.$after;
	}
}
function form_textarea($sName,$iCols,$iRows,array $attrib=array()){
	$attrib=combine_attrib($attrib);
	$val=form_get($sName,'');
	echo '<textarea name="'.$sName.'" cols="'.$iCols.'" rows="'.$iRows.'" '.$attrib.'>'.$val.'</textarea>';
}

/**
 * use is_date
 * @deprecated
 */
function isValidDate($name){
	return is_date($name);
}
/**
 * use is_phone
 * @deprecated
 */
function isValidPhone($value){
	return is_phone($value);
}

/**
 * use is_alphastr
 * @deprecated
 */
function isAlphaStr($value,$min=1,$max=1){
	return is_alphastr($value,$min,$max);
}
/**
 * use is_aplhanumstr
  * @deprecated
 */
function isAlphaNumStr($value,$min=1,$max=1){
	return is_alphanumstr($value,$min,$max);
}
/**
 * use is_namestr
 * @deprecated
 */
function isNameStr($value,$min=1,$max=1){
	return is_namestr($value,$min,$max);
}
/**
 * use is_address
 * @deprecated
 */
function isAddressStr($value){
	return is_address($value);
}
/**
 * use is_zip
 * @deprecated
 */
function isZipStr($value){
	return is_zip($value);
}
/**
 * use is_wordstr
 * @deprecated
 */
function isWordStr($value,$min=1,$max=1){
	return is_wordstr($value,$min,$max);
}
/**
 * use is_email
 * @deprecated
 */
function isValidEmail($value){
	return is_email($value);
}
/**
 * use is_url
 * @deprecated
 */
function isValidURL($url){
	return is_url($url);
}
/**
 * use is_validselection
 * @deprecated
 */
function isValidSelection($name,array $valid){
	return is_validselection($name, $valid);
}
/**
 * use is_minselected
 * @deprecated
 */
function isMinSelected($name,$iMin){
	return is_minselected($name, $iMin);
}
/**
 * use is_selected
 * @deprecated
 */
function isSelected($name,$value){
	return is_selected($name, $value);
}
/**
 * use is_notselected
 * @deprecated
 */
function notSelected($name,$value){
	return is_notselected($name, $value);
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
 * Tests to see if the field only contains A-Z and a-z.
 * @param string $value
 * @param number $min Defaults to 1. Minimum number of space delimited words allowed. 0 returns true instead of E_FORM_EMPTY.
 * @param number $max Defaults to 1. Maximum number of space delimited words allowed. 0 means unlimited.
 * @return boolean|integer true if valid, false if not, or an error constant
 */
function is_alphastr($value,$min=1,$max=1){
	if(empty($value))
		return ($min==0)?true:E_FORM_EMPTY;
	if($max==1)
		return preg_match('/^[A-Za-z]+$/',$value)===1;
	if($max==0){
		if($min>1)
			return (preg_match('/^([A-Za-z]+ +){'.($min-1).',}[A-Za-z]+$/',$value)>=$min)?true:E_FORM_MINNOTMET;
		else
			return preg_match('/^[A-Za-z ]+$/',$value)===1;
	}
	$max--;
	if($min>0)$min--;
	return (preg_match("/^[A-Za-z]+( +[A-Za-z]+){".$min.",$max}$/",$value)===1)?true:E_FORM_LIMITEXCEEDED;
}
/**
 * Tests to see if the field only contains A-Z, a-z, and 0-9.
 * @param string $value
 * @param number $min Defaults to 1. Minimum number of space delimited words allowed. 0 returns true instead of E_FORM_EMPTY.
 * @param number $max Defaults to 1. Maximum number of space delimited words allowed. 0 means unlimited.
 * @return boolean|integer true if valid, false if not, or an error constant
 */
function is_alphanumstr($value,$min=1,$max=1){
	if(empty($value))
		return ($min==0)?true:E_FORM_EMPTY;
	if($max==1)
		return preg_match('/^[A-Za-z0-9]+$/',$value)===1;
	if($max==0){
		if($min>1)
			return (preg_match('/^([A-Za-z0-9]+ +){'.($min-1).',}[A-Za-z0-9]+$/',$value)>=$min)?true:E_FORM_MINNOTMET;
		else
			return preg_match('/^[A-Za-z0-9 ]+$/',$value)===1;
	}
	$max--;
	if($min>0)$min--;
	return (preg_match("/^[A-Za-z0-9]+( +[A-Za-z0-9]+){".$min.",$max}$/",$value)===1)?true:E_FORM_LIMITEXCEEDED;
}
/**
 * Tests to see if the field only contains A-Z, a-z, apostrophe, hyphen, comma and period.
 * @param string $value
 * @param number $min Defaults to 1. Minimum number of space delimited words allowed. 0 returns true instead of E_FORM_EMPTY.
 * @param number $max Defaults to 1. Maximum number of space delimited words allowed. 0 means unlimited.
 * @return boolean|integer true if valid, false if not, or an error constant
 */
function is_namestr($value,$min=1,$max=1){
	if(empty($value))
		return ($min==0)?true:E_FORM_EMPTY;
	if($max==1)
		return preg_match('/^[A-Za-z\'\.\-,]+$/',$value)===1;
	if($max==0){
		if($min>1)
			return (preg_match('/^([A-Za-z\'\.\-,]+ +){'.($min-1).',}[A-Za-z\'\.\-,]+$/',$value)===1)?true:E_FORM_MINNOTMET;
		else
			return preg_match('/^[A-Za-z\'\.\-, ]+$/',$value)===1;
	}
	$max--;
	if(preg_match('/^([A-Za-z\'\.\-,]+ +){'.($min-1).','.$max.'}[A-Za-z\'\.\-,]+$/',$value)===1)return true;
	return (preg_match('/^([A-Za-z\'\.\-,]+ +){'.($min-1).',}[A-Za-z\'\.\-,]+$/',$value)===1)?E_FORM_LIMITEXCEEDED:E_FORM_MINNOTMET;
	//return (preg_match("/^[A-Za-z'\\.\\-]+( +[A-Za-z'\\.\\-]+){".$min.",$max}$/",$value)===1)?true:E_FORM_LIMITEXCEEDED;
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
	if(empty($value))
		return ($min==0)?true:E_FORM_EMPTY;
	$legal='A-Za-z0-9\-\/\+=~`\.,:;<>\{\}\[\]\(\)\|!@\$%\^&\*\'"_\?\\\#';
	if($max==1)
		return preg_match('/^['.$legal.']+$/',$value)===1;
	if($max==0){
		if($min>1)
			return (preg_match('/^(['.$legal.']+[ \r\n\t]+){'.($min-1).',}[.$legal.]+$/',$value)===1)?true:E_FORM_MINNOTMET;
		else
			return preg_match('/^['.$legal.' \r\n\t]+$/',$value)===1;
	}
	$max--;
	if($min>0)$min--;
	return (preg_match("/^[$legal]+([ \r\n\t]+[$legal]+){".$min.",$max}$/",$value)===1)?true:E_FORM_LIMITEXCEEDED;
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