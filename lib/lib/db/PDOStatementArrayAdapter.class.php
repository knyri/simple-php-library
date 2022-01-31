<?php

class PDOStatementArrayAdapter extends PDOStatement{
	private $rows, $idx= 0;
	public function __construct(array $rows){
		$this->rows= $rows;
	}
	/**
	 * fetch_style is ignored
	 * {@inheritDoc}
	 * @see PDOStatement::fetch()
	 */
	public function fetch($style= null, $orient= PDO::FETCH_ORI_NEXT, $offset= 0){
		$idx= 0;
		switch($orient){
			case PDO::FETCH_ORI_NEXT:
				$idx= $this->idx + 1;
			break;
			case PDO::FETCH_ORI_ABS:
				$idx= $offset;
				break;
			case PDO::FETCH_ORI_FIRST:
				$idx= 0;
				break;
			case PDO::FETCH_ORI_LAST:
				$idx= count($this->rows) - 1;
				break;
			case PDO::FETCH_ORI_PRIOR:
				$idx= $this->idx - 1;
				break;
			case PDO::FETCH_ORI_REL:
				$idx= $this->idx + $offset;
				break;
			default:
				throw new PDOException("Unknown cursor orientation");
		}
		if(array_key_exists($idx, $this->rows)){
			$this->idx= $idx;
			return array_merge(array(), $this->rows[$idx]);
		}
		return false;
	}
	/**
	 * All args are ignored
	 * {@inheritDoc}
	 * @see PDOStatement::fetchAll()
	 */
	public function fetchAll($style= null, $args= null, $ctr_args= null){
		return array_map(function($arr){return array_merge(array(), $arr);});
	}
	public function fetchColumn($num= 0){
		if($row= $this->fetch()){
			if(array_key_exists($num, $row)){
				return $row[$num];
			}else{
				// Closest approximation I can get to adapting associative to indexed arrays
				$keys= array_keys($row);
				if(array_key_exists($num, $keys)){
					return $row[$keys[$num]];
				}
			}
		}
		return false;
	}
	/** Only supports stdClass
	 * {@inheritDoc}
	 * @see PDOStatement::fetchObject()
	 */
	public function fetchObject($className= "stdClass", array $args= null){
		if($className != "stdClass"){
			throw new PDOException("Not supported");
		}
		$row= $this->fetch();
		return $row === false ? false : (object) $row;
	}
	public function rowCount(){
		return count($this->rows);
	}
	public function getAttribute($attribute){
		throw new PDOException("Not supported");
	}
	public function getColumnMeta($column){
		throw new PDOException("Not supported");
	}
	public function nextRowset(){
		return false;
	}
	public function setAttribute($attribute, $value){
		throw new PDOException("Not supported");
	}
	public function setFetchMode($mode){
		throw new PDOException("Not supported");
	}
	public function bindColumn($column, $param, $a1= null, $a2= null, $a3= null){
		throw new PDOException("Not supported");
	}
	public function bindParam($column, $param, $a1= null, $a2= null, $a3= null){
		throw new PDOException("Not supported");
	}
	public function bindValue($column, $param, $a1= null){
		throw new PDOException("Not supported");
	}
	public function closeCursor(){
		return true;
	}
	public function columnCount(){
		if(count($this->rows)){
			return count($this->rows[0]);
		}
		return 0;
	}
	public function execute($params= null){
		throw new PDOException("Not supported");
	}
}