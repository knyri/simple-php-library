<?php
require_once('model.php');
class user extends sql_model{
	private $logged_in=false,
		$validated=false;
	public function __construct(){
		$this->table='users';
		$this->primary_fields[]='user_id';
	}

	/**
	 * Enter description here ...
	 * @param string $password
	 * @param string $salt
	 * @return boolean
	 */
	public function login($password,$salt=''){
		if($this->get('upass')==md5($salt.$password))
			$this->logged_in=true;
		return $this->isLoggedIn();
	}
	/**
	 * Tests to see if the $key matches the one in
	 * the DB and sets the validated flag if it is.
	 * @param string $key
	 * @return boolean True if the $key matches
	 */
	public function validate($key){
		$this->validated=$this->get('verification')==$key;
		if($this->isValidated()){
			$this->set('verified','Y');
			$this->update();
		}
		return $this->isValidated();
	}
	/**
	* Returns the user's validation status.
	* @return boolean True if the user has been validated.
	*/
	public function isValidated(){
		return $this->validated;
	}
	/**
	 * Returns the user's logged in status.
	 * @return boolean True if the user has logged in
	 */
	public function isLoggedIn(){
		return $this->logged_in;
	}
	function onBeforeCreate(){
		$this->set('joined',date('yyyy-mm-dd'));
	}

}