<?php
/**
 * @author Kenneth Pierce kcpiercejr@gmail.com
 */

PackageManager::requireClassOnce('ml.html');
PackageManager::requireClassOnce('error.IllegalArgumentException');
//require_once LIB.'lib/ml/class_html.inc.php';
//require_once LIB.'lib/error/class_IllegalArgumentException.php';
global $_DB, $_DB_OPEN_CON;
$_DB = null;
$_DB_OPEN_CON = false;
function db_isDebug(){
	if(isset($GLOBALS['simple']['lib']['db']['debug']))
		if($GLOBALS['simple']['lib']['db']['debug']==true)
			return true;
	return false;
}
/**
 * creates a connection to the database if none exists
 * or returns one already created.
 * @param boolean $forcenew Forces the creation of a new connection.
 * @return Ambigous <NULL, resource>
 */
function db_get_connection($forcenew = false) {
	global $_DB, $_DB_OPEN_CON;
	$conf = $GLOBALS['simple']['lib']['db'];
	if ($forcenew){db_close_connection();}
	if (!$_DB_OPEN_CON || $_DB = null) {
		$_DB = mysql_connect($conf['host'], $conf['user'], $conf['password']);
		mysql_select_db($conf['dbname']);
	}
	return $_DB;
}
/**
 * Closes the connection to the database.
 */
function db_close_connection() {
	global $_DB, $_DB_OPEN_CON;
	@mysql_close($_DB);
	$_DB_OPEN_CON = false;
}
/** Logs a database error.
 * Common usage:
 * if (!$res)
 *		echo db_log_error(mysql_error(), $query);
 * @param string $msg Error message
 * @param string $qry The query that caused it. Optional.
 * @return string $msg
 */
function db_log_error($msg, $qry = '') {
	$db = db_get_connection();
	mysql_query('INSERT INTO errors (err_date, err_msg, err_query) VALUES (NOW(), \''.clean_text($msg).'\', \''.clean_text($qry).'\')', $db);
	return $msg;
}
/** Checks to see if a record exists that matches the conditions.
 * @param resource $db mysql database link. Set to null to use the default settings.
 * @param string $table required
 * @param string $condition required
 * @return mixed boolean on success or a string containing the error message.
 */
function db_record_exist($db, $table, $condition) {
	if ($db===null)
		$db = db_get_connection();
	if ($condition) {
		$res = mysql_query("SELECT * FROM $table WHERE $condition", $db);
		if (!$res) {
			return db_log_error(mysql_error(), "SELECT * FROM $table WHERE $condition");
		}
		$ret = (mysql_num_rows($res) > 0);
		mysql_free_result($res);
		return $ret;
	} else {
		throw new IllegalArgumentException('$condition MUST be set.');
	}
}
/**
 * Alias of mysql_real_escape_string(...)
 * @param string $string
 * @return string
 */
function clean_text($string) {
	return mysql_real_escape_string($string, db_get_connection());
}
function db_do_operation($db, $operation, $default = null) {
	if ($db===null)
		$db= db_get_connection();
	$res = mysql_query("SELECT $operation", $db);
	if (!$res) {
		db_log_error(mysql_error(), "SELECT $operation");
		return $default;
	}
	$row = mysql_fetch_array($res);
	mysql_free_result($res);
	return $row[0];
}
/** Fetches a single column value from the database.
 * @param resource $db mysql database link. Set to null to use the default settings.
 * @param string $table
 * @param string $column
 * @param string $condition
 * @param mixed $default Default value if the query failed or no value was found.
 * @return mixed The found value or $default
 */
