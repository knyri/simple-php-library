<?php
PackageManager::requireClassOnce('util.propertylist');
require_once 'wherebuilder.class.php';
require_once 'pdo_database.functions.php';
/**
 * Class for working with a PDO table.
 * @author Ken
 *
 */
class PDOTable{
	protected
		$table,
		$columns,
		$customTypeColumns= array(),
		$customTypes= array(),
		$data,
		$dataset=null,
		$pkey=null,
		$db=null,
		$rowCountstm=null,
		$saveopstm=null,
		$plainloadall=null,
		$trackChanges=false,
		$lastOperation=self::OP_NONE,
		$lastError= false,
		$doneIterating= true;
	const
	OP_NONE= 0,
	OP_LOAD= 1,
	OP_INSERT= 2,
	OP_UPDATE= 3,
	OP_DELETE= 4;
	/**
	 * If set to TRUE it will keep a second array with the changes made to the model.
	 * By default, it will not track changes.
	 * If set to false, any changes made cannot be undone.
	 * @param boolean $v (null) Sets the state if supplied
	 * @return boolean
	 */
	public function trackChanges($v= null){
		if($v === null){
			return $this->trackChanges;
		}
		$v= ($v === true);
		if($v === $this->trackChanges){
			return;
		}
		if($v){
			$t= new ChangeTrackingPropertyList();
		}else{
			$t= new PropertyList();
		}
		$t->initFrom($this->data->asArray());
		$this->data= $t;
		$this->trackChanges= $v;
	}
	/**
	 * Clears the value stored for $k
	 * @param string $k
	 */
	public function uset($k){
		$this->data->uset($k);
	}
	/**
	 * Merges the changes with the main array and clears the changes.
	 * Does NOT save the changes to the database.
	 */
	public function mergeChanges(){
		if($this->trackChanges){
			$this->data->mergeChanges();
		}
	}
	/**
	 * Forgets any changes to the model if set to track changes.
	 * Will NOT undo changes commited to the database by calling save().
	 */
	public function forgetChanges(){
		if($this->trackChanges){
			$this->data->discardChanges();
		}
	}
	/**
	 * THIS IS NOT A CALL TO ROLLBACK.
	 * Attempts to undo the last change by calling opposite operation.
	 * Reversing an update requires that change tracking be enabled.
	 * It will NOT revert any auto increment values. Do not use this
	 * as a replacement for transactions.
	 * @return bool True on success
	 */
	public function rollback(){
		switch($this->lastOperation){
			case self::OP_DELETE:
				return $this->insert();
			case self::OP_INSERT:
				return $this->delete();
			case self::OP_UPDATE:
				$change= clone $this->data;
				$this->data->discardChanges();
				$err= $this->update();
				$this->data= $data;
				return $err;
			case self::OP_LOAD:
			case self::OP_NONE:
				return false;
				break;
		}
	}
	/**
	 * Enter description here ...
	 * @param string $table
	 * @param array $columns column=>columnType(PDO::PARAM_*)
	 * @param string|array $pkey The primary key(s) for the table
	 * @param resource $db
	 * @param bool $trackChanges (false)
	 */
	public function __construct($table, array $columns, $pkey, $db, $trackChanges= false){
		$this->table= $table;
		$this->columns= $columns;
		$this->pkey= $pkey;
		$this->db= $db;
		$this->data= $trackChanges ? new ChangeTrackingPropertyList : new PropertyList;
		$this->trackChanges= $trackChanges;
		$this->setupCustomTypes();
		foreach ($this->columns as $name => $type){
			if($this->customTypes[$type]){
				$this->customTypeColumns[$name]= $type;
				$this->columns[$name]= $this->customTypes[$type][0];
	}
		}
	}
	/**
	 * Returns the database connection
	 * @return PDO
	 */
	public function getDatabase(){
		return $this->db;
	}

