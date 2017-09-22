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
	protected $table,
		$columns,
		$data,
		$dataset=null,
		$pkey=null,
		$db=null,
		$rowCountstm=null,
		$saveopstm=null,
		$loadstm=null,
		$plainloadall=null,
		$trackChanges=false,
		$lastOperation=self::OP_NONE,
		$lastError=null;
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
			$t->initFrom($this->data->copyTo(array()));
			$this->data= $t;
		}else{
			$t= new PropertyList();
			$t->initFrom($this->data->copyTo(array()));
			$this->data= $t;
		}
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
	}
	/**
	 * Copies the loaded row to $ary
	 * @param array $ary
	 * @return array The merged array
	 */
	public function copyTo(array $ary){
		return $this->data->copyTo($ary);
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
		$stm= 'SELECT COUNT(*) FROM '.$this->table;
		$args= null;
		if($this->data->count()){
			$args= array();
			$stm.= ' WHERE ';
			$where= new WhereBuilder();
			$data= $this->data->copyTo(array());
			foreach($data as $k => $v){
				$where->andWhere($k, '=', $v);
			}
			$stm.= $where->getWhere();
			$args= $where->getValues();
		}
		$stm= $this->db->prepare($stm);
		if(!db_run_query($stm, $args)){
			$this->lastError= $stm->errorInfo();
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
			$ret= array();
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
					//$ret= array();
					$keys= array_combine($this->pkey, $id);
					foreach($keys as $k => $v){
						$where->andWhere($k, '=', $v);
					}
				}
			}else{
				$where->andWhere($this->pkey, '=', $id);
			}
		}else{//id==null
			if(is_array($this->pkey)){
				$ret= array();
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
			$this->lastError= 'Cancelled by subclass.';
			return false;
		}
		if($this->dataset){
			$this->dataset->closeCursor();
		}
		$where= $this->getPkey($id);
		if($this->loadstm == null){
			$this->loadstm= $this->db->prepare( 'SELECT * FROM '.$this->table.' WHERE ' . $where->getWhere());
		}else{
			$this->loadstm->closeCursor();
		}
		if(!db_run_query($this->loadstm, $where->getValues())){
			$this->lastError= $this->loadstm->errorInfo();
			$this->afterLoad(false);
			return false;
		}
		$row= $this->loadstm->fetch(PDO::FETCH_ASSOC);
		$this->data->initFrom($row ? $row : array());
		$this->lastOperation= self::OP_LOAD;
		$this->afterLoad($row != null);
		return $row != null;
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
		}

		if($columns || $sortBy || $groupBy || $limit || $offset){
			$stm= $this->db->prepare(db_make_query($this->table, $columns, null, $sortBy, $groupBy, null, $limit, $offset));
		}else{
			if($this->plainloadall == null){
				$this->plainloadall= $this->db->prepare('SELECT * FROM '.$this->table);
			}
			if(db_run_query($this->plainloadall)){
				$this->dataset= $this->plainloadall;
			}else{
				$this->lastError= $this->plainloadall->errorInfo();
			}
		}
		$this->lastOperation= self::OP_LOAD;
		return $this->dataset != false;
	}
	/**
	 * Returns a WhereBuilder based on the current data values
	 * @return WhereBuilder
	 */
	private function getWhere(){
		$where= new WhereBuilder('data');
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
	/**
	 * @param array $columns (null)
	 * @param array|WhereBuilder $where (null)
	 * @param array $sortBy (null)
	 * @param string $groupBy (null)
	 * @param string $having (null)
	 * @param int $limit (0)
	 * @param int $offset (0)
	 * @return boolean True if successful
	 */
	public function find(array $columns = null,$where = null,array $sortBy = null, $groupBy = null, $having = null,$limit=0,$offset=0){
		if($this->dataset){
			$this->dataset->closeCursor();
		}
		if($where == null){
			$where= $this->getWhere();
		}
		$this->dataset= null;
		if($this->exists($where)){
			if(is_array($where)){
				$where= _db_build_where_obj($where);
			}
			$query= $this->db->prepare(db_make_query($this->table, $columns, $where, $sortBy, $groupBy, $having, $limit, $offset));
			if(db_run_query($query, $where->getValues())){
				$this->dataset= $query;
			}else{
				$this->lastError= $query->errorInfo();
			}
		}
		$this->lastOperation= self::OP_LOAD;
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
		$count= db_exists($this->db, $this->table, $where);
		return $count == '1';
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
		return $row != false;
	}
	/**
	 * Deletes the record represented by this object.
	 * @return bool True on success
	 */
	public function delete(){
		if(!$this->beforeDelete()){
			return 'Cancelled by subclass.';
		}
		$query= "DELETE FROM ". $this->table . " WHERE ";
		if($this->isPkeySet()){
			$where= $this->getPkey();
		}else{
			$where= $this->getWhere();
		}
		$query.= $where->getWhere();
		$stm= $this->db->prepare($query);
		$error= !db_run_query($stm, $where->getValues());
		$this->lastOperation= self::OP_DELETE;
		if($error){
			$this->lastError= $stm->errorInfo();
		}
		$this->afterDelete($error);
		return $error;
	}
	/**
	 * Automatically chooses between insert() and update() based on the availability of the
	 * primary keys.
	 * @return string|boolean false on success or the error.
	 */
	public function save(){
		if(!$this->beforeSave()){
			$this->lastError= 'Cancelled by subclass';
			return false;
		}
		$error= false;
		if($this->isPkeySet()){
			$error= !$this->update();
		}else{
			$error= !$this->insert();
		}
		$this->afterSave($error);
		return $error;
	}
	/**
	 * Forces an update.
	 * @return bool True on success
	 */
	public function update(){
		if(!$this->beforeUpdate()){
			$this->lastError= 'Cancelled by subclass.';
			return false;
		}
		$query= 'UPDATE "'.$this->table.'"  SET ';
		$update= $this->data->copyTo(array());
		$data= array();
		foreach($update as $k => $v){
			$query.= "$k=:col$k,";
			$data[':col'.$k]= $v;
		}
		if($this->trackChanges()){
			$where= $this->getOldPkey();
		}else{
			$where= $this->getPkey();
		}
		$query= substr($query,0,-1) . ' WHERE ' . $where->getWhere();
		$stm= $this->db->prepare($query);
		$data= array_merge($data, $where->getValues());
		$this->lastOperation= self::OP_UPDATE;
		$hadError= !db_run_query($stm, $data);
		if($hadError){
			$this->lastError= $stm->errorInfo();
		}
		$this->afterUpdate($hadError);
		return $hadError;
	}
	/**
	 * Forces an insert.
	 * @return bool True on success
	 */
	public function insert(){
		if(!$this->beforeInsert()){
			$this->lastError= 'Cancelled by subclass.';
			return false;
		}
		$data= $this->data->copyTo(array());
		$cols= array_keys($data);
		$query='INSERT INTO "' . $this->table . '" ("' . implode('","', $cols).'") VALUES (:' . implode(',:', $cols) . ')';
		$stm= $this->db->prepare($query);
		foreach($data as $k => $v){
			$stm->bindValue(":$k", $v, $this->columns[$k]);
		}
		$error= !db_run_query($stm);
		if(!$error && !is_array($this->pkey) && !$this->isPkeySet()){
			$id= $this->db->lastInsertId();
			if($id == 0 && $this->db->getAttribute(PDO::ATTR_DRIVER_NAME) == "pgsql"){
				$id= $this->db->lastInsertId($this->table . '_' . $this->pkey . '_seq');
			}
			$this->data->set($this->pkey, $id);
		}
		$this->lastOperation= self::OP_INSERT;
		if($error){
			$this->lastError= $stm->errorInfo();
		}
		$this->afterInsert($error);
		return $error;
	}
	/**
	 * Forces an insert. Ignores duplicate key errors.
	 * @return bool True on success
	 */
	public function insertIgnore(){
		if(!$this->beforeInsert()){
			$this->lastError= 'Cancelled by subclass.';
			return false;
		}
		$data= $this->data->copyTo(array());
		$cols= array_keys($data);
		$query= 'INSERT IGNORE INTO "'.$this->table.'" ("'.implode('","', $cols).'") VALUES (:'.implode(',:', $cols).')';
		$stm=$this->db->prepare( $query);
		foreach($data as $k => $v){
			$stm->bindValue(":$k", $v, $this->columns[$k]);
		}
		$error= !db_run_query($stm);
		if(!$error && !is_array($this->pkey) && !$this->isPkeySet()){
			$id= $this->db->lastInsertId();
			if($id == 0 && $this->db->getAttribute(PDO::ATTR_DRIVER_NAME) == "pgsql"){
				$id= $this->db->lastInsertId($this->table . '_' . $this->pkey . '_seq');
			}
			$this->data->set($this->pkey, $id);
		}
		$this->lastOperation= self::OP_INSERT;
		if($error){
			$this->lastError= $stm->errorInfo();
		}
		$this->afterInsert($error);
		return $error;
	}
	/**
	 * Forces an insert. Does an update on duplicate key errors.
	 * Does not call the insert or update hooks!
	 * @return bool True on success
	 */
	public function insertUpdate(){
		$dbType= $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
		$data= $this->data->copyTo(array());
		$cols= array_keys($data);
		switch($dbType){
			case 'mysql':
				$query='INSERT INTO "'.$this->table.'" ("'.implode('","', $cols).'") VALUES (:'.implode(',:', $cols).') ON DUPLICATE KEY UPDATE';
				break;
			case 'pgsql':
				// TODO: Not sure if the keys have to be in order for it to match
				$query='INSERT INTO "'.$this->table.'" ("'.implode('","', $cols).'") VALUES (:'.implode(',:', $cols).') ON CONFLICT ("'.( is_array($this->pkey) ? implode('","', $this->pkey) : $this->pkey ).'") DO UPDATE SET';
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
			$query.= " \"$k\"=:$k,";
		}
		$query= trim($query, ',');
		$stm= $this->db->prepare( $query);
		foreach($data as $k=>$v){
			$stm->bindValue(":$k", $v, $this->columns[$k]);
		}
		$error= !db_run_query($stm);
		if(!$error && !is_array($this->pkey) && !$this->isPkeySet()){
			$id= $this->db->lastInsertId();
			if($id == 0 && $dbType == "pgsql"){
				$id= $this->db->lastInsertId($this->table . '_' . $this->pkey . '_seq');
			}
			$this->data->set($this->pkey, $id);
		}
		$this->lastOperation= self::OP_INSERT;
		if($error){
			$this->lastError= $stm->errorInfo();
		}
		return $error;
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
	 * @return string The last error.
	 */
	public function getLastError(){
		return $this->lastError;
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
}
