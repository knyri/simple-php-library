<?php
/**
 * Date comparison functions.
 * Those ending in '_ary' take array('year'=>year, 'month'=>month, 'day'=>day) as the date.
 *
 * @author Kenneth Pierce
 */


/**
 * Determines if the two dates are equal.
 * Accepts a Linux date or timestamp or an array with the indecies 'day','month', and 'year'.
 * @param mixed $start
 * @param mixed $end
 */
function date_equal($start, $end){
	if (is_array($start)&&is_array($end)) return date_equal_ary($start, $end);
	if (is_string($start)&&is_string($end)){
		$start=explode('-', $start);
		$end=explode('-', $end);
		return date_equal_int($start[1], $start[2], $start[0], $end[1], $end[2], $end[0]);
	}
}
/**
 * Tests to see if the dates are equal.
 * @param int $smonth The starting month
 * @param int $sday The starting day
 * @param int $syear The starting year
 * @param int $emonth The ending month
 * @param int $eday The ending day
 * @param int $eyear The ending year
 * @return boolean True if the dates are equal.
 */
function date_equal_int($smonth, $sday, $syear, $emonth, $eday, $eyear){
	if ($smonth ==$emonth){
		if ($sday ==$eday){
			if ($syear ==$eyear){
				return true;
			}
		}
	}
	return false;
}
function date_equal_ary(array $start, array $end){
	foreach ($start as $key=>$value){
		if ($end[$key]!=$value) return false;;
	}
	return true;
}
/**
 * Tests if $start>$end.
 * Accepts a Linux date or timestamp or an array with the indecies 'day','month', and 'year'.
 * @param mixed $start
 * @param mixed $end
 * @return boolean True if $start>$end
 */
function date_greater($start, $end){
	if (is_array($start)&&is_array($end)) return date_greater_ary($start, $end);
	if (is_string($start)&&is_string($end)){
		$start=explode('-', $start);
		$end=explode('-', $end);
		return date_greater_int($start[1], $start[2], $start[0], $end[1], $end[2], $end[0]);
	}
}
function date_greater_int($smonth, $sday, $syear, $emonth, $eday, $eyear){
	if ($syear>$eyear) return true;
	if ($syear==$eyear){
		if ($smonth>$emonth) return true;
		if ($smonth==$emonth){
			if ($sday>$eday) return true;
		}
	}
	return false;
}
function date_greater_ary(array $start, array $end){
	if ($start['year']>$end['year']) return true;
	if ($start['year']==$end['year']){
		if ($start['month']>$end['month']) return true;
		if ($start['month']==$end['month']){
			if ($start['day']>$end['day']) return true;
		}
	}
	return false;
}
/**
 * Tests if $start >=$end. Alias for!date_lesser($start, $end).
 * Accepts a Linux date or timestamp or an array with the indecies 'day','month', and 'year'.
 * @param mixed $start
 * @param mixed $end
 * @return boolean True if $start >=$end
 */
function date_equal_greater($start, $end){
	return!date_lesser($start, $end);
}
/**
 * Tests if $start >=$end. Alias for!date_greater($start, $end).
 * Accepts a Linux date or timestamp or an array with the indecies 'day','month', and 'year'.
 * @param mixed $start
 * @param mixed $end
 * @return boolean True if $start >=$end
 */
function date_equal_lesser($start, $end){
	return!date_greater($start, $end);
}
/**
 * Tests if $start<$end.
 * Accepts a Linux date or timestamp or an array with the indecies 'day','month', and 'year'.
 * @param mixed $start
 * @param mixed $end
 * @return boolean True if $start<$end
 */
function date_lesser($start, $end){
	if (is_array($start)&&is_array($end)) return date_lesser_ary($start, $end);
	if (is_string($start)&&is_string($end)){
		$start=explode('-', $start);
		$end=explode('-', $end);
		return date_lesser_int($start[1], $start[2], $start[0], $end[1], $end[2], $end[0]);
	}
}
function date_lesser_int($smonth, $sday, $syear, $emonth, $eday, $eyear){
	if ($syear<$eyear) return true;
	if ($syear==$eyear){
		if ($smonth<$emonth) return true;
		if ($smonth==$emonth){
			if ($sday<$eday) return true;
		}
	}
	return false;
}
function date_lesser_ary(array $start, array $end){
	if ($start['year']<$end['year']) return true;
	if ($start['year']==$end['year']){
		if ($start['month']<$end['month']) return true;
		if ($start['month']==$end['month']){
			if ($start['day']<$end['day']) return true;
		}
	}
	return false;
}
function date_create_unix_ary($date){
	return $date['year'] . '-' . $date['month'] . '-' . $date['day'];
}
function date_create_unix_timestamp_ary($date){
	return $date['year'] . '-' . $date['month'] . '-' . $date['day'] . ' 00:00:00';
}
/**
 * Fixes the date so that it is valid. Can handle negative values. Great for creating date ranges.
 * Ex. 2-29-2001 will become 3-1-2001
 * 15-6-2011 will become 3-6-2012
 * 3-0-2001 will become 2-28-2001
 * -10-29-2002 will become 3-1-2001
 * @param integer $month 0 values decrement a year and is set to 12.
 * @param integer $day 0 values decrement a month and is set to the last day of that month.
 * @param integer $year
 * @param boolean $unix Optional. Default is true. Use UNIX date format
 * @return string The resulting string
 */