	/**
	 * Hook for sub-classes to set-up custom data types.
	 * Populate $this->customTypes like so:
	 * $this->customTypes[name]= array(PDO::PARAM_*, pre-string, post-string)
	 */
	protected function setupCustomTypes(){}
	/**
	 * Copies the loaded row to $ary
	 * @param array $ary
	 * @return array The merged array
	 */
	public function copyTo(array $ary){
		return $this->data->copyTo($ary);
	}
	public function asArray(){
		return $this->data->asArray();
	}
	/**
	 * Get the value for $k
	 * @param string $k
	 * @param mixed $d (null) default value
	 * @return mixed
	 */
	public function get($k, $d= null){
		return $this->data->get($k, $d);
	}
	/**
	 * Set the value for $k
	 * @param string $k
	 * @param mixed $v
	 */
	public function set($k, $v){
		$this->data->set($k, $v);
		return $this;
	}
	/**
	 * Sets all the values
	 * @param array $map Map of values to set
	 */
	public function setAll(array $map){
		foreach($map as $k => $v){
			$this->data->set($k, $v);
		}
	}
	/**
	 * Gets the number of rows in the table
	 * @return int|bool the number of rows in the table or false if the query failed
	 */
	public function getTotalRows(){
		if($this->rowCountstm == null){
			$this->rowCountstm= $this->db->prepare('SELECT COUNT(*) FROM '.$this->table);
		}
		if(!db_run_query($this->rowCountstm)){
			$this->lastError= $this->rowCountstm->errorInfo();
			return false;
		}
		$ret= $this->rowCountstm->fetch(PDO::FETCH_NUM);
		return $ret[0];
	}
	/**
	 * Number of rows matched by the query
	 * @return int The number of rows matched by the query or false if the query failed
	 */
	public function count(){
		$where= $this->getWhere();
		$query= 'SELECT COUNT(*) FROM '.$this->table . $where->toString();
		$stm= $this->db->prepare($query);
		if(!db_run_query($stm, $where->getValues())){
			$this->lastError= $stm->errorInfo();
			$this->lastError['query']= $query;
			$this->lastError['params']= $where->getValues();
			return false;
		}
		$ret= $stm->fetch(PDO::FETCH_NUM);
		return $ret[0];
	}
	/**
	 * Get's the primary key.
	 * If it is a compound key, an array in the form of ($key=>$value) containing the values is returned.
	 * @return boolean|array|string false if the ID is not set
	 */
	public function getId(){
		if(is_array($this->pkey)){
			$ret= array();
			foreach($this->pkey as $k){
				$ret[$k]= $this->data->get($k);
				if(null === $ret[$k]){
					return false;
				}
			}
			return $ret;
		}else{
			return $this->data->get($this->pkey,false);
		}
	}
	/**
	 * Used to get the old primary key if track changes is on
	 * @return boolean|array|string
	 */
	public function getOldId(){
		if(!$this->trackChanges){
			return $this->getId();
		}
		if(is_array($this->pkey)){
			$ret= array();
			foreach($this->pkey as $k){
				$ret[$k]= $this->data->getPrevious($k);
				if(null === $ret[$k]){
					return false;
				}
			}
			return $ret;
		}else{
			return $this->data->getPrevious($this->pkey,false);
		}
	}
	/**
	 * Checks to see if the primary key is set.
	 * Assumes the primary key cannot be null.
	 * @return boolean
	 */
	public function isPkeySet(){
		if(is_array($this->pkey)){
			foreach($this->pkey as $k){
				if(null === $this->data->get($k)){
					return false;
				}
			}
			return true;
		}else{
			return $this->data->get($this->pkey) !== null;
		}
	}
	protected function isOldPkeySet(){
		if(is_array($this->pkey)){
			foreach($this->pkey as $k){
				if(null === $this->data->getPrevious($k)){
					return false;
				}
			}
			return true;
		}else{
			return $this->data->getPrevious($this->pkey) !== null;
		}
	}
	/**
	 * Gets the primary key(s) for update and delete operations.
	 * @param mixed $id value(s) for the primary key
	 * @throws IllegalArgumentException
	 * @return WhereBuilder
	 */
	protected function getPkey($id= null){
		$where= new WhereBuilder('pkey');
		if($id != null){
			if(is_array($this->pkey)){
				if(!is_array($id)){
					throw new IllegalArgumentException('Primary key is an array. Supplied IDs must also be an array.');
				}elseif(count($this->pkey) != count($id)){
					throw new IllegalArgumentException('Key count('.count($this->pkey).') and ID count('.count($id).') are not equal');
				}else{
					$keys= array_combine($this->pkey, $id);
					foreach($keys as $k => $v){
						$where->andWhere($k, '=', $v);
					}
				}
			}else{
				if(is_array($id)){
					throw new IllegalArgumentException('Primary key is not an array. Supplied IDs must also not be an array.');
				}
				$where->andWhere($this->pkey, '=', $id);
			}
		}else{//id==null
			if(!$this->isPkeySet()){
				throw new IllegalStateException('Primary key is not set.');
			}
			if(is_array($this->pkey)){
				foreach($this->pkey as $key){
					$where->andWhere($key, '=', $this->data->get($key));
				}
			}else{
				$where->andWhere($this->pkey, '=', $this->data->get($this->pkey));
			}
		}
		return $where;
	}
	/**
	 * @return WhereBuilder
	 */
	protected function getOldPkey(){
		$where= new WhereBuilder('oldpkey');
		if(is_array($this->pkey)){
			$ret= array();
			foreach($this->pkey as $key){
				$where->andWhere($key,'=',$this->data->getPrevious($key));
			}
		}else{
			$where->andWhere($this->pkey,'=',$this->data->getPrevious($this->pkey));
		}
		return $where;
	}
	/**
	 * Useful function for helper classes
	 * @param PDOStatement $data
	 */
	public function setDataset(PDOStatement $data){
		$this->recycle();
		$this->dataset= $data;
	}
	/**
	 * Loads the record. Returns false on error or if no records are found.
	 * @param mixed $id
	 * @return boolean
	 */
	public function load($id= null){
		if(!$this->beforeLoad()){
			return false;
		}
		if($this->dataset){
			$this->dataset->closeCursor();
		}
		$where= $this->getPkey($id);
		$loadstm= $this->db->prepare( 'SELECT * FROM '. $this->table . $where->toString());
		if(!db_run_query($loadstm, $where->getValues())){
			$this->lastError= $loadstm->errorInfo();
			$this->afterLoad(false);
			return false;
		}
		$row= $loadstm->fetch(PDO::FETCH_ASSOC);
		$this->lastOperation= self::OP_LOAD;
		if($row == null){
// 			$this->data->clear();
			$this->resetError();
			$this->afterLoad(false);
			return false;
		}
		$this->data->initFrom($row);
		$this->afterLoad(true);
		return true;
	}
	/**
	 * @param array $columns (null)
	 * @param array $sortBy (null)
	 * @param string $groupBy (null)
	 * @param int $limit (0)
	 * @param int $offset (0)
	 * @return boolean True if successful
	 */
	public function loadAll(array $columns=null,array $sortBy=null, $groupBy=null, $limit=0, $offset=0){
		if($this->dataset){
			$this->dataset->closeCursor();
			$this->dataset= false;
		}
		if($columns || $sortBy || $groupBy || $limit || $offset){
			$stm= $this->db->prepare(db_make_query($this->table, $columns, null, $sortBy, $groupBy, null, $limit, $offset));
		}else{
			if($this->plainloadall == null){
				$this->plainloadall= $this->db->prepare('SELECT * FROM '.$this->table);
			}
			$stm= $this->plainloadall;
		}
		if(db_run_query($stm)){
			$this->dataset= $stm;
		}else{
			$this->lastError= $this->plainloadall->errorInfo();
		}
		$this->lastOperation= self::OP_LOAD;
		return $this->dataset != false;
	}
	/**
	 * Returns a WhereBuilder based on the current data values
	 * @return WhereBuilder
	 */
	protected function getWhere(){
		$where= new WhereBuilder('pdotabledata');
		$data= $this->data->copyTo(array());
		foreach($data as $key => $value){
			if(is_array($value)){
				$where->andWhere($key, 'in', $value);
			}else if($value ===  null){
				$where->andWhere($key, null);
			}else{
				$where->andWhere($key, '=', $value);
			}
		}
		return $where;
	}
	protected function getWherePkey(){
		$where= new WhereBuilder('pdotablepkey');
		$pkeys= is_array($this->pkey) ? $this->pkey : array($this->pkey);
		foreach($pkeys as $key){
			$value= $this->data->get($key);
			if(is_array($value)){
				$where->andWhere($key, 'in', $value);
			}else if($value ===  null){
				$where->andWhere($key, null);
			}else{
				$where->andWhere($key, '=', $value);
			}
		}
		return $where;
	}
	/**
	 * @param array $columns (null)
	 * @param array|WhereBuilder $where (null)
	 * @param array $sortBy (null)
	 * @param string $groupBy (null)
	 * @param string $having (null)
	 * @param int $limit (0)
	 * @param int $offset (0)
	 * @return boolean True if successful; even if there are 0 results
	 */
	public function find(array $columns = null,$where = null,array $sortBy = null, $groupBy = null, $having = null,$limit=0,$offset=0){
		if(!$this->beforeFind()){
			return false;
		}
		if($this->dataset){
			$this->dataset->closeCursor();
		}
		if(!$columns){
			$columns= array_keys($this->columns);
		}
		$this->dataset= null;
		if($where == null){
			$where= $this->getWhere();
		}else if(is_array($where)){
			$where= _db_build_where_obj($where);
		}
		if($this->db->getAttribute(PDO::ATTR_DRIVER_NAME) == 'oci' && ($offset || $limit)){
			// TODO: sniff for 12c and use the better form
			// rownum and row_number() are 1-based so we add 1 to each to adjust
			if($limit){
				$limit++;
			$query= db_make_query($this->table, $columns, $where, $sortBy, $groupBy, $having);
				if($offset){
					$offset++;
					$limit= $limit + $offset;
					$query= "SELECT * (SELECT \"tbl_ asde\".*, rownum AS \"podndflkjer_iasne\" FROM ($query) AS \"tbl_ asde\" WHERE ROWNUM < $limit) WHERE \"podndflkjer_iasne\" >= $offset";
				}else{
					$query= "SELECT * FROM ($query) WHERE ROWNUM < $limit";
				}
			}else{
				if($sortBy && count($sortBy)){
					$sort= db_build_order_by($sortBy);
					$columns[]="row_number() over ($sort) AS \"podndflkjer_iasne\"";
				}else{
					$columns[]='rownum AS "podndflkjer_iasne"';
				}
				$query= db_make_query($this->table, $columns, $where, $sortBy, $groupBy, $having);
				$query= "SELECT * FROM ($query) WHERE \"podndflkjer_iasne\" >= $offset";
				array_pop($columns);
			}
		}else{
			$query= db_make_query($this->table, $columns, $where, $sortBy, $groupBy, $having, $limit, $offset);
		}
		$stm= $this->db->prepare($query);
		if(db_run_query($stm, $where->getValues())){
			$this->dataset= $stm;
		}else{
			$this->lastError= $stm->errorInfo();
			$this->lastError['query']= $query;
			$this->lastError['params']= $where->getValues();
		}
		$this->lastOperation= self::OP_LOAD;
		$this->afterFind($this->dataset != null);
		return $this->dataset != null;
	}
	/**
	 * @param array|WhereBuilder $where (null)
	 * @return boolean
	 */
	public function exists($where= null){
		if($this->dataset){
			$this->dataset->closeCursor();
		}
		if($where == null){
			$where= $this->getWhere();
		}
		$query= 'SELECT DISTINCT 1 FROM '. $this->table . $where->toString();
		$stm= db_prepare($this->db, $query);
		if(db_run_query($stm, $where->getValues())){
			return is_array($stm->fetch(PDO::FETCH_NUM));
		}else{
			$this->lastError= $stm->errorInfo();
			$this->lastError['query']= $query;
			$this->lastError['params']= $where->getValues();
		}
		return false;
	}
	public function pkeyExists(){
		if($this->dataset){
			$this->dataset->closeCursor();
		}
		if($where == null){
			$where= $this->getWherePkey();
		}
		$query= 'SELECT DISTINCT 1 FROM '. $this->table . $where->toString();
		$stm= db_prepare($this->db, $query);
		if(db_run_query($stm, $where->getValues())){
			return is_array($stm->fetch(PDO::FETCH_NUM));
		}else{
			$this->lastError= $stm->errorInfo();
			$this->lastError['query']= $query;
			$this->lastError['params']= $where->getValues();
		}
		return false;
	}
	/**
	 * @return PDOStatement The last PDOStatement or null
	 */
	public function getLoadAllResult(){
		return $this->dataset;
	}
	/**
	 * @throws IllegalStateException If no query was run or if the last query failed.
	 * @return boolean True if there is a next row and the next row was loaded
	 */
	public function loadNext(){
		if(!$this->dataset){
			throw new IllegalStateException('No query run or last query failed.');
		}
		$row= $this->dataset->fetch(PDO::FETCH_ASSOC);
		$this->data->initFrom($row ? $row : array());
		$this->lastOperation= self::OP_LOAD;
		$row= $row != false;
		$this->afterLoad($row);
		return $row;
	}
	/**
	 * Deletes the record represented by this object.
	 * @return bool True on success
	 */
	public function delete(){
		if(!$this->beforeDelete()){
			return false;
		}
		$query= "DELETE FROM ". $this->table;
		if($this->isPkeySet()){
			$where= $this->getPkey();
		}else{
			$where= $this->getWhere();
		}

		$query.= $where->toString();
		$stm= $this->db->prepare($query);
		$success= db_run_query($stm, $where->getValues());
		$this->lastOperation= self::OP_DELETE;
		if(!$success){
			$this->lastError= $stm->errorInfo();
			$this->lastError['query']= $query;
			$this->lastError['params']= $where->getValues();
		}
		$this->afterDelete($success);
		return $success;
	}

