<?php
include_once 'date.functions.php';
class SDate{
	/**
	 * array(
	 * 	0=> Year
	 * 	1=> Month
	 * 	2=> Day
	 * )
	 * @var array
	 */
	private $date;
	/**
	 * array(
	 * 	0=> hour
	 * 	1=> minute
	 * 	2=> second
	 * )
	 * @var array
	 */
	private $time;
	/**
	 * array(
	 * 	0=> hour offset
	 * 	1=> minute offset
	 * 	2=> total offest in minutes
	 * )
	 * @var array
	 */
	private $offset;
	/**
	 * @param int $timestamp
	 */
	public function __constructor($timestamp){
		$d=date('Y_m_d|H_i_s|O',$timestamp);
		$d=explode('|',$d);
		$this->date=array_map('intval',explode('_',$d[0]),array(10,10,10));
		$this->time=array_map('intval',explode('_',$d[1]),array(10,10,10));
		$this->offset=array(intval(substr($d[2],0,-2),10),intval($d[2][0].substr($d[2],-2),10));
		$this->offset[2]=$this->offset[0]*60+$this->offset[1];
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
	 * @return int The offset from GMT in minutes
	 */
	public function getOffset(){return $this->offset[2];}

	public function addRange(SDateRange $r){
		$this->date[0]+=$r->getYear();
		$this->date[1]+=$r->getMonth();
		$this->date[2]+=$r->getDay();
		$this->time[0]+=$r->getHour();
		$this->time[1]+=$r->getMinute();
		$this->time[2]+=$r->getSecond();
		if($r->getOffset()!=null){
			$this->time[1]+=$this->offset[2]-$r->getOffset();
		}
	}
	public function subtractRange(SDateRange $r){
		$this->date[0]-=$r->getYear();
		$this->date[1]-=$r->getMonth();
		$this->date[2]-=$r->getDay();
		$this->time[0]-=$r->getHour();
		$this->time[1]-=$r->getMinute();
		$this->time[2]-=$r->getSecond();
		if($r->getOffset()!=null){
			$this->time[1]-=$this->offset[2]-$r->getOffset();
		}
	}
	/**
	 * Fixes the date and time
	 */
	public function normalize(){
		/*
		 * Fix the time
		 */
		//Seconds
		if($this->time[2]<0) do{
				$this->time[1]--;
				$this->time[2]+=60;
			}while($this->time[2]<0);
		else{
			while($this->time[2]>59){
				$this->time[2]-=60;
				$this->time[1]++;
			}
		}
		//minutes
		if($this->time[1]<0) do{
				$this->time[0]--;
				$this->time[1]+=60;
			}while($this->time[1]<0);
		else{
			while($this->time[1]>59){
				$this->time[1]-=60;
				$this->time[0]++;
			}
		}
		//hours
		if($this->time[0]<0) do{
				$this->date[2]--;
				$this->time[0]+=24;
			}while($this->time[0]<0);
		else{
			while($this->time[0]>23){
				$this->time[0]-=24;
				$this->date[2]++;
			}
		}
		/*
		 * Fix the date
		 */
		while($this->date[1]>12){
			$this->date[1]-=12;
			$this->date[0]++;
		}
		while($this->date[1]<1){
			$this->date[1]+=12;
			$this->date[0]--;
		}
		$lastDay=date_get_last_day($this->date[1],$this->date[0]);
		while($this->date[2]>$lastDay){
			$this->date[2]-=$lastDay;
			$this->date[1]++;
			if($this->date[1]==13){
				$this->date[1]=1;
				$this->date[0]++;
			}
			$lastDay=date_get_last_day($this->date[1],$this->date[0]);
		}
		while($this->date[2]<1){
			$lastDay=date_get_last_day($this->date[1],$this->date[0]);
			$this->date[2]+=$lastDay;
			$this->date[1]--;
			if($this->date[1]==0){
				$this->date[1]=12;
				$this->date[0]--;
			}
		}
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
	 * 	0=> Year
	 * 	1=> Month
	 * 	2=> Day
	 * 	3=> hour
	 * 	4=> minute
	 * 	5=> second
	 * 	6=> array{
	 * 		0=> hour offset
	 * 		1=> minute offset
	 * 		2=> offset in minutes
	 * 	)
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
	 * @param string $o GMT offset (ex. +200; -1030; -0600)
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