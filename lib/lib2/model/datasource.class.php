<?php

PackageManager::requireClassOnce('object');

/**
 * Serves as the source for data models.
 * @author Kenneth Pierce
 *
 */
class DataSource extends Object {
	var $connected = false;
	/**
	 * Creates the model in the datasource.
	 * @param DataModel $model
	 * @return boolean success
	 */
	public function create(DataModel $model) {
		return false;
	}
	/**
	 * Loads the model from the data source
	 * @param DataModel $model
	 * @return boolean success
	 */
	public function read(DataModel &$model) {
		return false;
	}
	/**
	 * Updates the model in the datasource.
	 * @param DataModel $model
	 * @return boolean success
	 */
	public function update(DataModel $model) {
		return false;
	}
	/**
	 * Deletes the model from the datasource.
	 * @param DataModel $model
	 * @return boolean success
	 */
	public function delete(DataModel $model) {
		return false;
	}
	/**
	 * Searches the data source.
	 * @param DataModel $model Fields set here are used to find the entry. The entry is then stored in the model.
	 * @param array $sort
	 * @param array $group
	 * @param array $having
	 * @param array $limit
	 * @return resource|boolean false on error
	 */
	public function query(DataModel &$model, array $sort = null, array $group = null, array $having = null, array $limit = null) {
		return false;
	}
	/**
	 * connects to the data source.
	 * @param array $config
	 * @return boolean
	 */
	public function connect(array $config) {
		return false;
	}
	/**
	 * Disconnects from the data source.
	 * @return boolean success
	 */
	public function disconnect() {
		return false;
	}
	/**
	 * Reconnects to the data source. Disconnects if needed.
	 * @param array $config
	 * @return boolean success
	 */
	public function reconnect(array $config) {
		if ($this->connected) $this->disconnect();
		return $this->connect($config);
	}
	/**
	 * Returns the connected status
	 * @return boolean
	 */
	public function connected() {
		return $connected;
	}
}
?>