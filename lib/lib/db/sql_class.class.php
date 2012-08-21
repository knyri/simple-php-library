<?php
PackageManager::requireClassOnce('error.IllegalArgumentException');
class sql_class{
	protected $data=array();
	protected $dataset=null;
	protected $table=null;
	protected $pkey=null;
	/**
	 * Deletes the record represented by this object.
	 * @return false on success or the error.
	 */
	public function delete(){
		$error=db_delete(null,$this->table,$this->getPkey());
		//$this->saveOperation('delete');
		return $error;
	}
	public function deleteWhere(){
		$where=array();
		foreach($this->data as $key=>$value){
			$where[]=array($key,$value,'AND');
		}
		unset($where[count($where)-1][2]);
		$error=db_delete(null,$this->table,$where);
		//$this->saveOperation('delete');
		return $error;
	}
	public function getId(){
		if(is_array($this->pkey)){
			$ret=array();
			foreach($this->pkey as $key){
				$ret[$key]=$this->data[$key];
			}
			return $ret;
		}else
			return $this->data[$this->pkey];
	}
	/**
	 * Automatically chooses between insert and update based on the availability of the
	 * primary keys.
	 * @return string,boolean false on success or the error.
	 */
	public function save(){
		$error=false;
		if($this->isPkeySet()){
			$error=$this->update();
		}else{
			$error=$this->insert();
		}
		return $error;
	}
	/**
	 * Forces an update.
	 * @return string,boolean FALSE on success or the error.
	 */
	public function update(){
		$error=db_update(null,$this->table,$this->data,$this->getPkey());
		//$this->saveOperation('update');
		return $error;
	}
	/**
	 * Forces an insert. Useful if you want to specify an Auto Increment field.
	 * @return string,boolean false on success or the error.
	 */
	public function insert(){
		$error=db_insert(null,$this->table,$this->data);
		if(!is_array($this->pkey))
			$this->data[$this->pkey]=mysql_insert_id(db_get_connection());
		//$this->saveOperation('insert');
		return $error;
	}
	/**
	 * Loads the record.
	 * @param mixed $id
	 * @return boolean
	 */
	public function load($id){
		$this->data=db_get_row_assoc(null,$this->table,$this->getPkey($id));
		return $this->data!=null;
	}
	public function isPkeySet(){
		if(is_array($this->pkey)){
				$ret=array();
				foreach($this->pkey as $key){
					if(!isset($this->data[$key]))return false;
				}
				return true;
			}else
				return isset($this->data[$this->pkey]);
	}
	/**
	 * Gets the primary key(s) for update and delete operations.
	 * @param mixed $id
	 * @throws IllegalArgumentException
	 * @return array
	 */
	protected function getPkey($id=null){
		if($id!=null){
			if(is_array($this->pkey)){
				if(!is_array($id))
					throw new IllegalArgumentException('Primary key is an array. Supplied IDs must also be an array.');
				elseif(count($this->pkey)!=count($id))
					throw new IllegalArgumentException('Key count('.count($this->pkey).') and ID count('.count($id).') are not equal');
				else{
					$ret=array();
					$keys=array_combine($this->pkey,$id);
					foreach($keys as $key=>$value){
						$ret[]=array($key,$value,'AND');
					}
					unset($ret[count($ret)-1][2]);
					return $ret;
				}
			}else
				return array(array($this->pkey,$id));
		}else{//id==null
			if(is_array($this->pkey)){
				$ret=array();
				foreach($this->pkey as $key){
					$ret[]=array($key,$this->data[$key],'AND');
				}
				unset($ret[count($ret)-1][2]);
				return $ret;
			}else
				return array(array($this->pkey,$this->data[$this->pkey]));
		}
	}
	/**
	 * The number of rows in the table.
	 * @return number The number of rows in the table.
	 */
	public function getTotalRecords(){
		return db_num_rows(null,$this->table);
	}
	/**
	 * Enter description here ...
	 * @param int $limit
	 * @param int $offset
	 * @param array $sort
	 * @return boolean
	 */
	public function loadAll($limit=0,$offset=0,$sort=null){
		$this->dataset=db_query(null,$this->table,'*',null,$sort,null,null,$limit,$offset);
		return $this->dataset!=null;
	}
	/**
	 * Enter description here ...
	 * @return boolean FALSE if there are no more results
	 */
	public function loadNext(){
		$this->data=mysql_fetch_assoc($this->dataset);
		return $this->data!=null;
	}
	/**
	 * Returns the result set from a load all or find call.
	 * @return NULL, resource
	 */
	public function getLoadAllResult(){
		return $this->dataset;
	}
	/**
	 * Frees the result set if set.
	 */
	public function freeResult(){
		if(is_resource($this->dataset))
			mysql_free_result($this->dataset);
		$this->dataset=null;
	}
	/**
	 * Meant to be overridden by subclasses.
	 * @return boolean
	 */
	public function loadAllForForm(){
		return $this->loadAll();
	}
	/**
	 * Meant to be overridden by subclasses.
	 * @return Ambigous <number, number>
	 */
	public function findAllForForm(){
		return $this->find();
	}
	protected function saveOperation($type){
		db_insert(null,'changelog',array(
			'type'=>$type,
			'data'=>serialize($this)
		));
	}
	/**
	 * Enter description here ...
	 * @param int $limit
	 * @param int $offset
	 * @param array $sort
	 * @return number,boolean The number of rows found or false on error.NOTE: there is a chance that 0 is returned; use === for testing.
	 */
	public function find($limit=0,$offset=0,$sort=null){
		$where=array();
		foreach($this->data as $key=>$value){
			$where[]=array($key,$value,'AND');
		}
		unset($where[count($where)-1][2]);
		$this->dataset=db_query(null,$this->table,'*',$where,$sort,null,null,$limit,$offset);
		if(is_resource($this->dataset))
			return db_num_rows(null,$this->table,$where,$sort,null,null,$limit,$offset);
		else
			return false;
	}
	/**
	 * Resets the internal arrays and frees any open result sets.
	 */
	public function recycle(){
		if(is_resource($this->dataset))
			mysql_free_result($this->dataset);
		$this->data=array();
	}
	/**
	 * Copies the data held by the internal array to the given array.
	 * @param array $ary
	 * @return array The resulting array.
	 */
	public function copyTo(array $ary){
		foreach($this->data as $k=>$v)
			$ary[$k]=$v;
		return $ary;
	}
	public function __clone(){
		$this->data=clone $this->data;
	}
}