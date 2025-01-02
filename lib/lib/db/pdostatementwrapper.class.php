<?php
PackageManager::requireClassOnce('util.propertylist');
require_once 'PDOBindException.class.php';

/**
 * Convenience wrapper for <var>PDOStatement</var>s
 * @author Ken
 */
class PDOStatementWrapper extends PropertyList{
	protected $dataset=null;
	/**
	 * @param PDOStatement $stm
	 * @param int $fetch_mode (PDO::FETCH_ASSOC)
	 * @throws IllegalArgumentException
	 */
	public function __construct($stm, $fetch_mode= PDO::FETCH_ASSOC){
		if(!$stm instanceof PDOStatement){
			throw new IllegalArgumentException('$stm is not a PDOStatement');
		}
		$this->dataset= $stm;
		$this->dataset->setFetchMode($fetch_mode);
	}
	public function setFetchMode($mode){
		$this->dataset->setFetchMode($mode);
	}
	public function getStatement(){
		return $this->dataset;
	}
	/**
	 * Binds the value to the variable
	 * See PDOStatement::bindParam() {http://www.php.net/manual/en/pdostatement.bindparam.php}
	 * @param string|int $key
	 * @param mixed $variable
	 * @param int $type
	 * @param int $length
	 * @param mixed $driver_options
	 * @return PDOStatementWrapper
	 * @throws PDOBindException If the bind fails
	 */
	public function bindParam($key, &$variable, $type= PDO::PARAM_STR, $length= null, $driver_options= null ){
		if(!$this->dataset->bindParam($key, $variable, $type, $length, $driver_options)){
			throw new PDOBindException("Binding [$key] to [$variable] failed");
		}
		return $this;
	}
	/**
	 * See PDOStatement::bindValue() {http://www.php.net/manual/en/pdostatement.bindvalue.php}
	 * @param string|int $key
	 * @param mixed $value
	 * @param int $type
	 * @return PDOStatementWrapper
	 * @throws PDOBindException If the bind fails
	 */
	public function bindValue($key, $value, $type= PDO::PARAM_STR){
		if(!$this->dataset->bindValue($key, $value, $type)){
			throw new PDOBindException("Binding [$key] to [$value] failed");
		}
		return $this;
	}
	/**
	 * Binds the keys to the array values using bindParam($key, $params[$key]);
	 * Updating a key's value should update it for the statement.
	 *
	 * @param array $params
	 * @return PDOStatementWrapper
	 * @throws PDOBindException If the bind fails
	 */
	public function bindAllParam(array $params){
		foreach($params as $key => $value){
			if(!$this->dataset->bindParam($key, $params[$key])){
				throw new PDOBindException("Binding [$key] to [$value] failed");
			}
		}
		return $this;
	}
	/**
	 * @param array $params
	 * @return PDOStatementWrapper
	 * @throws PDOBindException If the bind fails
	 */
	public function bindAllValues(array $params){
		foreach($params as $key => $value){
			if(!$this->dataset->bindValue($key, $value)){
				throw new PDOBindException("Binding [$key] to [$value] failed");
			}
		}
		return $this;
	}
	public function bindColumn($column, &$param, $type= null, $maxlen= null, $driverdata= null){
		if(!$this->dataset->bindColumn($column, $param, $type, $maxlen, $driverdata)){
			throw new PDOBindException("Binding [$column] to [$param] failed");
		}
		return $this;
	}
	/**
	 * Closes the cursor and runs the statement.
	 * @param array $args (null) The arguements for the statement
	 * @return boolean true on success, false on failure
	 */
	public function run(array $args= null){
		$this->dataset->closeCursor();
		return db_run_query($this->dataset, $args);
	}
	/**
	 * Loads the next row found by this query.
	 * @throws IllegalStateException
	 * @return boolean false if the fetch failed or if the end was reached
	 */
	public function loadNext(){
		$row= $this->dataset->fetch();
		$this->initFrom($row ? $row : array());
		return $row != false;
	}
	/**
	 * Closes the cursor and clears the loaded data.
	 */
	public function recycle(){
		if($this->dataset){
			$this->dataset->closeCursor();
		}
		$this->clear();
	}
	public function getError(){
		return $this->dataset->errorInfo();
	}
}
