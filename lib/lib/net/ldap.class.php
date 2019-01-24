<?php
/**
 * LDAP connection wrapper
*/
class Ldap {
	private
		$con,
		$result,
		$errHandler,
		$caughtError,
		$originalErrorHandler,
		$lastError
	;
	function __construct($host, $port= 389){
		$this->con= ldap_connect($host, $port);
		$this->errHandler= array($this, 'errHandler');
	}
	public static function escapeValue($str){
		return
			str_replace('(','\\(',
				str_replace(')','\\)',
					str_replace('\\', '\\\\', $str)
				)
			)
		;
	}
	private function listenForErrors(){
		$this->caughtError= false;
		$this->lastError= false;
		$this->originalErrHandler= set_error_handler($this->errHandler);
	}
	private function stopListeningForErrors(){
		restore_error_handler();
		if($this->caughtError){
			$this->caughtError= false;
		}
	}
	public function errHandler($number, $string, $file, $line, $context){
		$error= explode(':', $string, 3);
		$this->caughtError= true;
		$this->lastError= trim($error[1]);
		if(function_exists('logit')){
			logit("$string\n$file\n$line\n".print_r($context, true));
		}
	}
	/** The result from ldap_connect
	 * @return boolean
	 */
	function isValid(){
		return $this->con !== false;
	}
	/**
	 * @param string $rdn
	 * @param string $password
	 * @return boolean
	 */
	function bind($rdn= null, $password= null){
		return ldap_bind($this->con, $rdn, $password);
	}
	function unbind(){
		return ldap_unbind($this->con);
	}
	function startTls(){
		return ldap_start_tls($this->con);
	}
	/**
	 * Adds attributes to the DN
	 * @param string $dn
	 * @param array $entry
	 * @return boolean
	 */
	function add($dn, array $entry){
		return ldap_add($this->con, $dn, $entry);
	}
	/**
	 * Tests to see if the entry's attribute has that value
	 * @param string $dn
	 * @param string $attribute
	 * @param string $value
	 * @return mixed Returns TRUE if value matches otherwise returns FALSE. Returns -1 on error.
	 */
	function compare($dn, $attribute, $value){
		return ldap_compare($this->con, $dn, $attribute, $value);
	}
	/**
	 * Deletes an entry
	 * @param string $dn
	 * @return boolean
	 */
	function delete($dn){
		return ldap_delete($this->con, $dn);
	}
	/**
	 * The last error
	 * @return string
	 */
	function getError(){
		return ldap_error($this->con);
	}
	/**
	 * The last error number
	 * @return number
	 */
	function getErrorNum(){
		return ldap_errno($this->con);
	}
	/**
	 * Sets the option
	 * @param number $option
	 * @param mixed $value
	 * @return boolean
	 */
	function setOption($option, $value){
		return ldap_set_option($this->con, $option, $value);
	}
	/**
	 * @param number $option
	 * @param mixed $value
	 * @return boolean
	 */
	function getOption($option, &$value){
		return ldap_get_option($this->con, $option, $value);
	}
	/**
	 * @param string $dn Entity to rename
	 * @param string $newrdn The new RDN
	 * @param string $newparent The new parent entry
	 * @param boolean $deleteold If TRUE the old RDN value(s) is removed, else the old RDN value(s) is retained as non-distinguished values of the entry.
	 * @return boolean
	 */
	function rename($dn, $newrdn, $newparent, $deleteold){
		return ldap_rename($this->con, $dn, $newrdn, $newparent, $deleteold);
	}
	/**
	 * The result of the last search, read, or listEntries
	 * @return boolean|LdapResult
	 */
	function getResult(){
		return $this->result;
	}
	/**
	 * Does not recurse
	 * @param string|array $base_dn
	 * @param string|array $filter
	 * @param array $attributes
	 * @param boolean $attrsonly
	 * @param int $sizelimit
	 * @param int $timelimit
	 * @param int $deref
	 * @return boolean|LdapResult
	 */
	function listEntries($base_dn, $filter, $attributes= null, $attrsonly= null, $sizelimit= null, $timelimit= null, $deref= null){
		if($attributes){
			if($attrsonly){
				if($sizelimit){
					if($timelimit){
						if($deref){
							$res= ldap_list($this->con, $base_dn, $filter, $attributes, $attrsonly, $sizelimit, $timelimit, $deref);
						}else{
							$res= ldap_list($this->con, $base_dn, $filter, $attributes, $attrsonly, $sizelimit, $timelimit);
						}
					}else{
						$res= ldap_list($this->con, $base_dn, $filter, $attributes, $attrsonly, $sizelimit);
					}
				}else{
					$res= ldap_list($this->con, $base_dn, $filter, $attributes, $attrsonly);
				}
			}else{
				$res= ldap_list($this->con, $base_dn, $filter, $attributes);
			}
		}else{
			$res= ldap_list($this->con, $base_dn, $filter);
		}
		if(is_array($res)){
			$this->result= array_map(function($e){return new LdapResult($this->con, $e);}, $res);
		}else{
			$this->result= $res === false ? false : new LdapResult($this->con, $res);
		}
		return $this->result;
	}
	/**
	 * Searches $base_dn and recurses into child DNs
	 * @param string|array $base_dn
	 * @param string|array $filter
	 * @param array $attributes
	 * @param boolean $attrsonly
	 * @param int $sizelimit
	 * @param int $timelimit
	 * @param int $deref
	 * @return boolean|LdapResult
	 */
	function search($base_dn, $filter, $attributes= null, $attrsonly= null, $sizelimit= null, $timelimit= null, $deref= null){
		$this->listenForErrors();
		if($attributes){
			if($attrsonly){
				if($sizelimit){
					if($timelimit){
						if($deref){
							$res= ldap_search($this->con, $base_dn, $filter, $attributes, $attrsonly, $sizelimit, $timelimit, $deref);
						}else{
							$res= ldap_search($this->con, $base_dn, $filter, $attributes, $attrsonly, $sizelimit, $timelimit);
						}
					}else{
						$res= ldap_search($this->con, $base_dn, $filter, $attributes, $attrsonly, $sizelimit);
					}
				}else{
					$res= ldap_search($this->con, $base_dn, $filter, $attributes, $attrsonly);
				}
			}else{
				$res= ldap_search($this->con, $base_dn, $filter, $attributes);
			}
		}else{
			$res= ldap_search($this->con, $base_dn, $filter);
		}
		$this->stopListeningForErrors();
		if(is_array($res)){
			$this->result= array_map(function($e){return new LdapResult($this->con, $e);}, $res);
		}else{
			$this->result= $res === false ? false : new LdapResult($this->con, $res, $this->lastError && strpos($this->lastError, 'Partial search results returned') === 0);
		}
		return $this->result;
	}
	/**
	 * Read a single entry
	 * @param string|array $base_dn
	 * @param string|array $filter
	 * @param array $attributes
	 * @param boolean $attrsonly
	 * @param int $sizelimit
	 * @param int $timelimit
	 * @param int $deref
	 * @return boolean|LdapResult
	 */
	function read($base_dn, $filter='(objectClass=*)', $attributes= null, $attrsonly= null, $sizelimit= null, $timelimit= null, $deref= null){
		if($attributes){
			if($attrsonly){
				if($sizelimit){
					if($timelimit){
						if($deref){
							$res= ldap_read($this->con, $base_dn, $filter, $attributes, $attrsonly, $sizelimit, $timelimit, $deref);
						}else{
							$res= ldap_read($this->con, $base_dn, $filter, $attributes, $attrsonly, $sizelimit, $timelimit);
						}
					}else{
						$res= ldap_read($this->con, $base_dn, $filter, $attributes, $attrsonly, $sizelimit);
					}
				}else{
					$res= ldap_read($this->con, $base_dn, $filter, $attributes, $attrsonly);
				}
			}else{
				$res= ldap_read($this->con, $base_dn, $filter, $attributes);
			}
		}else{
			$res= ldap_read($this->con, $base_dn, $filter);
		}
		if(is_array($res)){
			$this->result= array_map(function($e){return new LdapResult($this->con, $e);}, $res);
		}else{
			$this->result= $res === false ? false : new LdapResult($this->con, $res);
		}
		return $this->result;
	}
}
/**
 * Result from list, search, and read
 */
