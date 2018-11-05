<?php
/**
 * For building WHERE clauses.
 * @author Ken
 *
 */
class WhereBuilder{
	private static $instanceCnt= 0;
	private
		$where= '',
		$values= array(),
		$prefix,
		$curIdx= 0;
	public function __construct($prefix='wh'){
		$this->prefix= ":$prefix" . (++self::$instanceCnt) . '_';
	}
	public function isEmpty(){
		return $this->curIdx == 0;
	}
	public function reset(){
		$this->where= '';
		$this->values= array();
		$this->curIdx= 0;
	}
	public function &addParam($name, $value){
		$this->values[$name]= $value;
		return $this;
	}
	/**
	 * Appends a finished WhereBuilder to this
	 * @param string $andor 'and' or 'or;
	 * @param WhereBuilder $where The conditon to be appended
	 * @param boolean $parens (false) Wrap the appended WHERE conditions in parenthesis
	 * @return WhereBuilder $this for chaining
	 */
	public function &appendWhere($andor, WhereBuilder $where, $parens= false){
		if(!$where->where){
			return $this;
		}
		$this->values= array_merge($this->values, $where->getValues());
		if($this->curIdx == 0){
			$andor= '';
			$this->curIdx++;
		}
		if($parens){
			$this->where.= " $andor (". $where->where. ')';
		}else{
			$this->where.= " $andor ". $where->where;
		}
		return $this;
	}
	public function &andLiteral($literal, $wrap= false) {
		if($this->curIdx != 0){
			$this->where.= ' AND ';
		}
		if($wrap){
			$this->where.= '('. $literal .')';
		}else{
			$this->where.= $literal;
		}
		$this->curIdx++;
		return $this;
	}
	public function &orLiteral($literal, $wrap= false) {
		if($this->curIdx != 0){
			$this->where.= ' OR ';
		}
		if($wrap){
			$this->where.= '('. $literal .')';
		}else{
			$this->where.= $literal;
		}
		$this->curIdx++;
		return $this;
	}
	public function &andNot(WhereBuilder $where){
		return $this->appendWhere('AND NOT', $where, true);
	}
	public function &orNot(WhereBuilder $where){
		return $this->appendWhere('OR NOT', $where, true);
	}
	/**
	 * Accepts multiple arguments.
	 * (col, NULL[, negate])
	 * (col,'in',vals[,negate])
	 * (col,'like',val[,negate])
	 * (col,'between',start,end[,negate])
	 * (col,comparator,val)
	 * comparator: '=' | '!=' | '>' | '<' | '>=' | '<='
	 * @return WhereBuilder A reference to itself for chaining.
	 */
	public function &andWhere(){
		$args= func_get_args();
		if(count($args) == 1 && $args[0] instanceof WhereBuilder){
			return $this->appendWhere('AND', $args[0], true);
		}
		if($this->curIdx != 0){
			$this->where.= ' AND ';
		}

		$this->addCondition($args);

		$this->curIdx++;
		return $this;
	}
	/**
	 * Accepts multiple arguments.
	 * (col, NULL[, negate])
	 * (col,'in',vals[,negate])
	 * (col,'like',val[,negate])
	 * (col,'between',start,end[,negate])
	 * (col,comparator,val)
	 * comparator: '=' | '!=' | '>' | '<' | '>=' | '<='
	 * @return WhereBuilder A reference to itself for chaining.
	 */
	public function &orWhere(){
		$args= func_get_args();
		if(count($args) == 1 && $args[0] instanceof WhereBuilder){
			return $this->appendWhere('OR', $args[0], true);
		}
		if($this->curIdx != 0){
			$this->where.= ' OR ';
		}

		$this->addCondition($args);

		$this->curIdx++;
		return $this;
	}
	private function addCondition($args){
		$argCount= count($args);
		if($argCount < 3){
			if($args[1] !== null){
				throw new ErrorException('At least 3 parameters are expected.');
			}
			$this->where.= ' '. $args[0] .' IS NULL';
			return;
		}
		if($args[1] === null){
			if($args[2]){
				$this->where.= ' '. $args[0] .' IS NOT NULL';
			}else{
				$this->where.= ' '. $args[0] .' IS NULL';
			}
			return;
		}

		switch($args[1]){
			case 'in':
// 				$this->values[$this->prefix.$this->curIdx]= $args[2];
				if(is_array($args[2])){
					$intmp= '';
					foreach($args[2] as $value){
						$this->values[$this->prefix . (++$this->curIdx)]= $value;
						$intmp.= ',' . $this->prefix . $this->curIdx;
					}
					$intmp= substr($intmp, 1);
				}else{
					$this->values[$this->prefix . $this->curIdx]= $args[2];
					$intmp= $this->prefix . $this->curIdx;
				}
				if($argCount == 4 && $args[3] == true){
					$this->where.= "$args[0] NOT IN ( $intmp )";
				} else {
					$this->where.= "$args[0] IN ( $intmp )";
				}
				break;
			case 'like':
				$this->values[$this->prefix . $this->curIdx]= $args[2];
				if($argCount == 4 && $args[3] == true){
					$this->where.= "$args[0] NOT LIKE ". $this->prefix . $this->curIdx;
				} else {
					$this->where.= "$args[0] LIKE ". $this->prefix . $this->curIdx;
				}
				break;
			case 'between':
				$this->values[$this->prefix . ($this->curIdx++)]= $args[2];
				$this->values[$this->prefix . ($this->curIdx)]  = $args[3];
				if($argCount == 5 && $args[4] == true){
					$this->where.= $args[0] .' NOT BETWEEN '. $this->prefix .($this->curIdx - 1) .' AND '. $this->prefix . $this->curIdx;
				} else {
					$this->where.= $args[0] .' BETWEEN '. $this->prefix .($this->curIdx - 1) .' AND '. $this->prefix . $this->curIdx;
				}

				break;
			default:
				$this->values[$this->prefix . $this->curIdx]= $args[2];
				$this->where.= "$args[0]$args[1]" . $this->prefix . $this->curIdx;
				//echo $this->prefix .$this->curIdx .'====='. $this->values[$this->prefix .$this->curIdx].PHP_EOL;
		}
	}
	/**
	 * The current WHERE clause.
	 * (note: does not start with 'WHERE')
	 * @return string
	 */
	public function getWhere(){
		return $this->where;
	}
	/**
	 * The where clause with the WHERE keyword and with leading and trailing spaces or a blank string if there is nothing
	 * @return string
	 */
	public function toString(){
		if(!$this->isEmpty()){
			return ' WHERE ' .$this->where . ' ';
		}else{
			return '';
		}
	}
	/**
	 * @return array Values to be substituted
	 */
	public function getValues(){
		return $this->values;
	}
}
