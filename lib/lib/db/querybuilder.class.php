<?php


class QueryBuilder {
	private
		$fields= array(),
		$baseTable,
		$tables= array(),
		$where,
		$orderBy= '',
		$groupBy= array(),
		$joinArgs= array(),
		$query= null,
		$runArgs,
		$limit= false,
		$offset= false,
		$lastError= false;
	/**
	 * @param PDO $db
	 * @param string $table
	 */
	public function __construct($table){
		$this->baseTable= $table;
		$this->where= new WhereBuilder();
	}
	public function setLimit($limit){
		$this->limit= $limit;
		return $this;
	}
	public function setOffset($offset){
		$this->offset= $offset;
		return $this;
	}
	/**
	 * @param string $name
	 * @return QueryBuilder
	 */
	public function addField($name){//, $type= PDO::PARAM_STR){
		$this->fields[]= $name;//array($name, $type);
		return $this;
	}
	public function orderBy($field, $asc){
		$this->orderBy.= ',' . $field . ' ' . ($asc ? 'ASC' : 'DESC');
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
	public function andNot(WhereBuilder $where){
		$this->where->andNot($where);
		return $this;
	}
	public function orNot(WhereBuilder $where){
		$this->where->orNot($where);
		return $this;
	}

	/**
	 * Builds the query
	 * @return QueryBuilder
	 */
	public function build(){
		$this->runArgs= array_merge(array(), $this->joinArgs, $this->where->getValues());
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
				$this->runArgs= array_merge($this->runArgs, $table[2]->getValues());
			}else{
				$from.= $table[2];
			}
			$from.= ')';
		}
		$orderBy= '';
		if(strlen($this->orderBy)){
			$orderBy= ' ORDER BY ' . substr($this->orderBy, 1);
		}
		$this->query=
			'SELECT '.
				implode(',', $this->fields) .
			" FROM $from $filter ".
			( count($this->groupBy) ? ' GROUP BY '. implode(',', $this->groupBy) : '' ) .
			$orderBy .
			($this->limit ? ' LIMIT '. $this->limit : '') .
			($this->offset ? ' OFFSET '. $this->offset : '');
		return $this;
	}
	public function getLastError(){
		return $this->lastError;
	}
	public function resetWhere(){
		$this->where->reset();
		return $this;
	}
	public function getParams(){
		return $this->runArgs;
	}
	public function getQuery(){
		return $this->query;
	}
	/**
	 * Makes a PDOStatementWrapper from the query.
	 * @param PDO $db
	 * @param unknown $fetchMode
	 * @return PDOStatementWrapper
	 */
	public function getStatement(PDO $db, $fetchMode= PDO::FETCH_ASSOC){
		$stm= new PDOStatementWrapper(db_prepare($db, $this->query), $fetchMode);
		$stm->bindAllValues($this->runArgs);
		return $stm;
	}
	/**
	 *
	 * @param PDO $db
	 * @param int $fetchMode (PDO::FETCH_ASSOC)
	 * @return boolean|PDOStatementWrapper
	 */
	public function run(PDO $db, $fetchMode= PDO::FETCH_ASSOC){
		$stm= new PDOStatementWrapper(db_prepare($db, $this->query), $fetchMode);
		if(!$stm->run($this->runArgs)){
			$this->lastError= $stm->getError();
			$this->lastError[]= $this->query;
			$this->lastError[]= $this->where->getValues();
		}else{
			$this->lastError= false;
		}
		return $stm->run($this->runArgs) ? $stm : false;
	}
}