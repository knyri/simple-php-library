<?php
class PDOStatementIterator extends PDOStatement {
	private
		$fetchMode,
		$dataset,
		$datasets= array()
	;
	public function __construct($fetchMode= PDO::FETCH_ASSOC){
		$this->fetchMode= $fetchMode;
	}
	public function getCurrentStatement(){
		return $this->dataset;
	}
	public function bindColumn($column, &$param, $type= null, $maxlen= null, $driverdata= null){
		throw new PDOException("Not supported");
	}
	public function bindParam($key, &$variable, $type= PDO::PARAM_STR, $length= null, $driver_options= null ){
		throw new PDOException("Not supported");
	}
	public function bindValue($key, $value, $type= PDO::PARAM_STR){
		throw new PDOException("Not supported");
	}
	public function closeCursor(){
		if($this->dataset){
			$this->dataset->closeCursor();
			$this->dataset= null;
		}
		while($dataset= array_pop($this->datasets)){
			$dataset->closeCursor();
		}
		return true;
	}
	public function columnCount(){
		return $this->dataset ? $this->dataset->columnCount() : 0;
	}
	public function errorCode(){
		return $this->dataset ? $this->dataset->errorCode() : 0;
	}
	public function errorInfo(){
		return $this->dataset ? $this->dataset->errorInfo() : array();
	}
	private function nextDataset(){
		$this->dataset->closeCursor();
		$this->dataset= array_shift($this->datasets);
		if($this->dataset){
			$this->dataset->setFetchMode($this->fetchMode);
		}
		return $this->dataset != null;
	}
	public function fetchAll($fetchStyle= null, $fetchArgument= null, $ctorArgs= array()){
		if(!$fetchStyle){
			$fetchStyle= $this->fetchMode;
		}
		$allRows= array();
		while($this->dataset){
			$rows= $this->dataset->fetchAll($fetchStyle, $fetchArgument, $ctorArgs);
			if($rows === false){
				return false;
			}
			$allRows= array_merge($allRows, $rows);
			$this->nextDataset();
		}
		return $allRows;
	}
	public function fetch($fetchStyle= null, $cursorOrientation= PDO::FETCH_ORI_NEXT, $cursorOffset= 0){
		if(!$this->dataset){
			return null;
		}
		while(! ($row= $this->dataset->fetch($fetchStyle, $cursorOrientation, $cursorOffset)) ){
			if($this->dataset->errorCode() != '00000'){
				return false;
			}
			if(!$this->nextDataset()){
				return null;
			}
		}
		return $row;
	}
	public function execute($params= null){
		throw new PDOException("Not supported");
	}
	public function fetchColumn($columnNumber= 0){
		return $this->dataset ? $this->dataset->fetchColumn($columnNumber) : null;
	}
	public function fetchObject($className= 'stdObj', $ctorArgs= array()){
		if(!$this->dataset){
			return null;
		}
		while(!( $row= $this->dataset->fetchObject($className, $ctorArgs) )){
			if($row === false){
				return false;
			}
			if(!$this->nextDataset()){
				return null;
			}
		}
		return $row;
	}
	public function getAttribute($attribute){
		return $this->dataset ? $this->dataset->getAttribute($attribute) : null;
	}
	public function getColumnMeta($column){
		return $this->dataset ? $this->dataset->getColumnMeta($column) : null;
	}
	public function nextRowset(){
		if(!$this->dataset){
			return false;
		}
		if(!$this->dataset->nextRowset()){
			return $this->nextDataset();
		}
		return true;
	}
	public function rowCount(){
		return $this->dataset ? $this->dataset->rowCount() : 0;
	}
	public function setAttribute($attribute, $value){
		throw new PDOException("Not supported");
	}
	public function setFetchMode($mode, $params= null){
		if($this->dataset){
			$this->dataset->setFetchMode($mode);
		}
		$this->fetchMode= $mode;
	}
	public function debugDumpParams(){
		if($this->dataset){
			$this->dataset->debugDumpParams();
		}
	}

	/**
	 * Adds a statement to the end of the fetch queue
	 * @param PDOStatement $stm
	 */
	public function addStatement(PDOStatement $stm){
		if($stm == null){
			return;
		}
		$stm->setFetchMode($this->fetchMode);
		if($this->dataset == null){
			$this->dataset= $stm;
		}else{
			$this->datasets[]= $stm;
		}
	}

}