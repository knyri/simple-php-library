<?php
PackageManager::requireClassOnce('util.propertylist');

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
	public function __construct($stm,$fetch_mode= PDO::FETCH_ASSOC){
		if(!$stm instanceof PDOStatement){
			throw new IllegalArgumentException('$stm is not a PDOStatement');
		}
		$this->dataset= $stm;
		$this->dataset->setFetchMode($fetch_mode);
	}
	/**
	 * See PDOStatement::bindParam() {http://www.php.net/manual/en/pdostatement.bindparam.php}
	 * @param string|int $key
	 * @param mixed $value
	 * @param int $type
	 */
	public function bindParam($key, &$value, $type){
		$this->dataset->bindParam($key, $value, $type);
	}
	/**
	 * See PDOStatement::bindValue() {http://www.php.net/manual/en/pdostatement.bindvalue.php}
	 * @param string|int $key
	 * @param mixed $value
	 * @param int $type
	 */
	public function bindValue($key, $value, $type){
		$this->dataset->bindValue($key, $value, $type);
	}
	/**
	 * Closes the cursor and runs the statement.
	 * @param array $args (null) The arguements for the statement
	 * @return boolean true on success, false on failure
	 */
	public function run(array $args= null){
		$this->dataset->closeCursor();
		return !db_run_query($this->dataset, $args);
	}
	/**
	 * Loads the next row found by this query.
	 * @throws IllegalStateException
	 * @return boolean false if the fetch failed or if the end was reached
	 */
	public function loadNext(){
		if(!$this->dataset){
			throw new IllegalStateException('Query was not ran or query failed.');
		}
		$row=$this->dataset->fetch();
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
