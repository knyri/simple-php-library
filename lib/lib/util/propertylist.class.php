<?php
class PropertyList{
	protected $data=array();
	/**
	 * @param string $key
	 * @param mixed $value
	 */
	public function set($key,$value){
		$this->data[$key]=$value;
	}
	/**
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	public function get($key,$default=null){
		if(isset($this->data[$key]))
			return $this->data[$key];
		return $default;
	}
	/**
	 * @param string $key
	 */
	public function uset($key){
		if(isset($this->data[$key]))
			unset($this->data[$key]);
	}
	/**
	 * Copies the data held by the internal array to the given array.
	 * @param array $ary
	 * @return array The resulting array.
	 */
	public function copyTo(array $ary){
		foreach($this->data as $k=>$v)
			$ary[$k]=$v;
		return $ary;
	}
}