class LdapResult {
	private
	$con,
	$result,
	$errcode,
	$matcheddn,
	$errmsg,
		$referrals,
		$sizeLimitHit
	;
	/**
	 * @param resource $con The LDAP connection
	 * @param resource $result The LDAP result
	 * @param boolean $hasMoreResults If SizeLimit was hit
	 */
	function __construct($con, $result, $hasMoreResults= false){
		$this->con= $con;
		$this->result= $result;
		$this->sizeLimitHit= $hasMoreResults;
	}
	function sizeLimitHit(){
		return $this->sizeLimitHit;
	}
	function countEntries(){
		return ldap_count_entries($this->con, $this->result);
	}
	function firstEntry(){
		$entry= ldap_first_entry($this->con, $this->result);
		if($entry === false){
			return false;
		}
		return new LdapResultEntry($this->con, $entry);
	}
	function getEntries(){
		return ldap_get_entries($this->con, $this->result);
	}
	/**
	 * Parse result meta-data
	 * @return boolean
	 */
	function parse(){
		return ldap_parse_result($this->con, $this->result, $this->errcode, $this->matcheddn, $this->errmsg, $this->referrals);
	}
	function getParsedReferrals(){
		return $this->referrals;
	}
	function getParsedErrorCode(){
		return $this->errcode;
	}
	function getParsedErrorMessage(){
		return $this->errmsg;
	}
	function getParsedMatchedDn(){
		return $this->matcheddn;
	}
	/**
	 * ldap_free_result(...)
	 * @return boolean
	 */
	function close(){
		return ldap_free_result($this->result);
	}
	function getError(){
		return ldap_error($this->con);
	}
	function getErrorNum(){
		return ldap_errno($this->con);
	}
}
class LdapObject{
	protected
	$con,
	$dn;
	function __construct($con, $dn){
		$this->con= $con;
		$this->dn= $dn;
	}
	function delete($entry= null){
		if($entry == null){
			$entry= $this->dn;
		}
		return ldap_delete($this->con, $entry);
	}
	function getDn(){
		return $this->dn;
	}
	function addAttr($attrValues){
		return ldap_mod_add($this->con, $this->dn, $attrValues);
	}
	function delAttr($attrValues){
		return ldap_mod_del($this->con, $this->dn, $attrValues);
	}
	function replaceAttr($attrValues){
		return ldap_mod_replace($this->con, $this->dn, $attrValues);
	}
	function compare($attr, $value){
		return ldap_compare($this->con, $this->dn, $attr, $value);
	}
	function explodeDn($valuesOnly= false){
		return ldap_explode_dn($this->dn, $valuesOnly ? 1 : 0);
	}
	function add($entry){
		return ldap_add($this->con, $this->dn, $entry);
	}
	function modify($entry){
		return ldap_modify($this->con, $this->dn, $entry);
	}
	function rename($newrdn, $newparent, $deleteold){
		if(ldap_rename($this->con, $this->dn, $newrdn, $newparent, $deleteold)){
			$this->dn= $newrdn;
			return true;
		}
		return false;
	}
	function getError(){
		return ldap_error($this->con);
	}
	function getErrorNum(){
		return ldap_errno($this->con);
	}
}
/**
 * Entry from an LDAP result
 *
 */
