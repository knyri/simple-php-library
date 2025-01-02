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
		$columnWrappers= array(),
		$data,
		$dataset= null,
		$pkey= null,
		$db= null,
		$rowCountstm= null,
		$saveopstm= null,
		$plainloadall= null,
		$trackChanges= false,
		$lastOperation= self::OP_NONE,
		$lastError= false,
		$doneIterating= true,
		$differentialUpdate= true,
		$defaultSort= null
	;
	const
		OP_NONE= 0,
		OP_LOAD= 1,
		OP_INSERT= 2,
		OP_UPDATE= 3,
		OP_DELETE= 4
	;
	public function debug(){
		logit($this->data);
	}


	/**
	 * Ignore changes to these columns when determining if a change happened. Useful for columns used to track the modified date.
	 * Only works when track changes is set to true.
	 * @param array $cols
	 */
	public function ignoreUpdateColumns(array $cols){
		if($this->trackChanges){
			$this->data->setIgnoredProps($cols);
		}else{
			throw new Exception('Track changes must be enabled first to use this.');
		}
		return $this;
	}
	/**
	 * Sets if an update statement will send all the columns (false) or just the updated ones (true; default) when track changes is set.
	 * @param boolean $v Sets the state if supplied
	 * @return boolean|PDOTable
	 */
	public function differentialUpdate($v= null){
		if($v === null){
			return $this->differentialUpdate;
		}
		$this->differentialUpdate= !!$v;
		return $this;
	}
	/**
	 * The defined columns
	 * @return array
	 */
	public function getColumns(){
		return $this->columns;
	}
	/**
	 * If set to TRUE it will keep a second array with the changes made to the model.
	 * By default, it will not track changes.
	 * If set to false, any changes made cannot be undone.
	 * @param boolean $v (null) Sets the state if supplied
	 * @return boolean|PDOTable
	 */
	public function trackChanges($v= null){
		if($v === null){
			return $this->trackChanges;
		}
		$v= ($v === true);
		if($v === $this->trackChanges){
			return $this;
		}
		if($v){
			$t= new ChangeTrackingPropertyList();
		}else{
			$t= new PropertyList();
		}
		$t->initFrom($this->data->asArray());
		$this->data= $t;
		$this->trackChanges= $v;
		return $this;
	}
	/**
	 * Clears the value stored for $k
	 * @param string $k
	 * @return $this
	 */
	public function uset($k){
		$this->data->uset($k);
		return $this;
	}
	/**
	 * Merges the changes with the main array and clears the changes. Change tracking must be enabled.
	 * Does NOT save the changes to the database.
	 * @return $this
	 */
	public function mergeChanges(){
		if($this->trackChanges){
			$this->data->mergeChanges();
		}
		return $this;
	}
	/**
	 * Always returns true if track changes is not enabled
	 * @return boolean
	 */
	public function hasChanges(){
		if(!$this->trackChanges){
			return true;
		}
		return $this->data->hasChanges();
	}
	/**
	 * Forgets any changes to the model if set to track changes.
	 * Will NOT undo changes commited to the database by calling save().
	 * @return $this
	 */
	public function forgetChanges(){
		if($this->trackChanges){
			$this->data->discardChanges();
		}
		return $this;
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
				if(!($success= $this->update())){
					$this->data= $change;
				}
				return $success;
			case self::OP_LOAD:
			case self::OP_NONE:
				return false;
				break;
		}
		return false;
	}
	/**
	 * Enter description here ...
	 * @param string $table
	 * @param array $columns column=>columnType(PDO::PARAM_*)
	 * @param string|array $pkey The primary key(s) for the table
	 * @param PDO $db
	 * @param bool $trackChanges (false)
	 * @param array $defaultSort An array of arrays with column, direction. E.G. array(array('state','asc'),array('population','desc'))
	 */
	public function __construct($table, array $columns, $pkey, $db, $trackChanges= false, array $defaultSort= null){
		$this->table= $table;
		$this->columns= $columns;
		$this->pkey= $pkey;
		$this->db= $db;
		$this->data= $trackChanges ? new ChangeTrackingPropertyList : new PropertyList;
		$this->trackChanges= $trackChanges;
		$this->defaultSort= $defaultSort;
		$this->setupCustomTypes();
		foreach ($this->columns as $name => $type){
			if(array_key_exists($type, $this->customTypes)){
				$this->customTypeColumns[$name]= $type;
				$this->columns[$name]= $this->customTypes[$type][0];
			}
		}
		$this->setupColumnWrappers();
	}
	/**
	 * Returns the database connection
	 * @return PDO
	 */
	public function getDatabase(){
		return $this->db;
	}

	/**
	 * Hook to add additional custom types. This is the preferred function.
	 * @param string $typeName
	 * @param int $pdoType PDO::PARAM_*
	 * @param string $preStr
	 * @param string $postStr
	 * @return PDOTable
	 */
	public function addCustomType($typeName, $pdoType, $preStr, $postStr){
		$this->customTypes[$typeName]= array($pdoType, $preStr, $postStr);
		foreach ($this->columns as $name => $type){
			if($type == $typeName){
				$this->customTypeColumns[$name]= $type;
				$this->columns[$name]= $pdoType;
			}
		}
		return $this;
	}

	/**
	 * Deprecated. Use addCustomType
	 * Hook for sub-classes to set-up custom data types.
	 * Populate $this->customTypes like so:
	 * $this->customTypes[name]= array(PDO::PARAM_*, pre-string, post-string)
	 * @deprecated
	 */
	protected function setupCustomTypes(){}
	/**
	 * Hook for sub-classes to set-up column wrappers. You do not need to add an alias to the post string.
	 * Populate $this->columnWrappers like so:
	 * $this->columnWrappers[name]= array(pre-string, post-string)
	 */
	protected function setupColumnWrappers(){}
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
	 * @return $this
	 */
	public function set($k, $v){
		$this->data->set($k, $v);
		return $this;
	}
	/**
	 * Sets all the values from the array.
	 * @param array $map Map of values to set
	 * @return $this
	 */
	public function setAll(array $map){
		foreach($map as $k => $v){
			$this->data->set($k, $v);
		}
		return $this;
	}
	/**
	 * Sets all the values for columns defined
	 * @param array $map Map of values to set
	 * @return $this
	 */
	public function setAllDefined(array $map){
		foreach($map as $k => $v){
			if(array_key_exists($k, $this->columns)){
				$this->data->set($k, $v);
			}
		}
		return $this;
	}
	/**
	 * Gets the number of rows in the table
	 * @return int|bool the number of rows in the table or false if the query failed
	 */
	public function getTotalRows(){
		$this->resetError();
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
		$this->resetError();
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
		if($id !== null){
			if(is_array($this->pkey)){
				if(!is_array($id)){
					throw new IllegalArgumentException('Primary key is an array. Supplied ID is not an array.');
				}elseif(count($this->pkey) != count($id)){
					foreach($this->pkey as $v){
						if(!array_key_exists($v, $id)){
							throw new IllegalArgumentException("Key column $v is missing from the supplied array.");
						}
						$where->andWhere($v, '=', $id[$v]);
					}
				}else{
					$keys= array_combine($this->pkey, $id);
					foreach($keys as $k => $v){
						$where->andWhere($k, '=', $v);
					}
				}
			}else{
				if(is_array($id)){
					if(!array_key_exists($this->pkey, $id)){
						throw new IllegalArgumentException('Primary key is not in the supplied array');
					}
					$where->andWhere($this->pkey, '=', $id[$this->pkey]);
				}else{
					$where->andWhere($this->pkey, '=', $id);
				}
			}
		}else{//id===null
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
	 * @param mixed $id Can be an array or contain extra values as long as the primary key/keys is/are present
	 * @return boolean
	 */
	public function load($id= null){
		$this->resetError();
		if(!$this->beforeLoad()){
			return false;
		}
		if($this->dataset){
			$this->dataset->closeCursor();
		}
		$where= $this->getPkey($id);
		$loadstm= $this->db->prepare('SELECT * FROM '. $this->table . $where->toString());
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
		if($this->trackChanges){
			$this->data->commitChanges();
		}
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
		$this->resetError();
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
			$this->lastError= $stm->errorInfo();
		}
		$this->lastOperation= self::OP_LOAD;
		return $this->dataset != false;
	}
	/**
	 * Returns a WhereBuilder based on the current data values
	 * @return WhereBuilder
	 */
	public function getWhere(){
		$where= new WhereBuilder('pdotabledata');
		$data= $this->data->copyTo(array());
		foreach($data as $key => $value){
			if(is_array($value)){
				$where->andWhere($this->getColumnWrapped($key), 'in', $value);
			}else if($value ===  null){
				$where->andWhere($key, null);
			}else{
				$where->andWhere($this->getColumnWrapped($key), '=', $value);
			}
		}
		return $where;
	}
	/**
	 * Where statement using the primary key(s)
	 * @return WhereBuilder
	 */
	protected function getWherePkey(){
		$where= new WhereBuilder('pdotablepkey');
		$pkeys= is_array($this->pkey) ? $this->pkey : array($this->pkey);
		foreach($pkeys as $key){
			$value= $this->data->get($key);
			if(is_array($value)){
				$where->andWhere($this->getColumnWrapped($key), 'in', $value);
			}else if($value ===  null){
				$where->andWhere($key, null);
			}else{
				$where->andWhere($this->getColumnWrapped($key), '=', $value);
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
	public function find(array $columns= null, $where= null, array $sortBy= null, $groupBy= null, $having= null, $limit= 0, $offset= 0){
		$this->resetError();
		if(!$this->beforeFind()){
			return false;
		}
		if($sortBy === null){
			$sortBy= $this->defaultSort;
		}
		if($this->dataset){
			$this->dataset->closeCursor();
		}
		if(!$columns){
			$columns= array_keys($this->columns);
		}
		foreach($columns as $k=>$v){
			//echo "$k=>$v" . PHP_EOL;
			$columns[$k]= $this->getColumnAlias($v);
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
		//echo $query . PHP_EOL;
		$stm= $this->db->prepare($query);
// 		logit($query);
// 		logit($where->getValues());
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
	 * @param WhereBuilder $where (null)
	 * @return boolean
	 */
	public function exists($where= null){
		$res= false;
		$this->resetError();
		if($this->dataset){
			$this->dataset->closeCursor();
		}
		if($where == null){
			$where= $this->getWhere();
		}
		$query= 'SELECT DISTINCT 1 FROM '. $this->table . $where->toString();
		$stm= db_prepare($this->db, $query);
		if(db_run_query($stm, $where->getValues())){
			$res= is_array($stm->fetch(PDO::FETCH_NUM));
			$stm->closeCursor();
		}else{
			$this->lastError= $stm->errorInfo();
			$this->lastError['query']= $query;
			$this->lastError['params']= $where->getValues();
		}
		return $res;
	}
	/**
	 * Checks for the existence of this record using the primary keys
	 * @return boolean
	 */
	public function pkeyExists(){
		if(!$this->isPkeySet()){
			return false;
		}
		$res= false;
		$this->resetError();
		if($this->dataset){
			$this->dataset->closeCursor();
		}
		$where= $this->getWherePkey();
		$query= 'SELECT DISTINCT 1 FROM '. $this->table . $where->toString();
		$stm= db_prepare($this->db, $query);
		if(db_run_query($stm, $where->getValues())){
			$res= is_array($stm->fetch(PDO::FETCH_NUM));
			$stm->closeCursor();
		}else{
			$this->lastError= $stm->errorInfo();
			$this->lastError['query']= $query;
			$this->lastError['params']= $where->getValues();
		}
		return $res;
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
		if($row && $this->trackChanges){
			// prevent data conversions from marking it as changed
			$this->data->commitChanges();
		}
		return $row;
	}
	/**
	 * Deletes the record represented by this object.
	 * @return bool True on success
	 */
	public function delete(){
		$this->resetError();
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
// 		logit('saveShouldUpdate');
// 		if($this->trackChanges){
// 			logit('old pkey: ' . ($this->isOldPkeySet() ? 'set' : 'not set'));
// 		}
// 		logit('pkey exists: ' . ($this->pkeyExists()  ? 'yes' : 'no'));
		return
			$this->trackChanges && $this->isOldPkeySet() ||
			$this->pkeyExists();
	}
	/**
	 * Loads the record and sets the data
	 * @param $data
	 * @return boolean false on load error
	 */
	public function loadAndSetAll($data){
		$this->recycle();
		if(!$this->load($data)){
			return false;
		}
		$this->setAll($data);
		return true;
	}
	/**
	 * Saves only if there are changes
	 * @return boolean
	 */
	public function saveChanges(){
//		logit($this->data->getChanges());
		if($this->hasChanges()){
			return $this->save();
		}
		return true;
	}
	/**
	 * Automatically chooses between insert() and update() based on the availability of the
	 * primary keys.
	 * @return boolean True on success
	 */
	public function save(){
		$success= false;
		if($this->saveShouldUpdate()){
			$success= $this->update();
		}else if($this->hasError()){
			// check for error while doing pkey lookup
			$success= false;
		}else{
			$success= $this->insert();
		}
		$this->afterSave($success);
		if($this->trackChanges){
			$this->data->commitChanges();
		}
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
	 * Gets the column wrapped in any defined custom wrapper for the column list
	 * @param string $column
	 * @return string
	 */
	protected function getColumnAlias($column){
		if($this->columnWrappers[$column]){
			$typeSettings= $this->columnWrappers[$column];
			return $typeSettings[0] . $column . $typeSettings[1] . ' AS ' . $column;
		}else{
			return $column;
		}
	}
	/**
	 * Gets the column wrapped in any defined custom wrapper
	 * @param string $column
	 * @return string
	 */
	public function getColumnWrapped($column){
		if($this->columnWrappers[$column]){
			$typeSettings= $this->columnWrappers[$column];
			return $typeSettings[0] . $column . $typeSettings[1];
		}else{
			return $column;
		}
	}
	/**
	 * Forces an update.
	 * @return bool True on success
	 */
	public function update(){
		$this->resetError();
		if(!$this->beforeUpdate() || !$this->beforeSave()){
			if(!$this->hasError()){
				$this->_setError('beforeUpdate() or beforeSave() returned false');
			}
			return false;
		}
		$query= 'UPDATE '.$this->table.'  SET ';
		$update= $this->data->copyTo(array());
		$data= array();
		$types= array();
		if($this->trackChanges() && $this->differentialUpdate()){
			foreach($update as $k => $v){
				if($this->data->hasChanged($k)){
					$placeholder= $this->getPlaceholder($k);
					$query.= "$k=$placeholder,";
					$data[':PDT_'.$k]= $v;
					$types[':PDT_'.$k]= $this->columns[$k];
				}
			}
			if(count($data) == 0){
				return true;
			}
			if($this->isOldPkeySet()){
				$where= $this->getOldPkey();
			}else{
				$where= $this->getPkey();
			}
		}else{
			foreach($update as $k => $v){
				$placeholder= $this->getPlaceholder($k);
				$query.= "$k=$placeholder,";
				$data[':PDT_'.$k]= $v;
				$types[':PDT_'.$k]= $this->columns[$k];
			}
			$where= $this->getPkey();
		}
		$query= substr($query,0,-1) . $where->toString();
		$stm= $this->db->prepare($query);
		$data= array_merge($data, $where->getValues());
		$this->lastOperation= self::OP_UPDATE;
		$success= db_run_query($stm, $data, $types);
		if(!$success){
			$this->lastError= $stm->errorInfo();
			$this->lastError['query']= $query;
			$this->lastError['params']= var_export($data, true);
		}
		$this->afterUpdate($success);
		if($this->trackChanges){
			$this->data->commitChanges();
		}
		return $success;
	}
	/**
	 * Forces an insert.
	 * @return bool True on success
	 */
	public function insert(){
		$this->resetError();
		if(!$this->beforeInsert() || !$this->beforeSave()){
			if(!$this->hasError()){
				$this->_setError('beforeInsert() or beforeSave() returned false');
			}
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
		if($success && $this->trackChanges){
			$this->data->commitChanges();
		}
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
		$this->resetError();
		if(!$this->beforeInsert() || !$this->beforeSave()){
			if(!$this->hasError()){
				$this->_setError('beforeInsert() or beforeSave() returned false');
			}
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
		$stm= $this->db->prepare($query);
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
		if($success && $this->trackChanges){
			$this->data->commitChanges();
		}
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
		$this->resetError();
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
		$query= trim($query, ',');
		$stm= $this->db->prepare($query);
		foreach($data as $k => $v){
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
		if($success && $this->trackChanges){
			$this->data->commitChanges();
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
		$this->lastError= false;
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
	public function getErrorMessage(){
		return $this->lastError ? $this->lastError[2] : null;
	}
	public function getErrorCode(){
		return $this->lastError ? $this->lastError[1] : null;
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
	 * Called after save. Any changes to the data in this are not counted as changes if tracking changes.
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
	 * Called after load. Any changes to the data in this are not counted as changes if tracking changes.
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

/**
 * Doesn't allow inserts, updates, or deletes
 *
 */
class PDOView extends PDOTable {
	protected function beforeInsert(){
		return false;
	}
	protected function beforeUpdate(){
		return false;
	}
	protected function beforeDelete(){
		return false;
	}
}