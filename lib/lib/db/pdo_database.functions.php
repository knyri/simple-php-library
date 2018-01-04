<?php
/**
 * @author Kenneth Pierce kcpiercejr@gmail.com
* @package database
*/
// TODO: remove these. Kept for backwards compatability
require_once 'pdotable.class.php';
require_once 'pdostatementwrapper.class.php';
require_once 'OCIPDO.class.php';
PackageManager::requireClassOnce('error.IllegalArgumentException');
PackageManager::requireClassOnce('error.IllegalStateException');

global $_DB, $_DB_OPEN_CON;
$_DB= null;
$_DB_OPEN_CON= false;

/**
 *
 * Basic database profiling class.
 *
 */
class DBProfile{
	private static $queries= array(
		'insert'=> 0,
		'select'=> 0,
		'delete'=> 0,
		'update'=> 0,
		'run'=> 0
	);
	public static function query($type){
		self::$queries[$type]++;
	}
	public static function get($type){
		return self::$queries[$type];
	}
	public static function getTotal(){
		$sum=0;
		foreach(self::$queries as $v){
			$sum+=$v;
		}
			return $sum;
	}
}

/**
 * Sets/returns the state of the debugging status for the db_* functions
 * @param boolean $toggle (null) Optional.
 * @return boolean If the db_* functions provide debugging info.
 */
function db_debug($toggle=null){
	$conf=&LibConfig::getConfig('db');
	if($toggle!==null){
		$conf['debug']= $toggle;
	}
	return $conf['debug'] == true;
}
/**
 * creates a connection to the database if none exists
 * or returns one already created.
 * @param boolean $forcenew Forces the creation of a new connection.
 * @param string|array $db The name of a database defined in database.ini or a config array.
 *     example config:
 *         'name'=> 'Example OCI', // for caching
 *         'engine'=> 'oci',
 *         'host'=> 'localhost',
 *         'dbname'=> 'svc1',
 *         'user'=> 'user',
 *         'password'=> 'password'
 *    Host is optional for OCI. http://github.com/taq/pdooci is required for OCI support
 * @return Ambigous <NULL, resource> Returns a PDO object on success, null on failure. Throws a PDOException if database debug is on.
 */
function &db_get_connection($forcenew = false, $db = 'default') {
	global $_DB, $_DB_OPEN_CON;
	if(is_string($forcenew)){
		// because lazy
		$db= $forcenew;
		$forcenew= false;
	}
	if ($forcenew){
		db_close_connection($db);
	}

	if (!isset($_DB_OPEN_CON)){
		$_DB_OPEN_CON= array();
	}
	if (!isset($_DB)){
		$_DB= array();
	}

	if(is_array($db)){
		$conf= $db;
		$db= $conf['name'];
	}else{
		$conf= LibConfig::getConfig('db')[$db];
	}

	if (!$_DB_OPEN_CON[$db] || $_DB[$db] == null) {
		try{
			if($conf['engine'] == 'oci'){
				if($conf['host']){
				$_DB[$db] = new OCIPDO('oci:dbname=//'. $conf['host'] .'/'. $conf['dbname'], $conf['user'], $conf['password']);
				}else{
				$_DB[$db] = new OCIPDO('oci:dbname='. $conf['dbname'], $conf['user'], $conf['password']);
				}
			}else{
				$_DB[$db] = new PDO($conf['engine'].':host='.$conf['host'].';dbname='.$conf['dbname'],$conf['user'],$conf['password']);
			}
			$_DB_OPEN_CON[$db]= true;
			//$_DB->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
		}catch(PDOException $e){
			if(db_debug()){
				throw $e;
			}
			$_DB[$db]= null;
			$_DB_OPEN_CON[$db]= false;
		}
	}
	return $_DB[$db];
}
function db_make_connection($engine, $host, $dbname, $user, $password){
	if($engine == 'oci'){
		if($host){
			return new PDOOCI\PDO('oci:dbname=//'. $host .'/'. $dbname, $user, $password);
		}else{
			return new PDOOCI\PDO('oci:dbname='. $dbname, $user, $password);
		}
	}else{
		return new PDO($engine .':host='. $host .';dbname=' .$dbname, $user, $password);
	}
}
/**
 * Closes the connection to the database.
 */
function db_close_connection($db= 'default') {
	global $_DB, $_DB_OPEN_CON;
	if (isset($_DB)){
		$_DB[$db]= null;
	}
	if (isset($_DB_OPEN_CON)){
		$_DB_OPEN_CON[$db]= false;
	}
}
/** Logs a database error.
 * @param PDOStatement $statement
 * @param array $args (null) Arguments used
 * @return string string form of the error
 */