function db_get_column($db, $table, $column, $condition = null, $default = null, $cache = false) {
	if ($db===null)
		$db = db_get_connection();
	if ($condition) {
		if(is_array($condition))
			$condition=substr(_db_build_where($condition),6);
		$res = mysql_query("SELECT".($cache?'':' SQL_CACHE')." $column FROM $table WHERE $condition LIMIT 0,1", $db);
		if (!$res) {
			db_log_error(mysql_error(), "SELECT".($cache?'':' SQL_CACHE')." $column FROM $table WHERE $condition LIMIT 0,1");
			return $default;
		}
	} else {
		$res = mysql_query("SELECT".($cache?'':' SQL_CACHE')." $column FROM $table LIMIT 0,1", $db);
		if (!$res) {
			db_log_error(mysql_error(), "SELECT".($cache?'':' SQL_CACHE')." $column FROM $table LIMIT 0,1");
			return $default;
		}
	}
	$row = mysql_fetch_array($res);
	mysql_free_result($res);
	if ($row[$column] == null) return $default;
	return $row[$column];
}
/**
 * Alias of db_get_column(..)
 * @param resource $db mysql database link. Set to null to use the default settings.
 * @param string $table
 * @param string $column
 * @param string $condition
 * @param mixed $default
 * @param boolean $cache
 * @return mixed The found value or $default
 */
function db_get_field($db, $table, $column, $condition = '1=1', $default = null, $cache = false) {
	return db_get_column($db, $table, $column, $condition, $default, $cache);
}
/**
 * Shortcut for db_get_row($db,$table,$condition,$columns,$cache,MYSQL_ASSOC);
 * @param resource $db
 * @param string $table
 * @param string $condition
 * @param string $columns
 * @param boolean $cache
 * @return array the resulting array or null.
 */
function db_get_row_assoc($db, $table, $condition = null, $columns = '*', $cache = false) {
	return db_get_row($db, $table, $condition, $columns, $cache, MYSQL_ASSOC);
}
/** Fetches a single row from the database.
 * @param resource $db mysql database link. Set to null to use the default settings.
 * @param string $table
 * @param string $condition
 * @param string $columns
 * @param boolean $cache
 * @param int $type MYSQL_ASSOC, MYSQL_NUM, or MYSQL_BOTH. Defaults to MYSQL_BOTH.
 * @return array the resulting array or null.
 */
function db_get_row($db, $table, $condition=null, $columns='*', $cache=false, $type=MYSQL_BOTH) {
	if ($db===null)
		$db = db_get_connection();
	if ($condition) {
		if(is_array($condition))
			$condition=substr(_db_build_where($condition),6);
		$res = mysql_query("SELECT".($cache?'':' SQL_CACHE')." $columns FROM $table WHERE $condition LIMIT 0,1", $db);
		if (!$res)
			db_log_error(mysql_error(), "SELECT".($cache?'':' SQL_CACHE')." $columns FROM $table WHERE $condition LIMIT 0,1");
	} else {
		$res = mysql_query("SELECT".($cache?'':' SQL_CACHE')." $columns FROM $table LIMIT 0,1", $db);
		if (!$res)
			db_log_error(mysql_error(), "SELECT".($cache?'':' SQL_CACHE')." $columns FROM $table LIMIT 0,1");
	}
	if (!$res || mysql_num_rows($res)==0) return null;
	$row = mysql_fetch_array($res, $type);
	mysql_free_result($res);
	return $row;
}

/**
 * Prints a table displaying the result.
 * @param resource mysql_result
 */
function result_table($result) {
	echo '<table border="1" cellspacing="0" cellpadding="5" style="border-collapse: collapse" id="table1">';
		$rowc = 0;
		$color = "";
		$index = 0;
		$i = mysql_num_fields($result);
		echo '<tr>';
		for ($j=0; $j<$i;$j++)
			echo '<th>'.mysql_field_name($result, $j).'</th>';
		echo '</tr>';
		while ($row = mysql_fetch_array($result)) {
			$index = 0;
			if ($rowc%2 == 0)
				$color = "#FFFFFF";
			else
				$color = "#DDDDDD";
			echo "<tr>";
			foreach($row as $col) {

				if ($index%2 != 0)
					echo "<td bgcolor='$color'>".nl2br(htmlspecialchars($col))."</td>";
				$index++;
			}
			$rowc++;
			echo "</tr>";
		}
		echo '</table>';
}

/**
 * Returns the proper form of a variable for the query.
 * @param mixed $var
 * @return string|unknown|Ambigous <unknown, number>
 */
