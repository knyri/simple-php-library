<?php
/**
 * LDAP connection wrapper
 */
class Ldap {
	private $con;
	function __construct($host, $port= 389){
		$this->con= ldap_connect($host, $port);
	}
	function isValid(){
		return $this->con !== false;
	}
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
		if($res === false){
			return false;
		}else{
			return new LdapResult($this->con, $res);
		}
	}
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
		if($res === false){
			return false;
		}else{
			return new LdapResult($this->con, $res);
		}
	}
	function read($base_dn, $filter, $attributes= null, $attrsonly= null, $sizelimit= null, $timelimit= null, $deref= null){
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
		if($res === false){
			return false;
		}else{
			return new LdapResult($this->con, $res);
		}
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
		// TODO: update $this->dn
		return ldap_rename($this->con, $this->dn, $newrdn, $newparent, $deleteold);
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
		if($reuse){
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
}