function db_log_error($statement,array $args=null) {
	static $stm=null;
	if($stm==null){
		$db = db_get_connection();
		if(!$db){
			throw new IllegalStateException('Failed to get a database object.');
		}
		$stm=$db->prepare('INSERT INTO errors (err_date, err_msg, err_query) VALUES (NOW(),:message,:query)');
		if(!$stm){
			throw new IllegalStateException('Failed to prepare the error statement.');
		}
	}
	if(!is_object($statement)){
		throw new IllegalArgumentException('$statement is not an object.');
	}
	$err=$statement->errorInfo();
	$params=array(
			':query'=>db_stm_to_string($statement->queryString,$args),
			':message'=>'Err array:'.var_export($err,true)
	);
	try{
		$stm->execute($params);
	}catch(PDOException $e){
		if(db_debug()){
			throw $e;
		}
	}
	return $err;
}
/** Checks to see if a record exists that matches the conditions.
 * @param PDO $db Set to null to use the default settings.
 * @param string $table required
 * @param array|WhereBuilder $condition required
 * @return mixed boolean on success or a string containing the error message.
 */
function db_record_exist($db, $table, $condition) {
	if ($condition) {
		$ret= db_num_rows($db, $table, $condition);
		if(is_numeric($ret)){
			return $ret > 0;
		}
		return false;
	} else {
		throw new IllegalArgumentException('$condition MUST be set.');
	}
}

/**
 * Returns the proper form of a variable for the query.
 * @param mixed $var
 * @return string|int
 * @deprecated
 */
function _db_validate_value($var) {
	if (is_null($var)){
		return 'NULL';
	}elseif (is_string($var)){
		if ($var == "NOW()")return $var;
		return "'" . clean_text($var) . "'";
	}else{
		return (is_bool($var)) ? intval($var) : $var;
	}
}
/** Builds the WHERE clause.
 * @param array $where Array of conditions to be met. Each element must be array(column, value[, 'AND'|'OR'|negate[, negate]]) (negate is optional).
 *		The last element must have the 3rd argument ommited or set to NULL.
 *		Special elements:
 *		+array(column, 'IN', list, negate[, 'AND'|'OR'])
 *			list must NOT be an array nor enclosed in ().
 *			negate must be true or false and indicates 'NOT IN' when true.
 *		+array(column, 'BETWEEN', lower, upper, negate[, 'AND'|'OR'])
 *		+array(column, 'LIKE', string value, negate[, 'AND'|'OR'])
 *		+array('LITERAL', literal[, 'AND'|'OR'])
 * @deprecated
 * @return array the resulting where string and array of values for a PDOStatement
 */