function _db_validate_value($var) {
	if (is_string($var)){
		if ($var == "NOW()" || ($var[0]=='b' && $var[1]=='\''))return $var;
		return "'" . clean_text($var) . "'";
	}else{
		return (is_bool($var)) ? intval($var) : $var;
	}
}
/** Builds the WHERE clause.
 * @param array $where Array of conditions to be met. Each element must be array(column, value, ['AND'|'OR']).
 *		The last element must have the 3rd argument ommited or set to NULL.
 *		Special elements:
 *		+array(column, 'IN', list, negate, ['AND'|'OR'])
 *			list must NOT be an array nor enclosed in ().
 *			negate must be true or false and indicates 'NOT IN' when true.
 *		+array(column, 'BETWEEN', lower, upper, negate, ['AND'|'OR'])
 *		+array(column, 'LIKE', string value, negate, ['AND'|'OR'])
 *		+array('LITERAL', literal, ['AND'|'OR'])
 * @return string the resulting condition
 */
function _db_build_where($where) {
	if (is_array($where)) {
		$where_2 = array();
		foreach($where as $arg) {
			if (count($arg) > 3) {
				if ($arg[1] == 'IN') {
					$where_2[] = $arg[0] . ($arg[3]?' NOT IN (':' IN (') . $arg[2] . ')' . ((count($arg)==5)?' '.$arg[4].' ' : '');
				} else if ($arg[1] == 'LIKE') {
					$where_2[] = $arg[0] . ($arg[3]?' NOT LIKE ':' LIKE ') . '\'' . $arg[2] . '\'' . ((count($arg)==5)?' '.$arg[4].' ' : '');
				} else if ($arg[1] == 'BETWEEN') {
					$where_2[] = $arg[0] . ($arg[4]?' NOT BETWEEN ':' BETWEEN ') . _db_validate_value($arg[2]) . ' AND ' . _db_validate_value($arg[3]) . ((count($arg)==6)?' '.$arg[5].' ' : '');
				}
			} else {
				if (count($arg) == 3) {
					if ($arg[0]=='LITERAL')
						$where_2[] = $arg[1] . ' '.$arg[2].' ';
					elseif($arg[1]==null || strtoupper($arg[1])=='NULL'){
						$where_2[] = $arg[0] . ' IS NULL '.$arg[2].' ';
					}elseif(strtoupper($arg[1])=='!NULL'){
						$where_2[] = $arg[0] . ' IS NOT NULL '.$arg[2].' ';
					}else
						$where_2[] = $arg[0] . '=' . _db_validate_value($arg[1]) . ' '.$arg[2].' ';
				} else {
					if ($arg[0]=='LITERAL')
						$where_2[] = $arg[1];
					elseif($arg[1]==null || strtoupper($arg[1])=='NULL'){
						$where_2[] = $arg[0] . ' IS NULL ';
					}elseif(strtoupper($arg[1])=='!NULL'){
						$where_2[] = $arg[0] . ' IS NOT NULL ';
					}else
						$where_2[] = $arg[0] . '=' . _db_validate_value($arg[1]);
				}
			}
		}
		return 'WHERE '.implode('', $where_2);
	} else
		return 'WHERE '.$where;
}
/** Queries the database and returns the result set or NULL if it failed.
 * @param resource $db mysql database link. Set to null to use the default settings.
 * @param string $table Name of the table
 * @param array|string $columns Array or comma delimited string of column names
 * @param array $where See _db_build_where(..).
 * @param array $sort array(array(column1, dir)[, array(column2, dir)[, ...]]) where dir=['ASC'|'DESC']
 * @param string $groupBy Column to group by.
 * @param string $having See mysql documentation on the HAVING clause.
 * @param int $limit Max number of rows to return
 * @param int $offset Row to start at
 * @return mixed resource or false on error.
 */
