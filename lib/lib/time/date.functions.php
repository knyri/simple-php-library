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
	return $smonth==$emonth && $sday==$eday && $syear==$eyear;
}
function date_equal_ary(array $start, array $end){
	foreach ($start as $key=>$value){
		if ($end[$key]!=$value) return false;
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
	return $syear>$eyear ||
	$syear==$eyear && ($smonth>$emonth || ($smonth==$emonth && $sday>$eday));
}
function date_greater_ary(array $start, array $end){
	return $start['year']>$end['year'] ||
		$start['year']==$end['year'] &&
		(
			$start['month']>$end['month'] ||
			($start['month']==$end['month'] && $start['day']>$end['day'])
		);
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
	if(is_array($start)&&is_array($end)) return date_lesser_ary($start, $end);
	if(is_string($start)&&is_string($end)){
		$start=explode('-', $start);
		$end=explode('-', $end);
		return date_lesser_int($start[1],$start[2],$start[0],$end[1],$end[2],$end[0]);
	}
}
function date_lesser_int($smonth, $sday, $syear, $emonth, $eday, $eyear){
	return $syear<$eyear || $syear==$eyear && ($smonth<$emonth || ($smonth==$emonth && $sday<$eday));
}
function date_lesser_ary(array $start, array $end){
	return $start['year']<$end['year'] ||
		$start['year']==$end['year'] && ($start['month']<$end['month'] || ($start['month']==$end['month'] && $start['day']<$end['day']));
}
function date_create_unix_ary($date){
	return $date['year'].'-'.$date['month'].'-'.$date['day'];
}
function date_create_unix_timestamp_ary($date){
	return $date['year'].'-'.$date['month'].'-'.$date['day'].' 00:00:00';
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
	while($month>12){
		$month-=12;
		$year++;
	}
	while($month<1){
		$month+=12;
		$year--;
	}
	$lastDay=date_get_last_day($month,$year);
	while($day>$lastDay){
		$day-=$lastDay;
		$month++;
		if($month==13){
			$month=1;
			$year++;
		}
		$lastDay=date_get_last_day($month,$year);
	}
	while($day<1){
		$month--;
		if($month==0){
			$month=12;
			$year--;
		}
		$lastDay=date_get_last_day($month,$year);
		$day+=$lastDay;
	}

	if($year<1900) $year=1900;
	return $unix?"$year-$month-$day":"$month-$day-$year";
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
function date_fix_timestamp($month,$day,$year,$hour,$minute,$second,$unix=true){
	while($second<0){
		$minute--;
		$second+=60;
	}
	while($second>59){
		$second-=60;
		$minute++;
	}
	while($minute<0){
		$hour--;
		$minute+=60;
	}
	while($minute>59){
		$minute-=60;
		$hour++;
	}
	while($hour<0){
		$day--;
		$hour+=24;
	}
	while($hour>23){
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
	return ($month<8)? $month%2==1: $month%2==0;
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
/**
 * Returns an array containing the start and end dates for the month.
 * array(
 *  'start'=>array(
 *   'month'=>month,
 *   'year'=>year,
 *   'day'=>1
 *  ),
 *  'end'=>array(
 *   'month'=>month,
 *   'year'=>year,
 *   'day'=>day
 *  )
 * )
 * @param int $month
 * @param int $year
 * @return array
 */
function date_get_range_month($month,$year){
	$range=array();
	$range['start']=array('month'=>$month,'year'=>$year,'day'=>1);
	$range['end']=array('month'=>$month,'year'=>$year,'day'=>date_get_last_day($month, $year));
	return $range;
}
/**
 * @param int $month
 * @param int $year
 * @return number Number of days in the month
 */
function date_get_last_day($month,$year){
	if($month==2)
		return is_leap_year($year)?29:28;
	else
		return date_hasThirtyOneDays($month)?31:30;
}