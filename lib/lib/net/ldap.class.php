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
	function search($base_dn, $filter, $attributes= false, $attrsonly= null, $sizelimit= false, $timelimit= false, $deref= false){
		$this->listenForErrors();
		if($attributes){
			if($attrsonly === null){
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
class LdapResult Implements IteratorAggregate{
	private
	$con,
	$result,
	$errcode,
	$matcheddn,
	$errmsg,
		$referrals= array(),
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
	public function getIterator(){
		return new LdapResultIterator($this->firstEntry());
	}
	/**
	 * True if the max number of entries was reached
	 * @return boolean
	 */
	function sizeLimitHit(){
		return $this->sizeLimitHit;
	}
	/**
	 * Number of entries in the result
	 * @return number
	 * @see ldap_count_entries()
	 */
	function countEntries(){
		return ldap_count_entries($this->con, $this->result);
	}
	/**
	 * First entry in this result or false
	 * @return boolean|LdapResultEntry
	 */
	function firstEntry(){
		$entry= ldap_first_entry($this->con, $this->result);
		if($entry === false){
			return false;
		}
		return new LdapResultEntry($this->con, $entry);
	}
	/**
	 * An array of entry arrays or false
	 * @return array
	 * @see ldap_get_entries()
	 */
	function getEntries(){
		return ldap_get_entries($this->con, $this->result);
	}
	/**
	 * Parse result meta-data
	 * Fetches
	 * Error code
	 * Matched DN
	 * Error message
	 * Referrals
	 * @return boolean
	 * @see ldap_parse_result()
	 */
	function parse(){
		return ldap_parse_result($this->con, $this->result, $this->errcode, $this->matcheddn, $this->errmsg, $this->referrals);
	}
	/**
	 * Call parse() first
	 * @return array
	 */
	function getParsedReferrals(){
		return $this->referrals;
	}
	/**
	 * Call parse() first
	 * @return int
	 */
	function getParsedErrorCode(){
		return $this->errcode;
	}
	/**
	 * Call parse() first
	 * @return string
	 */
	function getParsedErrorMessage(){
		return $this->errmsg;
	}
	/**
	 * Call parse() first
	 * @return string
	 */
	function getParsedMatchedDn(){
		return $this->matcheddn;
	}
	/**
	 * Frees the resources used by the result
	 * @return boolean
	 * @see ldap_free_result()
	 */
	function close(){
		return ldap_free_result($this->result);
	}
	/**
	 * ldap_error()
	 * @return string
	 */
	function getError(){
		return ldap_error($this->con);
	}
	/**
	 * ldap_errno()
	 * @return number
	 */
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
	/**
	 * @param array $entry An array that specifies the information about the entry. The values in the entries are indexed by individual attributes. In case of multiple values for an attribute, they are indexed using integers starting with 0.
	 * @return boolean
	 * @see ldap_add()
	 */
	function add(array $entry){
		return ldap_add($this->con, $this->dn, $entry);
	}
	/**
	 * @param array $entry
	 * @return boolean
	 * @see ldap_modify()
	 */
	function modify(array $entry){
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
class LdapResultIterator implements Iterator {
	private $first, $entry, $idx= 0;
	public function __construct(LdapResultEntry $first){
		$this->first= $this->entry= $first;
	}
	public function current(){
		return $this->entry;
	}
	public function key(){
		return $this->idx;
	}
	public function next(){
		$this->entry= $this->entry->next();
		$this->idx++;
	}
	public function valid(){
		return $this->entry !== false;
	}
	public function rewind(){
		$this->entry= $this->first;
		$this->idx= 0;
	}
}
/**
 * Entry from an LDAP result
 *
 */
class LdapResultEntry extends LdapObject{
	private
	$entry,
		$reuse= true,
		$attr= false
	;
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
			$this->attr= false;
			return $this;
		}
		return new LdapResultEntry($this->con, $entry);
	}

	/**
	 * @param string $name
	 * @return boolean
	 */
	function hasAttribute($name){
		$attr= $this->getAttributes();
		return $attr && array_key_exists($name, $attr);
	}

	/**
	 * @param string $attribute
	 * @return array an array of values for the attribute on success and false on error. The number of values can be found by indexing "count" in the resultant array. Individual values are accessed by integer index in the array. The first index is 0.
	 * LDAP allows more than one entry for an attribute, so it can, for example, store a number of email addresses for one person's directory entry all labeled with the attribute "mail" return_value["count"] = number of values for attribute return_value[0] = first value of attribute return_value[i] = ith value of attribute
	 */
	function getValues($attribute){
		return ldap_get_values($this->con, $this->entry, $attribute);
	}
	/**
	 * Get all binary values from a result entry
	 * @param string $attribute
	 * @return array
	 * @see ldap_get_values_len(...)
	 */
	function getValuesBinary($attribute){
		return ldap_get_values_len($this->con, $this->entry, $attribute);
	}
	/**
	 * @return string|boolean the first attribute in the entry on success and false on error
	 * @see ldap_first_attribute(...)
	 */
	function firstAttribute(){
		return ldap_first_attribute($this->con, $this->entry);
	}
	/**
	 * @return string|boolean the next attribute in the entry on success and false on error
	 * @see ldap_next_attribute(...)
	 */
	function nextAttribute(){
		return ldap_next_attribute($this->con, $this->entry);
	}
	/**
	 * @return array|boolean a complete entry information in a multi-dimensional array on success and false on error.
	 * @see ldap_get_attributes(...)
	 */
	function getAttributes(){
		if(!$this->attr){
			$this->attr= ldap_get_attributes($this->con, $this->entry);
	}
		return $this->attr;
	}
	/**
	 * @return LdapAttributes|boolean
	 */
	function getAttributeIterator(){
		$attr= $this->getAttributes();
		if($attr == false){
			return false;
		}
		return new LdapAttributes($attr);
	}
	function getDn(){
		return $this->dn;
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
class LdapAttributes implements Iterator{
	private $attrs, $idx= 0;
	function __construct(array $attrs){
		$this->attrs= $attrs;
	}
	function current(){
		return $this->getValue($this->idx);
	}
	function valid(){
		return $this->hasMore();
	}
	function key(){
		return $this->idx;
	}
	function rewind(){
		return $this->first();
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
		return $this->attrs[$idx];
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
class LdapAttribute implements Iterator{
	private $key, $val, $idx=0;
	function __construct($key, $val){
		$this->key= $key;
		$this->val= $val;
	}
	function current(){
		return $this->getValue($this->key);
	}
	function valid(){
		return $this->hasMore();
	}
	function rewind(){
		return $this->first();
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
		if($idx < 0 || $idx >= $this->length()){
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