function db_query($db, $table, $columns = '*',array $where = null,array $sortBy = null, $groupBy = null, $having = null,$limit=0,$offset=0){
	if ($db===null)
		$db = db_get_connection();
	$query = 'SELECT ';
	if (is_array($columns))
		$query .= implode(', ', $columns);
	else
		$query .= $columns;
	if (!empty($table))
		$query .= " FROM $table";
	if ($where != null) {
		$query .= ' ' . _db_build_where($where);
	}
	if ($groupBy != null) {
		$query .= " GROUP BY $groupBy";
		if ($having != null)
			$query .= " HAVING $having";
	}
	if ($sortBy != null) {
		$query .= ' ORDER BY';
		foreach($sortBy as $sort) {
			$query .= " $sort[0] $sort[1],";
		}
		$query = substr($query, 0, -1);
	}
	if($limit>0){
		$query.=" LIMIT $limit";
		if($offset>0)
			$query.=" OFFSET $offset";
	}
	if(db_isDebug())
		echo $query;
	$res = mysql_query($query, $db);
	if (!$res)
		db_log_error(mysql_error(), $query);
	return $res;
}
/** Updates data in the database. Returns the error on failure and false on success.
 * @param resource $db mysql database link. Set to null to use the default settings.
 * @param string $table Name of the table
 * @param array $data Array of column names and the new values. Each element must be array[column]=value.
 * @param array $conditions See _db_build_where(..).
 * @return mixed false on success or the error on failure.
 */
function db_update($db, $table, array $data, array $conditions = null) {
	if ($db===null)
		$db = db_get_connection();
	$query = "UPDATE $table SET ";
	$data_2 = array();
	foreach($data as $key=>$value) {
		$data_2[] = $key.'='._db_validate_value($value);
	}
	$query .= implode(',', $data_2);
	if ($conditions != null)
		$query .= ' ' . _db_build_where($conditions);
	if(db_isDebug()) echo $query;
	$res = mysql_query($query, $db);
	if (!$res) {
		return db_log_error(mysql_error(), $query);
	}
	return false;
}

/** Inserts data into the database. Returns NULL on failure or false on success.
 * @param resource $db mysql database link. Set to null to use the default settings.
 * @param string $table Name of the table
 * @param array $data Array of column names and the new values. Each element must be array[column]=value.
 * @return mixed false on success or the error.
 */
function db_insert($db, $table, array $data) {
	if ($db===null)
		$db = db_get_connection();
	//printVar($data);
	//printVar(array_map('_db_validate_value', $data));
	$query = "INSERT INTO $table " . '(`' . implode('`, `',array_keys($data)) . '`) VALUES ('
		. implode(', ', array_map('_db_validate_value', $data)) . ')';
	$res = mysql_query($query, $db);
	if (!$res) {
		return db_log_error(mysql_error(), $query);
	} else {
		return false;
	}
}

/** Inserts data into the database. Returns the error message on failure or false on success.
 * @param resource $db mysql database link. Set to null to use the default settings.
 * @param string $table Name of the table
 * @param array $columns Array of column names.
 * @param array $data Multi-deminsional array of the values. $data = array(array(row data), array(row data), ...).
 */
function db_multi_insert($db, $table, array $columns, array $data) {
	if ($db===null)
		$db=db_get_connection();
	$query = "INSERT INTO $table (".implode(',',$columns).') VALUES ';
	$values = array();
	foreach ($data as $datum)
		$values[]= '('.implode(', ',array_map('_db_validate_value',$datum)).')';
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
 * @param resource $db mysql database link. Set to null to use the default settings.
 * @param string $table Name of the table
 * @param array $conditions See _db_build_where(..).
 * @return mixed false on success or the error
 */
function db_delete($db, $table, array $conditions = null) {
	if ($db===null)
		$db= db_get_connection();
	$query= "DELETE FROM $table";
	if (!is_null($conditions))
		$query.= ' '._db_build_where($conditions);
	$res = mysql_query($query,$db);
	if(!$res){
		return db_log_error(mysql_error(),$query);
	}else{
		return false;
	}
}
/**
 * Enter description here ...
 * @param resource $db
 * @param string $table
 * @param array $conditions see _db_build_where(...)
 * @return mixed false on error or the count.
 */
function db_num_rows($db,$table,$conditions=null){
	if ($db===null)
		$db= db_get_connection();
	if($conditions===null)
		$sql="SELECT COUNT(*) FROM $table";
	elseif(is_array($conditions))
		$sql="SELECT COUNT(*) FROM $table "._db_build_where($conditions);
	else
		$sql="SELECT COUNT(*) FROM $table WHERE $conditions";
	if(db_isDebug())echo $sql;
	$res=mysql_query($sql,$db);
	if(!$res){
		db_log_error(mysql_error(),$sql);
		return false;
	}
	$res=mysql_fetch_array($res);
	return $res[0];
}