	/**
	 * Determines if the save function should run an update or insert based on
	 * the availability of the primary keys.
	 * @return boolean True if the save function should perform an update
	 */
	protected function saveShouldUpdate(){
		return
			$this->trackChanges ?
				$this->isOldPkeySet() :
				$this->isPkeySet() && $this->exists($this->getWherePkey());
	}
	/**
	 * Automatically chooses between insert() and update() based on the availability of the
	 * primary keys.
	 * @return string|boolean false on success or the error.
	 */
	public function save(){
		if(!$this->beforeSave()){
			return false;
		}
		$success=false;
		if($this->saveShouldUpdate()){
			$success= $this->update();
		}else{
			$success= $this->insert();
		}
		$this->afterSave($success);
		return $success;
	}
	/**
	 * Gets the place holder wrapped in a cast if needed.
	 * Add support for the custom types
	 * @param string $column
	 */
	protected function getPlaceholder($column){
		if($this->customTypeColumns[$column]){
			$typeSettings= $this->customTypes[$this->customTypeColumns[$column]];
			return $typeSettings[1] . ":PDT_$column" . $typeSettings[2];
		}else{
			return ":PDT_$column";
		}
	}
	/**
	 * Forces an update.
	 * @return bool True on success
	 */
	public function update(){
		if(!$this->beforeUpdate()){
			return false;
		}
		$query= 'UPDATE '.$this->table.'  SET ';
		$update= $this->data->copyTo(array());
		$data= array();
		foreach($update as $k => $v){
			$placeholder= $this->getPlaceholder($k);
			$query.= "$k=$placeholder,";
			$data[':PDT_'.$k]= $v;
		}
		if($this->trackChanges()){
			$where= $this->getOldPkey();
		}else{
			$where= $this->getPkey();
		}
		$query= substr($query,0,-1) . $where->toString();
		$stm= $this->db->prepare($query);
		$data= array_merge($data, $where->getValues());
		$this->lastOperation= self::OP_UPDATE;
		$success= db_run_query($stm, $data);
		if(!$success){
			$this->lastError= $stm->errorInfo();
			$this->lastError['query']= $query;
			$this->lastError['params']= $data;
		}
		$this->afterUpdate($success);
		return $success;
	}
	/**
	 * Forces an insert.
	 * @return bool True on success
	 */
	public function insert(){
		if(!$this->beforeInsert()){
			return false;
		}
		$returningClause= $this->pkey && !is_array($this->pkey) && $this->db->getAttribute(PDO::ATTR_DRIVER_NAME) == 'oci';
		$data= $this->data->asArray();
		$cols= array_keys($data);
		$id= null;
		$placeholders= '';
		foreach($cols as $col){
			$placeholders.= ',' . $this->getPlaceholder($col);
		}
		$placeholders= substr($placeholders, 1);
		$query='INSERT INTO '. $this->table .' ('. implode(',', $cols) .') VALUES ('. $placeholders .')';

		if($returningClause){
			// TODO: doesn't work; returns a static value
			$query .= ' RETURNING ' . $this->pkey . ' INTO :pkey_return';
		}
		$stm= $this->db->prepare($query);
		foreach($data as $k => $v){
			$stm->bindValue(":PDT_$k", $v, isset($this->columns[$k]) ? $this->columns[$k] : PDO::PARAM_STR);
		}
		if($returningClause){
			$stm->bindParam(':pkey_return', $id, $this->columns[$this->pkey]);
		}
		$success= db_run_query($stm);
		if($success && !is_array($this->pkey) && !$this->isPkeySet()){
			if(!$returningClause){
			$id= $this->db->lastInsertId();
			if($id == 0 && $this->db->getAttribute(PDO::ATTR_DRIVER_NAME) == "pgsql"){
				$id= $this->db->lastInsertId($this->table . '_' . $this->pkey . '_seq');
			}
			}
			$this->data->set($this->pkey, $id);
		}
		$this->lastOperation= self::OP_INSERT;
		if(!$success){
			$this->lastError= $stm->errorInfo();
			$this->lastError['query']= $query;
			$this->lastError['params']= $data;
		}
		$this->afterInsert($success);
		return $success;
	}
	public function getPkeyColumns(){
		if(is_array($this->pkey)){
			return implode(',', $this->pkey);
		}else{
			return $this->pkey;
		}
	}
	/**
	 * Forces an insert. Ignores duplicate key errors.
	 * @return bool True on success
	 */
	public function insertIgnore(){
		if(!$this->beforeInsert()){
			return false;
		}
		$data= $this->data->copyTo(array());
		$cols= array_keys($data);
		$manualCheck= false;
		switch($this->db->getAttribute(PDO::ATTR_DRIVER_NAME)){
			case 'mysql':
				$query= 'INSERT IGNORE INTO '.$this->table.' ('.implode(',', $cols).') VALUES (:PDT_'.implode(',:PDT_', $cols).')';
			break;
			case 'pgsql':
				$query= 'INSERT INTO '.$this->table.' ('.implode(',', $cols).') VALUES (:PDT_'.implode(',:PDT_', $cols).') ON CONFLICT ('
						.$this->getPkeyColumns().') DO NOTHING';
			break;
			default:
				$manualCheck= true;
				$query= 'INSERT INTO '.$this->table.' ('.implode(',', $cols).') VALUES (:PDT_'.implode(',:PDT_', $cols).')';
		}
		$stm=$this->db->prepare( $query);
		foreach($data as $k => $v){
			$stm->bindValue(":PDT_$k", $v, isset($this->columns[$k]) ? $this->columns[$k] : PDO::PARAM_STR);
		}
		$success= db_run_query($stm);
		if($success && !is_array($this->pkey) && !$this->isPkeySet()){
			$id= $this->db->lastInsertId();
			if($id == 0 && $this->db->getAttribute(PDO::ATTR_DRIVER_NAME) == "pgsql"){
				$id= $this->db->lastInsertId($this->table . '_' . $this->pkey . '_seq');
			}
			$this->data->set($this->pkey, $id);
		}
		$this->lastOperation= self::OP_INSERT;
		if(!$success){
				$err= $stm->errorInfo();
			if(!$manualCheck || stripos($err[2], 'duplicate') === false || stripos($err[2], 'key') === false){
				$this->lastError= $err;
				$this->lastError['query']= $query;
				$this->lastError['params']= $data;
			}else{
				$success= true;
			}
		}
		$this->afterInsert($success);
		return $success;
	}
	/**
	 * Forces an insert. Does an update on duplicate key errors.
	 * Does not call the insert or update hooks!
	 * Has a fallback that checks for existence based on the primary key and
	 * calls insert or update which does call the hooks.
	 * @return bool True on success
	 */
	public function insertUpdate(){
		$dbType= $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
		$data= $this->data->copyTo(array());
		$cols= array_keys($data);
		switch($dbType){
			case 'mysql':
				$query='INSERT INTO '.$this->table.' ('.implode(',', $cols).') VALUES (:PDT_'.implode(',:PDT_', $cols).') ON DUPLICATE KEY UPDATE';
				break;
			case 'pgsql':
				// TODO: Not sure if the keys have to be in order for it to match
				$query='INSERT INTO '.$this->table.' ('.implode(',', $cols).') VALUES (:PDT_'.implode(',:PDT_', $cols).') ON CONFLICT ('.( is_array($this->pkey) ? implode(',', $this->pkey) : $this->pkey ).') DO UPDATE SET';
				break;
		}
		if(!$query){
			if($this->exists($this->getPkey())){
				return $this->update();
			}else{
				return $this->insert();
			}
		}
		foreach($data as $k => $v){
			$query.= " $k=:PDT_$k,";
		}
		$query=trim($query, ',');
		$stm= $this->db->prepare( $query);
		foreach($data as $k=>$v){
			$stm->bindValue(":PDT_$k", $v, isset($this->columns[$k]) ? $this->columns[$k] : PDO::PARAM_STR);
		}
		$success= db_run_query($stm);
		if($success && !is_array($this->pkey) && !$this->isPkeySet()){
			$id= $this->db->lastInsertId();
			if($id == 0 && $dbType == "pgsql"){
				$id= $this->db->lastInsertId($this->table . '_' . $this->pkey . '_seq');
			}
			$this->data->set($this->pkey, $id);
		}
		$this->lastOperation= self::OP_INSERT;
		if(!$success){
			$this->lastError= $stm->errorInfo();
			$this->lastError['query']= $query;
			$this->lastError['params']= $data;
		}
		return $success;
	}
	/**
	 * @param string $type
	 */
	protected function saveOperation($type){
		if($this->saveopstm == null){
			$this->saveopstm= $this->db->prepare('INSERT INTO "updates" ("type","data") VALUES (:type,:data)');
		}
		db_run_query($this->saveopstm, array(':type'=>$type,':data'=>serialize($this)));
	}
	/**
	 * Resets the internal arrays and frees any open result sets.
	 */
	public function recycle(){
		if($this->dataset){
			$this->dataset->closeCursor();
		}
		$this->data->initFrom(array());
		$this->lastOperation=self::OP_NONE;
		$this->lastError=null;
		$this->childRecycle();
	}
	public function __clone(){
		$this->data= clone $this->data;
	}
	/**
	 * @return mixed The last error.
	 */
	public function getLastError(){
		return $this->lastError;
	}
	public function resetError(){
		$this->lastError= false;
	}
	public function hasError(){
		return $this->lastError !== false;
	}
	##########
	# Hooks
	##########