function _db_build_where(array $where) {
	if($where==null || count($where)==0){
		return '';
	}
	$ret=array('',array());
	$wcount=0;
	$where_2 = array();
	foreach($where as $arg){
		if(count($arg) > 3){
			if($arg[1] == 'IN'){
				$ret[1][':where'.($wcount)]=$arg[2];
				$where_2[]= "$arg[0]" . ($arg[3]?' NOT IN (':' IN (') . ':where'.($wcount) . ')' . ((count($arg)==5)?' '.$arg[4].' ' : '');
				$wcount++;
			}elseif($arg[1] == 'LIKE'){
				$ret[1][':where'.($wcount)]=$arg[2];
				$where_2[] = "$arg[0]" . ($arg[3]?' NOT LIKE ':' LIKE ') .':where'.($wcount) . ((count($arg)==5)?' '.$arg[4].' ' : '');
				$wcount++;
			}elseif($arg[1] == 'BETWEEN'){
				$ret[1][':where'.($wcount)]=$arg[2];
				$ret[1][':where'.($wcount+1)]=$arg[3];
				$where_2[]= "$arg[0]" . ($arg[4]?' NOT BETWEEN ':' BETWEEN ') . ':where'.($wcount) . ' AND ' . ':where'.($wcount+1) . ((count($arg)==6)?' '.$arg[5].' ' : '');
				$wcount+=2;
			}elseif($arg[1]===null){
				if($arg[3])
					$where_2[] ="$arg[0] IS NOT NULL $arg[2] ";
					else
						$where_2[] ="$arg[0] IS NULL $arg[2] ";
			}else{//What case is this?
				$ret[1][':where'.$wcount]=$arg[1];
				if($arg[4])
					$where_2[] ="$arg[0]=:where$wcount $arg[2] ";
					else
						$where_2[] ="$arg[0]!=:where$wcount $arg[2] ";
						$wcount++;
			}
		}else{
			if(count($arg) == 3){
				if ($arg[0]=='LITERAL'){
					$where_2[] = $arg[1] . ' '.$arg[2].' ';
				}elseif($arg[1]===null){
					if(is_bool($arg[2])){
						if($arg[2])
							$where_2[] ="$arg[0] IS NOT NULL";
							else
								$where_2[] ="$arg[0] IS NULL";
					}else
						$where_2[] ="$arg[0] IS NULL $arg[2] ";
				}else{
					$ret[1][':where'.$wcount]=$arg[1];
					$where_2[] ="$arg[0]=:where$wcount $arg[2] ";
					$wcount++;
				}
			}else{
				if($arg[0]=='LITERAL'){
					$where_2[] = $arg[1];
				}elseif($arg[1]===null){
					$where_2[] ="$arg[0] IS NULL ";
				}else{
					$ret[1][':where'.($wcount)]=$arg[1];
					$where_2[] = "$arg[0]=:where$wcount";
					$wcount++;
				}
			}
		}
	}
	$ret[0]=' WHERE '.implode('', $where_2);
	return $ret;
}
/**
 * Builds the WHERE clause.
 * @param array $where Array of conditions to be met. Each element must be array(column, value[, 'AND'|'OR'|negate[, negate]]) (negate is optional).
 *		The last element must have the 3rd argument ommited or set to NULL.
 *		Special elements:
 *		+array(column, 'IN', list, negate[, 'AND'|'OR'])
 *			list must NOT be an array nor enclosed in ().
 *			negate must be true or false and indicates 'NOT IN' when true.
 *		+array(column, 'BETWEEN', lower, upper, negate[, 'AND'|'OR'])
 *		+array(column, 'LIKE', string value, negate[, 'AND'|'OR'])
 *		+array('LITERAL', literal[, 'AND'|'OR'])
 * @return WhereBuilder the resulting where string and array of values for a PDOStatement
 */
function _db_build_where_obj(array $where) {
	$ret= new WhereBuilder();
	if($where==null || count($where)==0){
		return $ret;
	}
	foreach($where as $arg){
		if(count($arg) > 3){
			if($arg[1] == 'IN'){
				if(count($arg) == 5 && $arg[4] == 'OR'){
					$ret->orWhere($arg[0], 'in', $arg[2], $arg[3]);
				}else{
					$ret->andWhere($arg[0], 'in', $arg[2], $arg[3]);
				}
			}elseif($arg[1] == 'LIKE'){
				if(count($arg) == 5 && $arg[4] == 'OR'){
					$ret->orWhere($arg[0], 'like', $arg[2], $arg[3]);
				}else{
					$ret->andWhere($arg[0], 'like', $arg[2], $arg[3]);
				}
			}elseif($arg[1] == 'BETWEEN'){
				if(count($arg) == 6 && $arg[5] == 'OR'){
					$ret->orWhere($arg[0], 'between', $arg[2], $arg[3], $arg[4]);
				}else{
					$ret->andWhere($arg[0], 'between', $arg[2], $arg[3], $arg[4]);
				}
			}elseif($arg[1]===null){
				if($arg[2] == 'OR'){
					$ret->orWhere($arg[0], null, $arg[3]);
				}else{
					$ret->andWhere($arg[0], null, $arg[3]);
				}
			}else{
				if($arg[2] == 'OR'){
					$ret->orWhere($arg[0], '=', $arg[1]);
				}else{
					$ret->orWhere($arg[0], '!=', $arg[1]);
				}
			}
		}else{
			if(count($arg) == 3){
				if ($arg[0]=='LITERAL'){
					if($arg[2] == 'OR'){
						$ret->orLiteral($arg[1]);
					}else{
						$ret->andLiteral($arg[1]);
					}
				}elseif($arg[1]===null){
					if(is_bool($arg[2])){
						$ret->andWhere($arg[0], null, $arg[2]);
					}else{
						if($arg[2] == 'OR'){
							$ret->orWhere($arg[0], null);
						}else{
							$ret->andWhere($arg[0], null);
						}
					}
				}else{
					if($arg[2] == 'OR'){
						$ret->orWhere($arg[0], '=', $arg[1]);
					}else{
						$ret->andWhere($arg[0], '=', $arg[1]);
					}
				}
			}else{
				if($arg[0]=='LITERAL'){
					$ret->andLiteral($arg[1]);
				}elseif($arg[1]===null){
					$ret->andWhere($arg[0], null);
				}else{
					$ret->andWhere($arg[0], $arg[1]);
				}
			}
		}
	}
	return $ret;
}
/** Queries the database and returns the result set or NULL if it failed.
 * @param PDO $db Set to null to use the default settings.
 * @param string $table Name of the table
 * @param array $columns (null) Array or comma delimited string of column names
 * @param array|WhereBuilder $where (null) See _db_build_where(..).
 * @param array $sort (null) array(array(column1, dir)[, array(column2, dir)[, ...]]) where dir=['ASC'|'DESC']
 * @param string $groupBy (null) Column to group by.
 * @param string $having (null) See mysql documentation on the HAVING clause.
 * @param int $limit (0) Max number of rows to return
 * @param int $offset (0) Row to start at
 * @return PDOStatement The resulting PDOStatement
 * @throws PDOException If the statement couldn't be prepared
 */