function date_fix_date($month,$day,$year,$unix=true){
	if($month>12){
		while ($month>12){
			$month-=12;//subtract a $year
			$year++;//add a $year
		}
	} else if ($month<1){
		while ($month<1){
			$month +=12;//add a $year
			$year--;//subtract a $year
		}
	}
	if ($day>31){
		while ($day>31){
			if ($month==2){
				if (is_leap_year($year)){//subtract a $month
					$day-=29;
				} else{
					$day-=28;
				}
				$month++;//add a $month
			} else if (date_hasThirtyOneDays($month)){
				$day-=31;
				$month++;
			} else{
				$day-=30;
				$month++;
			}
		}//end while
		while ($month>12){ //recheck $months
			$month-=12;//subtract a $year
			$year++;//add a $year
		}
	} else if ($day<1){
		while ($day<1){
			$month--;//subtract a $month
			if ($month==2){
				if (is_leap_year($year)){//add a $month
					$day+=29;
				}else{
					$day+=28;
				}
			} else if (date_hasThirtyOneDays($month)){
				$day+=31;
			} else{
				$day+=30;
			}
		}//end while
		while ($month<1){//recheck $months
			$month+=12;//add a $year
			$year--;//subtract a $year
		}
	} else if ($month==2){
		if(is_leap_year($year)){
			if($day>29){
				$day-=29;
				$month++;
			}
		}else if($day>28){
			$day-=28;
			$month++;
		}
	} else if (!date_hasThirtyOneDays($month)&&$day>30){
		$day-=30;
		$month++;
	}
	if ($year<1900) $year=1900;
	if ($unix){
		return "$year-$month-$day";
	} else{
		return "$month-$day-$year";
	}
}
/**
 * Fixes the date so that it is valid.
 * Ex. 2-29-2001 24:00:00 will become 3-2-2001 00:00:00
 * 15-6-2011 15:90:36 will become 3-6-2012 16:30:36
 * @param int $month
 * @param int $day
 * @param int $year
 * @param int $hour
 * @param int $minute
 * @param int $second
 * @param int $unix Optional. Default is true. Use UNIX date format
 * @return string The resulting string
 */
function date_fix_timestamp($month, $day, $year, $hour, $minute, $second, $unix=true){
	if ($month ==0) $month=1;
	if ($day ==0) $day=1;
	while ($second<0){
		$minute--;
		$second+=60;
	}
	while ($second >=60){
		$second-=60;
		$minute++;
	}
	while($minute<0){
		$hour--;
		$minute +=60;
	}
	while ($minute >=60){
		$minute-=60;
		$hour++;
	}
	while ($hour<0){
		$day--;
		$hour+=24;
	}
	while ($hour >=24){
		$hour-=24;
		$day++;
	}
	return date_fix_date($month,$day,$year,$unix)." $hour:$minute:$second";
}
/**
 * Checks to see if the month has 31 days.
 * @param integer $month
 * @return boolean True if the month has 31 days
 */
function date_hasThirtyOneDays($month){
	//1234567 89012:1357 802
	//JfMaMjJ AsOnD:JMMJ AOD
	if ($month<8)
		return $month%2==1;
	else
		return $month%2==0;
}
/**
 * Checks to see if the year is a leap year.
 * @param integer $year
 * @return boolean True if the year is a leap year
 */
function is_leap_year($year){
	return (0==$year%4&&0!=$year%100||0==$year%400);
}
function getdatetimestamp($timestamp=false){
	if ($timestamp===false)
		return date("YmdHis");
	else
		return date("YmdHis",$timestamp);
}
function date_get_date($timestamp){
	$split=explode(' ',$timestamp);
	return $split[0];
}
function date_get_range_month($month,$year){
	$range=array();
	$range['start']=array('month'=>$month,'year'=>$year,'day'=>1);
	$range['end']=array('month'=>$month,'year'=>$year,'day'=>date_get_last_day($month, $year));
	return $range;
}
function date_get_last_day($month,$year){
	if($month==2){
		if(is_leap_year($year)){
			return 29;
		}else{
			return 28;
		}
	}else{
		if(date_hasThirtyOneDays($month)){
			return 31;
		}else{
			return 30;
		}
	}
}