<?php
/**
 * @package util
 */

/**
 *
 * @author Ken
 */
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
		if(array_key_exists($key, $this->data)){
			return $this->data[$key];
		}
		return $default;
	}
	/**
	 * @param string $key
	 */
	public function uset($key){
		unset($this->data[$key]);
	}
	/**
	 * Copies the data held by the internal array to the given array.
	 * @param array $ary
	 * @return array The resulting array.
	 */
	public function copyTo(array $ary){
		return array_replace($ary, $this->data);
	}
	/**
	 * @param array $list $key=>$value list
	 */
	public function setAll(array $list){
		$this->data=array_merge($this->data,$list);
	}
	public function clear(){
		$this->data=array();
	}
	/**
	 * Sets the internal array to the supplied array.
	 * @param array $a
	 */
	public function initFrom(array $a){
		$this->data=$a;
	}
	public function count(){
		return count($this->data);
	}
	/**
	 * Shortcut for $this->copyTo(array())
	 * @return array
	 */
	public function asArray(){
		return $this->copyTo(array());
	}
}
/**
 * PropertyList that can track the changes
 * @author Ken
 */
class ChangeTrackingPropertyList extends PropertyList{
	protected
		$changes= array(),
		$cleared= array();
	public function getChanges(){
		$changes=array('old'=>array(),'new'=>array());
		foreach($this->changes as $k=>$v){
			$changes['new'][$k]=$v;
			$changes['old'][$k]=array_key_exists($k,$this->data)?$this->data[$k]:null;
		}
		foreach($this->cleared as $k=>$v){
			$changes['new'][$k]=null;
			$changes['old'][$k]=array_key_exists($k,$this->data)?$this->data[$k]:null;
		}
		return $changes;
	}
	public function count(){
		return count($this->getFinal());
	}
	public function initFrom(array $a){
		$this->reset();
		parent::initFrom($a);
	}
	public function reset(){
		$this->changes=array();
		$this->data=array();
		$this->cleared=array();
	}
	public function clear(){
		foreach($this->data as $k => $v){
			$this->cleared[$k]=true;
	}
	}
	/**
	 * @param string $k
	 * @param mixed $v
	 */
	public function set($k,$v){
		if(array_key_exists($k,$this->data) && $this->data[$k]==$v){
			unset($this->changes[$k]);
			return;
		}
		$this->changes[$k]=$v;
		unset($this->cleared[$k]);
	}
	public function discardChanges(){
		$this->changes=array();
		$this->cleared=array();
	}
	public function mergeChanges(){
		$this->data=$this->getFinal();
		$this->discardChanges();
	}
	/**
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	public function get($key,$default=null){
		if(array_key_exists($key, $this->changes)){
			return $this->changes[$key];
		}
		if(array_key_exists($key, $this->data) && !isset($this->cleared[$key])){
			return $this->data[$key];
		}
		return $default;
	}
	/**
	 * Gets the previous value for the key.
	 * @param string $key
	 * @param string $default
	 * @return mixed
	 */
	public function getPrevious($key,$default=null){
		if(array_key_exists($key,$this->data)){
			return $this->data[$key];
		}
		return $default;
	}
	/**
	 * @param string $k
	 */
	public function uset($k){
		unset($this->changes[$k]);
// 		unset($this->data[$k]);
		$this->cleared[$k]=true;
	}
	/**
	 * Copies the data held by the internal array to the given array.
	 * @param array $ary
	 * @return array The resulting array.
	 */
	public function copyTo(array $ary){
		return array_replace($ary, $this->getFinal());
	}
	protected function getFinal(){
		return array_diff_key(array_merge($this->data,$this->changes),$this->cleared);
	}
	/**
	 * @param array $list $key=>$value list
	 */
	public function setAll(array $list){
		$this->changes=array_merge($this->changes,$list);
	}
}