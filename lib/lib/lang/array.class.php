<?php

class ArrayObj{
	private $arr;
	public function __construct($arr= array()){
		$this->arr= $arr;
	}
	/**
	 * Returns the internal array
	 * @return array The internal array
	 */
	public function getArray(){
		return $this->arr;
	}
	public function setArray(array $arr){
		if($arr){
			$this->arr= $arr;
		}
	}
	public function changeKeyCase($case= CASE_LOWER){
		$o= $this->arr;
		$this->arr= array_change_key_case($this->arr, $case);
		return $o;
	}
	/**
	 * Splits the array into chunks
	 * Does not modify the internal array
	 * @param int $size
	 * @param string $preserve_keys
	 * @return array An array containing the chunks
	 */
	public function chunk($size, $preserve_keys= false){
		return array_chunk($this->arr, $size, $preserve_keys);
	}
	public function column($column_key, $index_key= null){
		return array_column($this->arr, $column_key, $index_key);
	}
	/**
	 * Counts the number of times each value in the array occurs
	 * @return array
	 */
	public function countValues(){
		return array_count_values($this->arr);
	}
	public function filter($callback= null, $flag= 0){
		$o= $this->arr;
		$this->arr= array_filter($this->arr, $callback, $flag);
		return $o;
	}
	/**
	 * Flips the internal array
	 * @return array The original array
	 */
	public function flip(){
		$o= $this->arr;
		$this->arr= array_flip($this->arr);
		return $o;
	}
	/**
	 * array_key_exists
	 * @param mixed $k
	 * @return boolean
	 */
	public function hasKey($k){
		return array_key_exists($k, $this->arr);
	}
	/**
	 * array_search !== false
	 * @param mixed $v
	 * @param boolean $strict
	 * @return boolean
	 */
	public function hasValue($v, $strict= false){
		return array_search($v, $this->arr, $strict) !== false;
	}
	public function hasValues($arr, $strict= false){
		foreach($arr as $v){
			if(array_search($v, $this->arr, $strict) === false){
				return false;
			}
		}
		return true;
	}
	public function keys($search= null, $strict= false){
		return array_keys($this->arr, $search, $strict);
	}
	public function map($cb){
		$o= $this->arr;
		$this->arr= array_map($cb, $this->arr);
		return $o;
	}
	public function mergeRecursive($arr){
		$o= $this->arr;
		$this->arr= array_merge_recursive($this->arr, $arr);
		return $o;
	}
	public function merge($arr){
		$o= $this->arr;
		$this->arr= array_merge($this->arr, $arr);
		return $o;
	}
	public function pad($size, $value){
		$o= $this->arr;
		$this->arr= array_pad($this->arr, $size, $value);
		return $o;
	}
// -------------------------------------------------
	public function replaceAll($val, $with, $strict= false){
		if($strict){
			if($val === $with){
				return;
			}
		}else if($val == $with){
			return;
		}
		$idx= array_search($val, $this->arr, $strict);
		while($idx !== false){
			$this->arr[$idx]= $with;
			$idx= array_search($val, $this->arr, $strict);
		}
	}
	public function replace($val, $with, $strict= false){
		if($strict){
			if($val === $with){
				return;
			}
		}else if($val == $with){
			return;
		}
		$idx= array_search($val, $this->arr, $strict);
		if($idx !== false){
			$this->arr[$idx]= $with;
		}
	}
	/**
	 * For Hash Arrays
	 * Uses unset
	 * @param mixed $val
	 * @param boolean $strict
	 */
	public function removeAssoc($val, $strict= false){
		$idx= array_search($val, $this->arr, $strict);
		if($idx !== false){
			unset($this->arr[$idx]);
		}
	}
	/**
	 * For indexed arrays.
	 * Uses array_splice
	 * @param mixed $val
	 */
	public function remove($val, $strict= false){
		$idx= array_search($val, $this->arr, $strict);
		if($idx !== false){
			array_splice($this->arr, $idx, 1);
		}
	}
// -------------------------------------------------
	/**
	 * Removes and returns the last element
	 * @return mixed
	 */
	public function pop(){
		return array_pop($this->arr);
	}
	/**
	 * Does not call array_push. Uses $arr[]= $v;
	 * @param mixed $v
	 */
	public function push($v){
		$this->arr[]= $v;
	}
	/**
	 * Removes and returns the first element
	 * @return mixed
	 */
	public function shift(){
		return array_shift($this->arr);
	}
	/**
	 * Pushes an element to the beginning of the array
	 * @param mixed $v
	 * @return number The new size
	 */
	public function unshift($v){
		return array_unshift($this->arr, $v);
	}
// -------------------------------------------------
	public function product(){
		return array_product($this->arr);
	}
	public function sum(){
		return array_sum($this->arr);
	}
	public function rand($num= 1){
		return array_rand($this->arr, $num);
	}
	/**
	 * Reduces the array down to a single value
	 * @param callable $cb
	 * @param mixed $initial
	 * @return mixed The end value
	 */
	public function reduce($cb, $initial= null){
		return array_reduce($this->arr, $cb, $initial);
	}
	public function reverse($preserveKeys= false){
		$o= $this->arr;
		$this->arr= array_reverse($this->arr, $preserveKeys);
		return $o;
	}
	/**
	 * 
	 * @param mixed $for
	 * @param boolean $strict
	 * @return mixed
	 */
	public function search($for, $strict= false){
		return array_search($for, $this->arr, $strict);
	}
	/**
	 * Returns a subrange of the array.
	 * @param integer $offset
	 * @param integer $len
	 * @param boolean $preserveKeys
	 * @return array The subrange
	 */
	public function slice($offset= 0, $len= null, $preserveKeys= false){
		if($len == null){
			$len= count($this->arr);
		}
		return array_slice($this->arr, $offset, $len, $preserveKeys);
	}
	/**
	 * Removes and returns a subrange of the array
	 * @param integer $offset
	 * @param integer $len
	 * @param array $replacement
	 * @return array The subrange
	 */
	public function splice($offset, $len= null, $replacement= array()){
		if($len == null){
			$len= count($this->arr);
		}
		return array_splice($this->arr, $offset, $len, $replacement);
	}
	/**
	 * Removes duplicate values from the array.
	 * @param string $sort
	 * @return array The original array
	 */
	public function unique($sort= SORT_STRING){
		$o= $this->arr;
		$this->arr= array_unique($this->arr, $sort);
		return $o;
	}
	/**
	 * Returns an indexed array of all the values
	 * @return array
	 */
	public function values(){
		return array_values($this->arr);
	}
	/**
	 * Calls the function on very memeber of the array
	 * @param callable $cb
	 * @param mixed $userData
	 * @return boolean
	 * @see array_walk
	 */
	public function walk($cb, $userData= null){
		return array_walk($this->arr, $cb, $userData);
	}
	/**
	 * Calls the function on very memeber of the array
	 * @param callable $cb
	 * @param mixed $userData
	 * @return boolean
	 * @see array_walk_recursive
	 */
	public function walkRecursive($cb, $userData= null){
		return array_walk_recursive($this->arr, $cb, $userData);
	}
	/**
	 * Number of elements in the array
	 * @return number
	 */
	public function count(){
		return count($this->arr);
	}
// -------------------------------------------------------
	/**
	 * Returns the value at current pointer positions
	 * @return mixed The current value
	 */
	public function current(){
		return current($this->arr);
	}
	/**
	 * Moves the pointer to the end of the array and returns the value.
	 * @return mixed The last value in the array
	 */
	public function end(){
		return end($this->arr);
	}
	/**
	 * Moves the pointer to forward and returns the value.
	 * @return mixed The next value or false
	 */
	public function next(){
		return next($this->arr);
	}
	/**
	 * Moves the pointer back and returns the value.
	 * @return mixed The previous value or false
	 */
	public function prev(){
		return prev($this->arr);
	}
	/**
	 * Moves the pointer to the beginning of the array and returns the value.
	 * @return mixed The first value in the array
	 */
	 public function first(){
		return reset($this->arr);
	}
	/**
	 * Returns the key for the current pointer location
	 * @return mixed
	 */
	public function key(){
		return key($this->arr);
	}
// --------------------------------------------------------
	public function contains($v, $strict= false, $fromEnd= false){
		if($fromEnd){
			return in_array_r($v, $this->arr, $strict);
		}else{
			return in_array($v, $this->arr, $strict);
		}
	}
// ---------------------------------------------------------------
	public function keySort($reverse= false, $sort= SORT_REGULAR){
		if($reverse){
			return krsort($this->arr, $sort);
		}else{
			return ksort($this->arr, $sort);
		}
	}
	/**
	 * Natural sort
	 * @param boolean $caseSensitive
	 * @return boolean
	 * @see natsort and natcasesort
	 */
	public function naturalSort($caseSensitive= true){
		if($caseSensitive){
			return natcasesort($this->arr);
		}else{
			return natsort($this->arr);
		}
	}
	/**
	 * Sorts and replaces the internal array.
	 * @param string $sort
	 * @param boolean $reverse
	 * @return array The original internal array
	 * @see sort and rsort 
	 */
	public function sort($sort= SORT_REGULAR, $reverse= false){
		$o= $this->arr;
		if($reverse){
			$this->arr= rsort($this->arr, $sort);
		}else{
			$this->arr= sort($this->arr, $sort);
		}
		return $o;
	}
	/**
	 * Sort by values, keeping the key => value association.
	 * Replaces the internal array.
	 * @param string $sort
	 * @param boolean $reverse
	 * @return array The old internal array
	 * @see arsort and asort
	 */
	public function sortAssoc($sort= SORT_REGULAR, $reverse= false){
		$o= $this->arr;
		if($reverse){
			$this->arr= arsort($this->arr, $sort);
		}else{
			$this->arr= asort($this->arr, $sort);
		}
		return $o;
	}
	/**
	 * Sort by values, keeping the key => value association, using the callback
	 * Modifies the internal array.
	 * @param callable $cb
	 * @return boolean
	 * @see uasort
	 */
	public function userSortAssoc($cb){
		return uasort($this->arr, $cb);
	}
	/**
	 * Sort by value using the callback.
	 * Modifies the internal array.
	 * @param callable $cb
	 * @return boolean
	 * @see usort
	 */
	public function userSort($cb){
		return usort($this->arr, $cb);
	}
	/**
	 * Sort by keys using the callback.
	 * Modifies the internal array.
	 * @param callable $cb
	 * @return boolean
	 * @see uksort
	 */
	public function userKeySort($cb){
		return uksort($this->arr, $cb);
	}
// ---------------------------------------------------------------
	public function shuffle(){
		return shuffle($this->arr);
	}
}

