<?php

PackageManager::requireClassOnce('object');
PackageManager::requireClassOnce('model.datasource');

/**
 * Enter description here ...
 * @author Kenneth Pierce
 *
 */
class DataModel extends Object {
	var $data = array();
	var $fields = array();
	var $primary_fields = array();
	var $datasource = false;
	public function setDataSource(DataSource $source) {
		$this->datasource = $source;
	}
	public function getDataSource() {
		return $this->datasource;
	}
	public function set($name, $value) {
		$this->data[$name] = $value;
	}
	public function get($name) {
		return $this->data[$name];
	}

	/**
	 * Executed before the item is created. Meant to be overridden.
	 * @return boolean True to continue. False to stop.
	 */
	function beforeCreate() {
		return true;
	}
	/**
	 * Calls validate, before, create, and after.
	 * @return boolean success
	 */
	public function create() {
		if (!$this->validate()) return false;
		if (!$this->beforeCreate()) return false;
		if (!$this->datasource->create($this)) return false;
		if (!$this->afterCreate()) return false;
		return true;
	}
	/**
	 * Executed after the item is created. Meant to be overridden.
	 * @return boolean True to continue. False to stop.
	 */
	public function afterCreate() {
		return true;
	}

	/**
	 * Executed before the item is read. Meant to be overridden.
	 * @return boolean True to continue. False to stop.
	 */
	function beforeRead() {
		return true;
	}
	/**
	 * Calls validate, before, read, and after.
	 * @return boolean success
	 */
	public function read() {
		if (!$this->validate()) return false;
		if (!$this->beforeRead()) return false;
		if (!$this->datasource->read($this)) return false;
		if (!$this->afterRead()) return false;
		return true;
	}
	/**
	 * Executed after the item is read. Meant to be overridden.
	 * @return boolean True to continue. False to stop.
	 */
	public function afterRead() {
		return true;
	}

	/**
	 * Executed before the item is updated. Meant to be overridden.
	 * @return boolean True to continue. False to stop.
	 */
	function beforeUpdate() {
		return true;
	}
	/**
	 * Calls validate, before, update, and after.
	 * @return boolean success
	 */
	public function update() {
		if (!$this->validate()) return false;
		if (!$this->beforeUpdate()) return false;
		if (!$this->datasource->update($this)) return false;
		if (!$this->afterUpdate()) return false;
		return true;
	}
	/**
	 * Executed after the item is updated. Meant to be overridden.
	 * @return boolean True to continue. False to stop.
	 */
	public function afterUpdate() {
		return true;
	}

	/**
	 * Executed before the item is deleted. Meant to be overridden.
	 * @return boolean True to continue. False to stop.
	 */
	function beforeDelete() {
		return true;
	}
	/**
	 * Calls validate, before, delete, and after.
	 * @return boolean success
	 */
	public function delete() {
		if (!$this->validate()) return false;
		if (!$this->beforeDelete()) return false;
		if (!$this->datasource->delete($this)) return false;
		if (!$this->afterDelete()) return false;
		return true;
	}
	/**
	 * Executed after the item is deleted. Meant to be overridden.
	 * @return boolean True to continue. False to stop.
	 */
	public function afterDelete() {
		return true;
	}
	/**
	 * An array of fields for this model.
	 * @return array
	 */
	public function getFieldList() {
		return $this->fields;
	}
	/**
	 * An array of fields used to uniquely identify the data.
	 * @return array
	 */
	public function getPrimaryFieldList() {
		return $this->primary_fields;
	}
	/**
	 * Validates the data in this model. Meant to be overridden.
	 * Called before all before operations.
	 * @return boolean True if valid. False otherwise.
	 */
	public function validate() {
		return true;
	}
}
?>