function db_query($db, $table, array $columns= null, $where= null, array $sortBy= null, $groupBy= null, $having= null,$limit= 0,$offset= 0){
	DBProfile::query('select');
	if($db === null){
		$db= db_get_connection();
	}

	if($where !== null){
		if(is_array($where)){
			$where= _db_build_where_obj($where);
		}
	}

	$stm= db_prepare($db, db_make_query($table, $columns, $where, $sortBy, $groupBy, $having, $limit, $offset));

	if($where){
		db_run_query($stm, $where->getValues());
	}else{
		db_run_query($stm);
	}

	return $stm;
}
/**
 * Creates a query from the parameters.
 *
 * @param string $table Name of the table
 * @param array $columns (null) Array or comma delimited string of column names
 * @param WhereBuilder $where (null) See _db_build_where(..).
 * @param array $sort (null) array(array(column1, dir)[, array(column2, dir)[, ...]]) where dir=['ASC'|'DESC']
 * @param string $groupBy (null) Column to group by.
 * @param string $having (null) See mysql documentation on the HAVING clause.
 * @param int $limit (0) Max number of rows to return
 * @param int $offset (0) Row to start at
 * @return string The query
 */
function db_make_query($table, array $columns= null, WhereBuilder $where= null, array $sortBy= null, $groupBy= null, $having= null,$limit= 0, $offset= 0){
	$query = 'SELECT ';
	if($columns !== null){
		$query.= implode(',', $columns);
	}else{
		$query .= '*';
	}

	if(!empty($table)){
		$query .= ' FROM '.$table;
	}

	if($where){
		$query .= ' WHERE '.$where->getWhere();
	}
	if($groupBy != null){
		$query .= ' GROUP BY '.$groupBy;
		if($having != null){
			$query .=  'HAVING '.$having;
		}
	}
	if($sortBy != null){
		$query .= ' ORDER BY';
		foreach($sortBy as $sort) {
			$query .= " $sort[0] $sort[1],";
		}
		$query = substr($query, 0, -1);
	}
	if($limit > 0){
		$query.=" LIMIT $limit";
		if($offset > 0){
			$query.=" OFFSET $offset";
		}
	}
	return $query;
}

/** Inserts data into the database. Returns the error message on failure or false on success.
 * TODO: Fix to use PDO
 * @param resource $db mysql database link. Set to null to use the default settings.
 * @param string $table Name of the table
 * @param array $columns Array of column names.
 * @param array $data Multi-deminsional array of the values. $data = array(array(row data), array(row data), ...).
 * @deprecated
 */
function db_multi_insert($db, $table, array $columns, array $data) {
	DBProfile::query('insert');
	if ($db===null){
		$db=db_get_connection();
	}
	$query = "INSERT INTO $table (".implode(',',$columns).') VALUES ';
	$values = array();
	foreach ($data as $datum){
		$values[]= '('.implode(', ',array_map('_db_validate_value',$datum)).')';
	}
	$query.= implode(',', $values);
	unset($values);
	$res = mysql_query($query,$db);
	if(!$res){
		return db_log_error(mysql_error(),$query);
	}else{
		return false;
	}
}
/** Deletes data from the database. Returns the error message on failure or false on success.
 * @param PDO $db Set to null to use the default settings.
 * @param string $table Name of the table
 * @param array|WhereBuilder (null) $conditions See _db_build_where(..).
 * @return bool true on success
 */