	/**
	 * Called before save()
	 * @return boolean True if the save should continue
	 */
	protected function beforeSave(){return true;}
	/**
	 * Called after save.
	 * Not called if canceled by beforeSave()
	 * @param boolean $sucess true if it suceeded
	 */
	protected function afterSave($sucess){}

	/**
	 * Called before load()
	 * @return boolean True if the load should continue
	 */
	protected function beforeLoad(){return true;}
	/**
	 * Called after load.
	 * Not called if canceled by beforeLoad()
	 * @param boolean $sucess true if it suceeded
	 */
	protected function afterLoad($sucess){}
	/**
	 * Called before insert()
	 * @return boolean True if the insert should continue
	 */
	protected function beforeInsert(){return true;}
	/**
	 * Called after insert()
	 * Not called if canceled by beforeInsert()
	 * @param boolean $sucess
	 */
	protected function afterInsert($sucess){}
	/**
	 * Called before update()
	 * @return boolean True if the update should continue
	 */
	protected function beforeUpdate(){return true;}
	/**
	 * Called after update()
	 * Not called if canceled by beforeUpdate()
	 * @param boolean $sucess
	 */
	protected function afterUpdate($sucess){}
	/**
	 * Called before delete
	 * @return boolean True if the delete should continue
	 */
	protected function beforeDelete(){return true;}
	/**
	 * Called after delete
	 * Not called if canceled by beforeDelete()
	 * @param boolean $sucess
	 */
	protected function afterDelete($sucess){}
	/**
	 * Called before find()
	 * @return boolean True if the find should continue
	 */
	protected function beforeFind(){return true;}
	/**
	 * Called after find()
	 * Not called if canceled by beforeFind()
	 * @param boolean $sucess
	 */
	protected function afterFind($sucess){}
	/**
	 * Called after recycle()
	 */
	protected function childRecycle(){}
	protected function _setError($msg){
		$this->lastError= array('56000','',$msg);
	}
}