class LdapResultEntry extends LdapObject{
	private
	$entry,
	$reuse= true;
	function __construct($con, $entry){
		parent::__construct($con, ldap_get_dn($con, $entry));
		$this->entry= $entry;
	}
	/**
	 * If true (default) then #next() will not create a new LdapResultEntry.
	 * @param boolean $reuse
	 */
	function setReuse($reuse){
		$this->reuse= $reuse === true;
	}
	/**
	 * The next entry or false if this is the last entry.
	 * @return boolean|LdapResultEntry
	 */
	function next(){
		$entry= ldap_next_entry($this->con, $this->entry);
		if($entry === false){
			return false;
		}
		if($this->reuse){
			$this->entry= $entry;
			$this->dn = ldap_get_dn($this->con, $entry);
			return $this;
		}
		return new LdapResultEntry($this->con, $entry);
	}

	function getValues($attribute){
		return ldap_get_values($this->con, $this->entry, $attribute);
	}
	/**
	 * return ldap_get_values_len(...)
	 * @param string $attribute
	 */
	function getValuesBinary($attribute){
		return ldap_get_values_len($this->con, $this->entry, $attribute);
	}
	function firstAttribute(){
		return ldap_first_attribute($this->con, $this->entry);
	}
	function nextAttribute(){
		return ldap_next_attribute($this->con, $this->entry);
	}
	function getAttributes(){
		return ldap_get_attributes($this->con, $this->entry);
	}
	function getAttributeIterator(){
		return new LdapAttributes($this->getAttributes());
	}
	function getDn(){
		return ($this->dn= ldap_get_dn($this->con, $this->entry));
	}
	/**
	 * Echos or returns all the attrubutes and values
	 * @param bool $return
	 */
	function dump($return= false){
		if($return){
			ob_start();
		}
		foreach($this->getAttributes() as $attr => $val){
			if(is_int($attr) || $attr === 'count'){
				continue;
			}
			echo $attr .'=';
			print_r($val);
			echo PHP_EOL;
		}
		if($return){
			return ob_get_clean();
		}
	}
}
class LdapAttributes {
	private $attrs, $idx= 0;
	function __construct(array $attrs){
		$this->attrs= $attrs;
	}
	function length(){
		return $this->attrs['count'];
	}
	/**
	 * Get's the value of the attribute
	 * @param string|int $key
	 */
	function getValue($key){
		if(is_int($key)){
			return $this->attrs[$this->attrs[$key]];
		}
		return $this->attrs[$key];
	}
	/**
	 * Get's the key name of the n-th attribute
	 * @param int $idx
	 * @return mixed
	 */
	function getKey($idx){
		return $this->attrs[$key];
	}
	/**
	 * Gets the attribute at the n-th index or false
	 * @param int $idx
	 * @return boolean|LdapAttribute
	 */
	function getAttribute($idx){
		if($idx > $this->length() || $idx < 0){
			return false;
		}
		return new LdapAttribute($this->attrs[$idx], $this->attrs[$this->attrs[$idx]]);
	}
	/**
	 * @param string $key
	 * @return LdapAttribute|boolean
	 */
	function getAttributeObject($key){
		if(array_key_exists($key, $this->attrs)){
			return new LdapAttribute($key, $this->attrs[$key]);
		}
		return false;
	}
	/**
	 * Will a call to next() return something?
	 * @return boolean
	 */
	function hasMore(){
		return $this->idx < $this->length();
	}
	/**
	 * Resets the internal index to 0 and returns the first attribute
	 * @return boolean|LdapAttribute
	 */
	function first(){
		$this->idx= 0;
		return $this->next();
	}
	/**
	 * Returns the next attribute in the set
	 * @return boolean|LdapAttribute
	 */
	function next(){
		return $this->getAttribute($this->idx++);
	}
}
class LdapAttribute{
	private $key, $val, $idx;
	function __construct($key, $val){
		$this->key= $key;
		$this->val= $val;
	}
	/**
	 * @return string
	 */
	public function key(){
		return $this->key;
	}
	public function length(){
		return $this->val['count'];
	}
	/**
	 * Will a call to next() return something?
	 * @return boolean
	 */
	function hasMore(){
		return $this->idx < $this->length();
	}
	/**
	 * @param int $idx
	 * @return boolean|string
	 */
	public function get($idx){
		if($idx < 0 || $idx > $this->length()){
			return false;
		}
		return $this->val[$idx];
	}
	/**
	 * @return boolean|string
	 */
	public function next(){
		return $this->get($this->idx++);
	}
	/**
	 * @return boolean|string
	 */
	public function first(){
		$this->idx= 0;
		return $this->next();
	}

}
