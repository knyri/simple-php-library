<?php
PackageManager::requireFunctionOnce('db.database');
/**
 * SQL backed model
 * @author Kenneth Pierce Jr
 *
 */
class sql_model{
	protected $table=null,
		$primary_fields=array(),
		$data=array();
	public function create(){
		$this->onBeforeCreate();
		$ret=db_insert(null,$this->table,$this->data)===false;
		$this->onAfterCreate();
		return $ret;
	}
	public function update(){
		$this->onBeforeUpdate();
		$ret= db_update(null,$this->table,$this->data,build_where());
		$this->onAfterUpdate();
		return $ret;
	}
	public function load(){
		$this->onBeforeLoad();
		$this->data=db_get_row_assoc(null,$this->table,build_where());
		$this->onAfterLoad();
		return $this->data!==null;
	}
	public function find($col){
		$this->onBeforeFind();
		$this->data=db_get_row_assoc(null,$this->table,array(array($col,$this->data[$col])));
		$this->onAfterFind();
		return $this->data!==null;
	}
	private function build_where(){
		$ret=array();$len=count($this->primary_fields);
		for($i=0;$i<$len;$i++){
			if($i<$len-1)
				$ret[]=array($this->primary_fields[$i],$this->data[$this->primary_fields[$i]],'AND');
			else
				$ret[]=array($this->primary_fields[$i],$this->data[$this->primary_fields[$i]]);
		}
		return $ret;
	}
	public function get($col){
		return $this->data[$col];
	}
	public function set($col, $value){
		$this->data[$col]=$value;
	}
	protected function onBeforeUpdate(){}
	protected function onAfterUpdate(){}
	protected function onBeforeLoad(){}
	protected function onAfterLoad(){}
	protected function onBeforeCreate(){}
	protected function onAfterCreate(){}
	protected function onBeforeFind(){}
	protected function onAfterFind(){}
}