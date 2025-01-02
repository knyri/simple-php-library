<?php
/**
 * @package time
 */

include_once 'date.functions.php';
class SDate{
	/**
	 * array(
	 *	0=> Year
	 *	1=> Month
	 *	2=> Day
	 * )
	 * @var array
	 */
	private $date;
	/**
	 * array(
	 *	0=> hour
	 *	1=> minute
	 *	2=> second
	 * )
	 * @var array
	 */
	private $time;
	/**
	 * array(
	 *	0=> hour offset
	 *	1=> minute offset
	 *	2=> total offest in minutes
	 * )
	 * @var array
	 */
	private $offset;
	/**
	 * @var \DateTime
	 */
	private $dateObj;
	/**
	 * @param int $timestamp
	 */
	public function __construct($timestamp){
		$d=date('Y_m_d|H_i_s|O',$timestamp);
		$this->dateObj= DateTime::createFromFormat('Y\\_m\\_d\\|H\\_i\\_s\\|O', $d);
		if($this->dateObj === false){
			//logit(print_r(DateTime::getLastErrors(), true));
			throw new Exception($d . " failed to parse");
		}
		$d=explode('|',$d);
		$this->date=array_map('intval',explode('_',$d[0]),array(10,10,10));
		$this->time=array_map('intval',explode('_',$d[1]),array(10,10,10));
		// offset in minutes; $d[2][0] is to preserve the sign
		$this->offset= (intval(substr($d[2],0,-2),10)*60) + intval($d[2][0].substr($d[2],-2),10);
		//logit($this->dateObj->format('Y-m-d\\TH:i:sO'));
	}
	/**
	 * @return int the year
	 */
	public function getYear(){return $this->date[0];}
	/**
	 * @return int the month
	 */
	public function getMonth(){return $this->date[1];}
	/**
	 * @return int the day
	 */
	public function getDay(){return $this->date[2];}
	/**
	 * @return int the hour
	 */
	public function getHour(){return $this->time[0];}
	/**
	 * @return int the minute
	 */
	public function getMinute(){return $this->time[1];}
	/**
	 * @return int the second
	 */
	public function getSecond(){return $this->time[2];}
	/**
	 * @return int The offset from UTC in minutes
	 */
	public function getOffset(){return $this->offset;}

