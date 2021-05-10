<?php
class Number{
	private $number;
	public function __construct($number= 0, $base= 10){
		if(!is_numeric($number)){
			throw new IllegalArgumentException('Number must be a number or numeric string');
		}
		if(is_string($number)){
			$tmp= intval($number, $base);
			if($tmp != $number){
				$this->number= floatval($number);
			}else{
				$this->number= $tmp;
			}
		}else{
			$this->number= $number;
		}
	}
	/**
	 * Exclusive (start, end)
	 * @param number $start
	 * @param number $end
	 * @return boolean
	 */
	public function between($start, $end){
		return $this->number > $start && $this->number < $end;
	}
	/**
	 * Inclusive [start, end]
	 * @param number $start
	 * @param number $end
	 * @return boolean
	 */
	public function inRange($start, $end){
		return $this->number >= $start && $this->number <= $end;
	}
}
