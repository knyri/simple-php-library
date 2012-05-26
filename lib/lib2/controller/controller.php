<?php

PackageManager::requireClassOnce('object');
PackageManager::requireClassOnce('model.datamodel');

class Controller extends Object {
	/**
	 * Array of models used.
	 * @var array
	 */
	var $models = array();
}
?>