	public function addRange(SDateRange $r){
		$this->date[0]+=$r->getYear();
		$this->date[1]+=$r->getMonth();
		$this->date[2]+=$r->getDay();
		$this->time[0]+=$r->getHour();
		$this->time[1]+=$r->getMinute();
		$this->time[2]+=$r->getSecond();
		$this->normalize();
	}
	public function subtractRange(SDateRange $r){
		$this->date[0]-=$r->getYear();
		$this->date[1]-=$r->getMonth();
		$this->date[2]-=$r->getDay();
		$this->time[0]-=$r->getHour();
		$this->time[1]-=$r->getMinute();
		$this->time[2]-=$r->getSecond();
		$this->normalize();
	}
	public function getDayOfWeek(){
		return getdate($this->dateObj->getTimestamp())['wday'];
	}
	public function getDayOfYear(){
		return getdate($this->dateObj->getTimestamp())['yday'];
	}
	public function setDay($v){
		$this->date[2]= $v;
		$lastDay= date_get_last_day($this->date[1], $this->date[0]);
		while($this->date[2] > $lastDay){
			$this->date[2]-= $lastDay;
			$this->date[1]++;
			if($this->date[1] == 13){
				$this->date[1]= 1;
				$this->date[0]++;
			}
			$lastDay= date_get_last_day($this->date[1], $this->date[0]);
		}
		while($this->date[2] < 1){
			$lastDay= date_get_last_day($this->date[1], $this->date[0]);
			$this->date[2]+= $lastDay;
			$this->date[1]--;
			if($this->date[1] == 0){
				$this->date[1]= 12;
				$this->date[0]--;
			}
		}
		$this->dateObj->setDate($this->date[0], $this->date[1], $this->date[2]);
		//logit('Day changed: ' . $this->dateObj->format('Y-m-d\\TH:i:sO'));
		return $this;
	}
	public function setMonth($v){
		$this->date[1]= $v;
		while($this->date[1] > 12){
			$this->date[1]-= 12;
			$this->date[0]++;
		}
		while($this->date[1] < 1){
			$this->date[1]+= 12;
			$this->date[0]--;
		}
		$lastDay= date_get_last_day($this->date[1], $this->date[0]);
		while($this->date[2] > $lastDay){
			$this->date[2]-= $lastDay;
			$this->date[1]++;
			if($this->date[1] == 13){
				$this->date[1]= 1;
				$this->date[0]++;
			}
			$lastDay= date_get_last_day($this->date[1], $this->date[0]);
		}
		$this->dateObj->setDate($this->date[0], $this->date[1], $this->date[2]);
		//logit('Month changed: ' . $this->dateObj->format('Y-m-d\\TH:i:sO'));
		return $this;
	}
	public function setYear($v){
		$this->date[0]= $v;
		// fix for leap years
		$lastDay= date_get_last_day($this->date[1], $this->date[0]);
		while($this->date[2] > $lastDay){
			$this->date[2]-= $lastDay;
			$this->date[1]++;
			if($this->date[1] == 13){
				$this->date[1]= 1;
				$this->date[0]++;
			}
			$lastDay= date_get_last_day($this->date[1], $this->date[0]);
		}
		$this->dateObj->setDate($this->date[0], $this->date[1], $this->date[2]);
		//logit('Year changed: ' . $this->dateObj->format('Y-m-d\\TH:i:sO'));
		return $this;
	}
	public function setSecond($v){
		$this->time[2]= $v;
		if($this->time[2] < 0) do{
			$this->time[1]--;
			$this->time[2]+= 60;
		}while($this->time[2] < 0);
		else{
			while($this->time[2] > 59){
				$this->time[2]-= 60;
				$this->time[1]++;
			}
		}
		$this->dateObj->setDate($this->date[0], $this->date[1], $this->date[2]);
		$this->dateObj->setTime($this->time[0], $this->time[1], $this->time[2]);
		//logit('Second changed: ' . $this->dateObj->format('Y-m-d\\TH:i:sO'));
		return $this;
	}
	public function setMinute($v){
		$this->time[1]= $v;
		if($this->time[1] < 0) do{
			$this->time[0]--;
			$this->time[1]+= 60;
		}while($this->time[1] < 0);
		else{
			while($this->time[1] > 59){
				$this->time[1]-= 60;
				$this->time[0]++;
			}
		}
		$this->dateObj->setDate($this->date[0], $this->date[1], $this->date[2]);
		$this->dateObj->setTime($this->time[0], $this->time[1], $this->time[2]);
		//logit('Minute changed: ' . $this->dateObj->format('Y-m-d\\TH:i:sO'));
		return $this;
	}
	public function setHour($v){
		$this->time[0]= $v;
		if($this->time[0] < 0) do{
			$this->date[2]--;
			$this->time[0]+= 24;
		}while($this->time[0] < 0);
		else{
			while($this->time[0] > 23){
				$this->time[0]-= 24;
				$this->date[2]++;
			}
		}
		$this->dateObj->setDate($this->date[0], $this->date[1], $this->date[2]);
		$this->dateObj->setTime($this->time[0], $this->time[1], $this->time[2]);
		//logit('Hour changed: ' . $this->dateObj->format('Y-m-d\\TH:i:sO'));
		return $this;
	}
	public function toDateTime(){
		$this->normalize();
		return $this->dateObj;
	}
	public function setDateTimeFrom(SDate $date){
		$this->time[0]= $date->getHour();
		$this->time[1]= $date->getMinute();
		$this->time[2]= $date->getSecond();
		$this->date[0]= $date->getYear();
		$this->date[1]= $date->getMonth();
		$this->date[2]= $date->getDay();
		$this->offset= $date->getOffset();
		$offset= intval($this->offset / 60) * 100 + abs($this->offset % 60);
		if($offset == 0){
			// doesn't like +0000 for some reason
			$this->dateObj->setTimezone(new DateTimeZone('UTC'));
		}else if($offset < 0){
			if($offset > -10){
				$this->dateObj->setTimezone(new DateTimeZone('-000' . $offset));
			}else if($offset > -100){
				$this->dateObj->setTimezone(new DateTimeZone('-00' . $offset));
			}else if($offset > -1000){
				$this->dateObj->setTimezone(new DateTimeZone('-0' . $offset));
			}else{
				$this->dateObj->setTimezone(new DateTimeZone('-' . $offset));
			}
		}else{
			if($offset < 10){
				$this->dateObj->setTimezone(new DateTimeZone('+000' . $offset));
			}else if($offset < 100){
				$this->dateObj->setTimezone(new DateTimeZone('+00' . $offset));
			}else if($offset < 1000){
				$this->dateObj->setTimezone(new DateTimeZone('+0' . $offset));
			}else{
				$this->dateObj->setTimezone(new DateTimeZone('+' . $offset));
			}
		}
		$this->normalize();
	}
	/**
	 * Fixes the date and time
	 */
	public function normalize(){
		/*
		 * Fix the time
		 */
		//Seconds
		if($this->time[2] < 0) do{
				$this->time[1]--;
				$this->time[2]+= 60;
			}while($this->time[2] < 0);
		else{
			while($this->time[2] > 59){
				$this->time[2]-= 60;
				$this->time[1]++;
			}
		}
		//minutes
		if($this->time[1] < 0) do{
				$this->time[0]--;
				$this->time[1]+= 60;
			}while($this->time[1] < 0);
		else{
			while($this->time[1] > 59){
				$this->time[1]-= 60;
				$this->time[0]++;
			}
		}
		//hours
		if($this->time[0] < 0) do{
				$this->date[2]--;
				$this->time[0]+= 24;
			}while($this->time[0] < 0);
		else{
			while($this->time[0] > 23){
				$this->time[0]-= 24;
				$this->date[2]++;
			}
		}
		/*
		 * Fix the date
		 */
		while($this->date[1] > 12){
			$this->date[1]-= 12;
			$this->date[0]++;
		}
		while($this->date[1] < 1){
			$this->date[1]+= 12;
			$this->date[0]--;
		}
		$lastDay= date_get_last_day($this->date[1], $this->date[0]);
		while($this->date[2] > $lastDay){
			$this->date[2]-= $lastDay;
			$this->date[1]++;
			if($this->date[1] == 13){
				$this->date[1]= 1;
				$this->date[0]++;
			}
			$lastDay= date_get_last_day($this->date[1], $this->date[0]);
		}
		while($this->date[2] < 1){
			$lastDay= date_get_last_day($this->date[1], $this->date[0]);
			$this->date[2]+= $lastDay;
			$this->date[1]--;
			if($this->date[1] == 0){
				$this->date[1]= 12;
				$this->date[0]--;
			}
		}
		$this->dateObj->setTime($this->time[0], $this->time[1], $this->time[2]);
		$this->dateObj->setDate($this->date[0], $this->date[1], $this->date[2]);
		//logit('Normalized: ' . $this->dateObj->format('Y-m-d\\TH:i:sO'));
	}
	public function daysInMonth(){
		if($this->date[1]==2){
			if(is_leap_year($this->date[0]))
				return 29;
			else
				return 28;
		}
		return date_hasThirtyOneDays($this->date[1])?31:30;
	}
}
class SDateRange{
	/**
	 * array(
	 *	0=> Year
	 *	1=> Month
	 *	2=> Day
	 *	3=> hour
	 *	4=> minute
	 *	5=> second
	 *	6=> array{
	 *		0=> hour offset
	 *		1=> minute offset
	 *		2=> offset in minutes
	 *	)
	 * )
	 * @var array
	 */
	private $dt;
	/**
	 * All parameters may be positive or negative
	 * @param int $y years
	 * @param int $m months
	 * @param int $d days
	 * @param int $h hours
	 * @param int $i minutes
	 * @param int $s seconds
	 * @param string $o (null) GMT offset (ex. +200; -1030; -0600)
	 */
	public function __construct($y,$m,$d,$h,$i,$s,$o=null){
		$this->dt[0]=intval($y,10);
		$this->dt[1]=intval($m,10);
		$this->dt[2]=intval($d,10);
		$this->dt[3]=intval($h,10);
		$this->dt[4]=intval($i,10);
		$this->dt[5]=intval($s,10);
		if($o==null)
			$this->dt[6]=null;
		else{
			$this->dt[6]=array(intval(substr($o,0,-2),10),intval($o[0].substr($o,-2),10));
			$this->dt[6][2]=$this->dt[6][0]*60+$this->dt[6][1];
		}
	}
	public function getYear(){return $this->dt[0];}
	public function getMonth(){return $this->dt[1];}
	public function getDay(){return $this->dt[2];}
	public function getHour(){return $this->dt[3];}
	public function getMinute(){return $this->dt[4];}
	public function getSecond(){return $this->dt[5];}
	/**
	 * @return int The offset from GMT in minutes
	 */
	public function getOffset(){return $this->dt[6][2];}
}