function db_delete($db, $table, $conditions = null) {
	DBProfile::query('delete');
	if ($db===null){
		$db= db_get_connection();
	}
	$stm= "DELETE FROM \"$table\"";
	if ($conditions !== null){
		if(is_array($conditions)){
			$conditions=_db_build_where_obj($conditions);
		}
		$stm.=' WHERE '.$conditions->getWhere();
		$conditions= $conditions->getValues();
	}
	try{
		$stm = $db->prepare($stm);
	}catch(PDOException $e){
		if(db_debug()){
			throw $e;
		}else{
			return 'Could not prepare the statement.';
		}
	}
	return db_run_query($stm, $conditions);
}
/**
 * Returns the number of rows the conditions match.
 * @param PDO $db
 * @param string $table
 * @param array|WhereBuilder $conditions (null) see _db_build_where(...)
 * @return mixed false on error or the count.
 */
function db_num_rows(PDO $db,$table, $conditions=null){
	DBProfile::query('select');
	if ($db == null){
		$db= db_get_connection();
	}
	if($conditions === null){
		$stm= "SELECT COUNT(*) FROM $table";
		$conditions= array();
	}else{
		if(is_array($conditions)){
			$conditions= _db_build_where_obj($conditions);
		}
		$stm = "SELECT COUNT(*) FROM $table WHERE ".$conditions->getWhere();
		$conditions= $conditions->getValues();
	}
	try{
		$stm = db_prepare($db, $stm);
	}catch(PDOException $e){
		if(db_debug()){
			throw $e;
		}else{
			return false;
		}
	}
	if(!db_run_query($stm, $conditions)){
		return false;
	}
	$ret = $stm->fetch(PDO::FETCH_NUM);
	$stm->closeCursor();
	return $ret[0];
}
/**
 * Tests to see if any record matching the criteria exists.
 * @param PDO $db
 * @param string $table
 * @param array|WhereBuilder $conditions (null)
 * @throws PDOException
 * @return boolean|string false on error. '0' or '1'
 */
function db_exists($db,$table, $conditions=null){
	DBProfile::query('select');
	if ($db === null){
		$db= db_get_connection();
	}
	if($conditions === null){
		$stm= "SELECT EXISTS(SELECT 1 FROM $table)";
	}else{
		if(is_array($conditions)){
			$conditions=_db_build_where_obj($conditions);
		}
		$stm= "SELECT EXISTS(SELECT 1 FROM $table WHERE {$conditions->getWhere()})";
		$conditions= $conditions->getValues();
	}
	try{
		$stm = db_prepare($db,$stm);
	}catch(PDOException $e){
		if(db_debug()){
			throw $e;
		}else{
			return false;
		}
	}
	if(!db_run_query($stm, $conditions)){
		return false;
	}
	$ret = $stm->fetch(PDO::FETCH_NUM);
	$stm->closeCursor();
	return $ret[0];
}

/**
 * Exists for logging purposes.
 * @param PDOStatement $stm
 * @param array $params (null)
 * @return boolean The success
 */
function db_run_query($stm, array $params= null){
	DBProfile::query('run');
	if(db_debug()){
		echo '[['.db_stm_to_string($stm, $params).']]'."\n";
	}
	// MySQL specific
// 	if ($stm->execute($params) === false && $stm->errorCode() != '00000') {
// 		return db_log_error($stm, $params);
// 	}
	if($stm->execute($params) === false){
		db_log_error($stm, $params);
		return false;
	}
	return true;
}
/**
 * Attempts to take a PDOStatement and create the final SQL statement.
 * @param PDOStatement $stm
 * @param array $params (null)
 * @return string
 */
function db_stm_to_string($stm, array $params=null){
	if($params == null){
		return is_object($stm) ? $stm->queryString : $stm;
	}
	if(is_object($stm)){
		$stm= $stm->queryString;
	}
	foreach($params as $key => $value){
		$stm= str_replace($key, "'$value'", $stm);
	}
	return $stm;
}
/**
 * Attempts to prepare the statement.
 * @param PDO $db
 * @param string $query
 * @throws PDOException on error if db_debug() returns true
 * @return boolean|PDOStatement false on error
 */
function db_prepare(PDO $db, $query){
	try{
		$query= $db->prepare($query);
	}catch(PDOException $e){
		if(db_debug()){
			throw $e;
		}else{
			return false;
		}
	}
	return $query;
}

/**
 * Convenience method for <code>date('Y-m-d',time())</code>
 * @return string
 */
function db_now(){
	return date('Y-m-d',time());
}
