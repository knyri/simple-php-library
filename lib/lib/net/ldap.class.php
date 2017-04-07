<?php
/**
 * LDAP connection wrapper
*/
class Ldap {
	private $con, $result;
	function __construct($host, $port= 389){
		$this->con= ldap_connect($host, $port);
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
	function add($dn, $entry){
		return ldap_add($this->con, $dn, $entry);
	}
	function compare($dn, $attribute, $value){
		return ldap_compare($this->con, $dn, $attribute, $value);
	}
	function delete($dn){
		return ldap_delete($this->con, $dn);
	}
	function getError(){
		return ldap_error($this->con);
	}
	function getErrorNum(){
		return ldap_errno($this->con);
	}
	function setOption($option, $value){
		return ldap_set_option($this->con, $option, $value);
	}
	function getOption($option, &$value){
		return ldap_get_option($this->con, $option, $value);
	}
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
		if(is_array($res)){
			$this->result= array_map(function($e){return new LdapResult($this->con, $e);}, $res);
		}else{
			$this->result= $res === false ? false : new LdapResult($this->con, $res);
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
 *
 */
class LdapResult {
	private
	$con,
	$result,
	$errcode,
	$matcheddn,
	$errmsg,
	$referrals;
	function __construct($con, $result){
		$this->con= $con;
		$this->result= $result;
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
	function setReuse($reuse){
		$this->reuse= $reuse === true;
	}
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