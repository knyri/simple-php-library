<?php


class QueryBuilder {
	private
		$fields= array(),
		$baseTable,
		$tables= array(),
		$where,
		$userFilter,
		$orderBy= '',
		$groupBy= array(),
		$joinArgs= array(),
		$query= null,
		$runArgs,
		$limit= false,
		$offset= FALSE;
	/**
	 * @param PDO $db
	 * @param string $table
	 */
	public function __construct($table){
		$this->baseTable= $table;
		$this->where= new WhereBuilder();
		$this->userFilter= new WhereBuilder('user');
	}
	public function setLimit($limit){
		$this->limit= $limit;
	}
	public function setOffset($offset){
		$this->offset= $offset;
	}
	/**
	 * @param string $name
	 * @return QueryBuilder
	 */
	public function addField($name){//, $type= PDO::PARAM_STR){
		$this->fields[]= $name;//array($name, $type);
		return $this;
	}
	/**
	 * Adds the parameters to the param list
	 * @param WhereBuilder $where
	 * @return QueryBuilder
	 */
	public function addParams(WhereBuilder $where){
		$this->joinArgs= array_merge($this->joinArgs, $where->getValues());
		return $this;
	}
	/**
	 * Takes a list of strings.
	 * @return QueryBuilder
	 */
	public function groupBy(){
		foreach(func_get_args() as $col){
			$this->groupBy[]= $col;
		}
		return $this;
	}
	/**
	 *
	 * @param string $table
	 * @param string|WhereBuilder $on
	 * @return QueryBuilder
	 */
	public function leftJoin($table, $on){
		$this->tables[]= array('left',$table, $on);
		return $this;
	}
	/**
	 *
	 * @param string $table
	 * @param string|WhereBuilder $on
	 * @return QueryBuilder
	 */
	public function join($table, $on){
		$this->tables[]= array('',$table, $on);
		return $this;
	}
	/**
	 *
	 * @param string $table
	 * @param string|WhereBuilder $on
	 * @return QueryBuilder
	 */
	public function rightJoin($table, $on){
		$this->tables[]= array('right',$table, $on);
		return $this;
	}
	/**
	 *
	 * @param string $table
	 * @param string|WhereBuilder $on
	 * @return QueryBuilder
	 */
	public function innerJoin($table, $on){
		$this->tables[]= array('inner',$table, $on);
		return $this;
	}
	/**
	 *
	 * @param string $table
	 * @param string|WhereBuilder $on
	 * @return QueryBuilder
	 */
	public function outerJoin($table, $on){
		$this->tables[]= array('outer',$table, $on);
		return $this;
	}
	/**
	 * Passthrough to the underlying WhereBuilder
	 * @param string $literal
	 * @param boolean $wrap
	 * @return QueryBuilder
	 */
	public function andLiteral($literal, $wrap=false) {
		$this->where->andLiteral($literal, $wrap);
		return $this;
	}
	/**
	 * Passthrough to the underlying WhereBuilder
	 * @param string $literal
	 * @param boolean $wrap
	 * @return QueryBuilder
	 */
	public function orLiteral($literal, $wrap=false) {
		$this->where->orLiteral($literal, $wrap);
		return $this;
	}
	/**
	 * Passthrough to the underlying WhereBuilder
	 * @return QueryBuilder
	 * @see WhereBuilder::andWhere()
	 */
	public function andWhere(){
		call_user_func_array(array($this->where, 'andWhere'), func_get_args());
		return $this;
	}
	/**
	 * Passthrough to the underlying WhereBuilder
	 * @return QueryBuilder
	 * @see WhereBuilder::orWhere()
	 */
	public function orWhere(){
		call_user_func_array(array($this->where, 'orWhere'), func_get_args());
		return $this;
	}
	/**
	 * Builds the query
	 */
	private function build(){
		if($this->query){
			return;
		}
		$filterValues= $this->where->getValues();
		$filter= $this->where->getWhere() ? 'WHERE ('. $this->where->getWhere() . ')' : '';
		$from= $this->baseTable;
		foreach ($this->tables as $table){
			/*
			 * table elements:
			 * 0 - join type
			 * 1 - table name
			 * 2 - join condition (WhereBuilder or string literal)
			 */
			$from.= ' '. $table[0] .' JOIN '. $table[1] .' ON (';
			if($table[2] instanceof WhereBuilder){
				$from.= $table[2]->getWhere();
				$this->joinArgs= array_merge($this->joinArgs, $table[2]->getValues());
			}else{
				$from.= $table[2];
			}
			$from.= ')';
			$filterValues= array_merge($filterValues, $this->joinArgs);
		}
		$this->runArgs= $filterValues;
		$this->query=
			'SELECT '.
				implode(',', $this->fields) .
			" FROM $from $filter ".
			( count($this->groupBy) ? ' GROUP BY '. implode(',', $this->groupBy) : '' ) .
			$this->orderBy .
			($this->limit ? ' LIMIT '. $this->limit : '') .
			($this->offset ? ' OFFSET '. $this->offset : '');
	}
	/**
	 *
	 * @param PDO $db
	 * @param int $fetchMode (PDO::FETCH_ASSOC)
	 * @return boolean|PDOStatementWrapper
	 */
	public function run(PDO $db, $fetchMode= PDO::FETCH_ASSOC){
		$stm= new PDOStatementWrapper(db_prepare($db, $this->query), $fetchMode);
		return $stm->run($this->runArgs) ? $stm : false;
	}
}