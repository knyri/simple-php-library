<?php
/**
 * @param array $data
 * @param mixed $idx1
 * @param int sort order; SORT_*
 * @param mixed $_ repeat param 1 and 2 for other columns
 * @return mixed
 */
function array_orderby()
{
	$args= func_get_args();
	$data= array_shift($args);
	foreach($args as $n => $field){
		if ($n % 2 == 0) {
			$args[$n]= array_column($data, $field);
		}
	}
	$args[]= &$data;
	call_user_func_array('array_multisort', $args);
	return array_pop($args);
}

class OrderedArray{
	private $ary= array();
	public function contains($v){
		$arr= $this->ary;
		// check for empty array
		$high= count($arr) - 1;
		if ($high === -1) return false;
		$low= 0;
		while ($low <= $high) {
			// compute middle index
			$mid= floor(($low + $high) / 2);
			// element found at mid
			if($arr[$mid] == $v) {
				return true;
			}
			if($v < $arr[$mid]) {
				// search the left side of the array
				$high= $mid -1;
			}else{
				// search the right side of the array
				$low= $mid + 1;
			}
		}

		// If we reach here element x doesnt exist
		return false;
	}
	public function getAry(){return $this->ary;}
	public function addUnique($v){
		if(!$this->contains($v)){
			$this->add($v);
		}
	}
	public function add($v){
		// 		logit("Added '$v'");
		if(empty($this->ary)){
			$this->ary[]= $v;
		}else{
			$i= floor(count($this->ary)/2);
			if($this->ary[$i] > $v){
				$i++;
				array_unshift($this->ary, $v);
				// shift down until something larger is found
				for($j= 1; $j < $i; $j++){
					if($this->ary[$j] > $v){
						break;
					}
					$this->ary[$j-1]= $this->ary[$j];
				}
				$this->ary[$j-1]= $v;
			}else{
				$i--;
				// shift up until something smaller is found
				for($j= count($this->ary); $j > $i; $j--){
					if($this->ary[$j-1] < $v){
						break;
					}
					$this->ary[$j]= $this->ary[$j-1];
				}
				$this->ary[$j]= $v;
			}
		}
		// 		var_export($this->ary);
	}
}
